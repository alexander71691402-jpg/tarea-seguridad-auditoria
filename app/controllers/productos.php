<?php
/**
 * Controlador de Productos / Inventario (API REST) - CRUD completo.
 * Incluye busqueda, alerta de stock bajo y carga de imagen al servidor.
 */

declare(strict_types=1);

const STOCK_MINIMO = 5;

/**
 * GET /api/productos
 * Parametros opcionales: ?q= (busqueda por nombre o codigo),
 *                        ?categoria= (id), ?stock_bajo=1
 */
function api_productos_list(): void
{
    require_api_login();

    $q          = trim($_GET['q'] ?? '');
    $categoria  = $_GET['categoria'] ?? '';
    $soloBajos  = ($_GET['stock_bajo'] ?? '') === '1';

    $sql = 'SELECT p.*, c.nombre AS categoria,
                   (p.stock < ' . STOCK_MINIMO . ') AS stock_bajo
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.id_categoria
            WHERE p.activo = 1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (p.nombre LIKE ? OR p.codigo LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($categoria !== '') {
        $sql .= ' AND p.id_categoria = ?';
        $params[] = (int) $categoria;
    }
    if ($soloBajos) {
        $sql .= ' AND p.stock < ' . STOCK_MINIMO;
    }
    $sql .= ' ORDER BY p.nombre';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Normaliza tipos para el JSON
    foreach ($rows as &$r) {
        $r['precio']     = (float) $r['precio'];
        $r['stock']      = (int) $r['stock'];
        $r['stock_bajo'] = (bool) $r['stock_bajo'];
    }
    json_ok($rows, 'Listado de productos.');
}

function api_productos_get(string $id): void
{
    require_api_login();
    $stmt = db()->prepare(
        'SELECT p.*, c.nombre AS categoria FROM productos p
         LEFT JOIN categorias c ON c.id = p.id_categoria WHERE p.id = ?'
    );
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Producto no encontrado.', 404);
    }
    $row['precio'] = (float) $row['precio'];
    $row['stock']  = (int) $row['stock'];
    json_ok($row, 'Detalle del producto.');
}

function api_productos_create(): void
{
    require_api_admin();
    $in = body_json();

    $codigo = trim($in['codigo'] ?? '');
    $nombre = trim($in['nombre'] ?? '');
    $precio = (float) ($in['precio'] ?? 0);

    if ($codigo === '' || $nombre === '') {
        json_error('Codigo y nombre son obligatorios.', 422);
    }
    if ($precio < 0) {
        json_error('El precio no puede ser negativo.', 422);
    }

    $imagen = guardar_imagen_producto() ?? ($in['imagen_url'] ?? null);

    $stmt = db()->prepare(
        'INSERT INTO productos (codigo, nombre, descripcion, precio, stock, id_categoria, imagen_url)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    try {
        $stmt->execute([
            $codigo,
            $nombre,
            trim($in['descripcion'] ?? ''),
            $precio,
            (int) ($in['stock'] ?? 0),
            !empty($in['id_categoria']) ? (int) $in['id_categoria'] : null,
            $imagen,
        ]);
    } catch (PDOException $e) {
        json_error('Ya existe un producto con ese codigo.', 409);
    }

    json_ok(['id' => (int) db()->lastInsertId()], 'Producto creado.', 201);
}

function api_productos_update(string $id): void
{
    require_api_admin();
    $in = body_json();
    $id = (int) $id;

    $stmt = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    $actual = $stmt->fetch();
    if (!$actual) {
        json_error('Producto no encontrado.', 404);
    }

    $imagen = guardar_imagen_producto() ?? ($in['imagen_url'] ?? $actual['imagen_url']);

    $stmt = db()->prepare(
        'UPDATE productos SET codigo = ?, nombre = ?, descripcion = ?, precio = ?,
                stock = ?, id_categoria = ?, imagen_url = ? WHERE id = ?'
    );
    $stmt->execute([
        trim($in['codigo'] ?? $actual['codigo']),
        trim($in['nombre'] ?? $actual['nombre']),
        trim($in['descripcion'] ?? $actual['descripcion']),
        isset($in['precio']) ? (float) $in['precio'] : $actual['precio'],
        isset($in['stock']) ? (int) $in['stock'] : $actual['stock'],
        !empty($in['id_categoria']) ? (int) $in['id_categoria'] : $actual['id_categoria'],
        $imagen,
        $id,
    ]);

    json_ok(null, 'Producto actualizado.');
}

/**
 * Baja logica del producto (activo = 0) para conservar el historial de ventas.
 */
function api_productos_delete(string $id): void
{
    require_api_admin();
    $stmt = db()->prepare('UPDATE productos SET activo = 0 WHERE id = ?');
    $stmt->execute([(int) $id]);
    if ($stmt->rowCount() === 0) {
        json_error('Producto no encontrado.', 404);
    }
    json_ok(null, 'Producto eliminado (baja logica).');
}

/**
 * Guarda la imagen enviada en $_FILES['imagen'] dentro de public/uploads.
 * Devuelve la URL relativa o null si no se envio ningun archivo.
 */
function guardar_imagen_producto(): ?string
{
    if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['imagen'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error('Error al subir la imagen.', 422);
    }

    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($file['tmp_name']) ?: '';
    if (!isset($permitidos[$mime])) {
        json_error('Formato de imagen no permitido (use JPG, PNG, WEBP o GIF).', 422);
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        json_error('La imagen supera el limite de 2 MB.', 422);
    }

    $dir = __DIR__ . '/../../public/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $nombre = 'prod_' . uniqid() . '.' . $permitidos[$mime];
    move_uploaded_file($file['tmp_name'], $dir . '/' . $nombre);

    return 'uploads/' . $nombre;
}
