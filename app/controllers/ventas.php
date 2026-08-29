<?php
/**
 * Controlador de Ventas / POS (API REST).
 * Registra la venta de forma atomica (transaccion): cabecera + detalle +
 * pago, y descuenta el stock. Calcula subtotal, IVA (12%) y descuento.
 */

declare(strict_types=1);

function api_ventas_list(): void
{
    require_api_login();
    $rows = db()->query(
        'SELECT v.id, v.fecha, v.subtotal, v.iva, v.descuento, v.total,
                v.metodo_pago, v.estado,
                u.nombre AS cajero, cl.nombre AS cliente
         FROM ventas v
         JOIN usuarios u  ON u.id = v.id_usuario
         LEFT JOIN clientes cl ON cl.id = v.id_cliente
         ORDER BY v.fecha DESC
         LIMIT 200'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['total'] = (float) $r['total'];
    }
    json_ok($rows, 'Listado de ventas.');
}

/**
 * Devuelve la venta completa con su detalle (usado por la factura y el QR).
 */
function api_ventas_get(string $id): void
{
    require_api_login();
    $venta = obtener_venta_completa((int) $id);
    if (!$venta) {
        json_error('Venta no encontrada.', 404);
    }
    json_ok($venta, 'Detalle de la venta.');
}

/**
 * POST /api/ventas
 * Cuerpo esperado:
 * {
 *   "id_cliente": 1,
 *   "metodo_pago": "efectivo|tarjeta|QR",
 *   "descuento": 0,
 *   "referencia_api": null,
 *   "items": [ { "id_producto": 4, "cantidad": 2 }, ... ]
 * }
 */
function api_ventas_create(): void
{
    require_api_login();
    $in = body_json();

    $items = $in['items'] ?? [];
    if (!is_array($items) || count($items) === 0) {
        json_error('La venta debe tener al menos un producto.', 422);
    }

    $metodo = in_array($in['metodo_pago'] ?? 'efectivo', ['efectivo', 'tarjeta', 'QR'], true)
        ? $in['metodo_pago'] : 'efectivo';
    $descuento = max(0.0, (float) ($in['descuento'] ?? 0));
    $idCliente = !empty($in['id_cliente']) ? (int) $in['id_cliente'] : null;

    $user  = current_user();
    $config = require __DIR__ . '/../config.php';
    $tasaIva = (float) $config['app']['iva'];

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // 1) Validar productos, stock y calcular subtotal
        $subtotal = 0.0;
        $lineas = [];
        $stmtProd = $pdo->prepare('SELECT id, nombre, precio, stock FROM productos WHERE id = ? AND activo = 1 FOR UPDATE');

        foreach ($items as $item) {
            $idProd   = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if ($idProd <= 0 || $cantidad <= 0) {
                throw new RuntimeException('Item invalido en la venta.');
            }
            $stmtProd->execute([$idProd]);
            $prod = $stmtProd->fetch();
            if (!$prod) {
                throw new RuntimeException("Producto ID {$idProd} no existe.");
            }
            if ((int) $prod['stock'] < $cantidad) {
                throw new RuntimeException("Stock insuficiente para '{$prod['nombre']}' (disponible: {$prod['stock']}).");
            }
            $lineaSub = round((float) $prod['precio'] * $cantidad, 2);
            $subtotal += $lineaSub;
            $lineas[] = [
                'id_producto'     => $idProd,
                'cantidad'        => $cantidad,
                'precio_unitario' => (float) $prod['precio'],
                'subtotal'        => $lineaSub,
            ];
        }

        if ($descuento > $subtotal) {
            throw new RuntimeException('El descuento no puede ser mayor al subtotal.');
        }

        $baseGravable = $subtotal - $descuento;
        $iva   = round($baseGravable * $tasaIva, 2);
        $total = round($baseGravable + $iva, 2);

        // 2) Insertar cabecera de la venta
        $stmt = $pdo->prepare(
            'INSERT INTO ventas (id_usuario, id_cliente, subtotal, iva, descuento, total, metodo_pago)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $idCliente, $subtotal, $iva, $descuento, $total, $metodo]);
        $idVenta = (int) $pdo->lastInsertId();

        // 3) Insertar detalle y descontar stock
        $stmtDet   = $pdo->prepare(
            'INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmtStock = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
        foreach ($lineas as $l) {
            $stmtDet->execute([$idVenta, $l['id_producto'], $l['cantidad'], $l['precio_unitario'], $l['subtotal']]);
            $stmtStock->execute([$l['cantidad'], $l['id_producto']]);
        }

        // 4) Registrar el pago
        $referencia = $metodo === 'QR' ? ('QR-REF-' . str_pad((string) $idVenta, 6, '0', STR_PAD_LEFT)) : ($in['referencia_api'] ?? null);
        $stmtPago = $pdo->prepare(
            'INSERT INTO pagos (id_venta, monto, metodo, referencia_api) VALUES (?, ?, ?, ?)'
        );
        $stmtPago->execute([$idVenta, $total, $metodo, $referencia]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_error('No se pudo registrar la venta: ' . $e->getMessage(), 422);
    }

    json_ok(obtener_venta_completa($idVenta), 'Venta registrada correctamente.', 201);
}

/**
 * Anula una venta y devuelve el stock (solo admin).
 */
function api_ventas_anular(string $id): void
{
    require_api_admin();
    $id  = (int) $id;
    $pdo = db();

    $stmt = $pdo->prepare('SELECT estado FROM ventas WHERE id = ?');
    $stmt->execute([$id]);
    $venta = $stmt->fetch();
    if (!$venta) {
        json_error('Venta no encontrada.', 404);
    }
    if ($venta['estado'] === 'anulada') {
        json_error('La venta ya estaba anulada.', 409);
    }

    try {
        $pdo->beginTransaction();
        $det = $pdo->prepare('SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = ?');
        $det->execute([$id]);
        $stmtStock = $pdo->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');
        foreach ($det->fetchAll() as $linea) {
            $stmtStock->execute([(int) $linea['cantidad'], (int) $linea['id_producto']]);
        }
        $pdo->prepare("UPDATE ventas SET estado = 'anulada' WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_error('No se pudo anular la venta.', 500);
    }

    json_ok(null, 'Venta anulada y stock restaurado.');
}

/**
 * Arma el objeto completo de una venta (cabecera + cliente + cajero + detalle).
 */
function obtener_venta_completa(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT v.*, u.nombre AS cajero,
                cl.nombre AS cliente_nombre, cl.nit AS cliente_nit, cl.direccion AS cliente_direccion
         FROM ventas v
         JOIN usuarios u ON u.id = v.id_usuario
         LEFT JOIN clientes cl ON cl.id = v.id_cliente
         WHERE v.id = ?'
    );
    $stmt->execute([$id]);
    $venta = $stmt->fetch();
    if (!$venta) {
        return null;
    }

    $det = db()->prepare(
        'SELECT d.*, p.nombre AS producto, p.codigo
         FROM detalle_ventas d JOIN productos p ON p.id = d.id_producto
         WHERE d.id_venta = ?'
    );
    $det->execute([$id]);

    $venta['subtotal']  = (float) $venta['subtotal'];
    $venta['iva']       = (float) $venta['iva'];
    $venta['descuento'] = (float) $venta['descuento'];
    $venta['total']     = (float) $venta['total'];
    $venta['detalle']   = $det->fetchAll();

    return $venta;
}
