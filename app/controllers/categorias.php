<?php
/**
 * Controlador de Categorias (API REST) - CRUD completo.
 */

declare(strict_types=1);

function api_categorias_list(): void
{
    require_api_login();
    $rows = db()->query(
        'SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.id_categoria = c.id) AS total_productos
         FROM categorias c ORDER BY c.nombre'
    )->fetchAll();
    json_ok($rows, 'Listado de categorias.');
}

function api_categorias_create(): void
{
    require_api_admin();
    $in = body_json();
    $nombre = trim($in['nombre'] ?? '');
    if ($nombre === '') {
        json_error('El nombre de la categoria es obligatorio.', 422);
    }
    $stmt = db()->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)');
    try {
        $stmt->execute([$nombre, trim($in['descripcion'] ?? '')]);
    } catch (PDOException $e) {
        json_error('Ya existe una categoria con ese nombre.', 409);
    }
    json_ok(['id' => (int) db()->lastInsertId()], 'Categoria creada.', 201);
}

function api_categorias_update(string $id): void
{
    require_api_admin();
    $in = body_json();
    $nombre = trim($in['nombre'] ?? '');
    if ($nombre === '') {
        json_error('El nombre de la categoria es obligatorio.', 422);
    }
    $stmt = db()->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?');
    $stmt->execute([$nombre, trim($in['descripcion'] ?? ''), (int) $id]);
    if ($stmt->rowCount() === 0) {
        // No cambio nada: verificar si existe
        $chk = db()->prepare('SELECT id FROM categorias WHERE id = ?');
        $chk->execute([(int) $id]);
        if (!$chk->fetch()) {
            json_error('Categoria no encontrada.', 404);
        }
    }
    json_ok(null, 'Categoria actualizada.');
}

function api_categorias_delete(string $id): void
{
    require_api_admin();
    $stmt = db()->prepare('DELETE FROM categorias WHERE id = ?');
    $stmt->execute([(int) $id]);
    if ($stmt->rowCount() === 0) {
        json_error('Categoria no encontrada.', 404);
    }
    json_ok(null, 'Categoria eliminada.');
}
