<?php
/**
 * Controladores de las paginas web (renderizan vistas con Bootstrap).
 * La logica de datos se consume via la API REST desde el navegador (fetch),
 * salvo el login que se procesa del lado del servidor para mayor robustez.
 */

declare(strict_types=1);

function web_login_form(): void
{
    if (is_logged_in()) {
        header('Location: ' . base_url('/dashboard'));
        return;
    }
    view('auth/login', ['error' => $_GET['error'] ?? null], null);
}

function web_login_submit(): void
{
    start_session();
    $correo = trim($_POST['correo'] ?? '');
    $pass   = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM usuarios WHERE correo = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        header('Location: ' . base_url('/login?error=1'));
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'     => (int) $user['id'],
        'nombre' => $user['nombre'],
        'correo' => $user['correo'],
        'rol'    => $user['rol'],
    ];
    header('Location: ' . base_url('/dashboard'));
}

function web_logout(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
    header('Location: ' . base_url('/login'));
}

function web_dashboard(): void
{
    require_login();
    view('dashboard', ['titulo' => 'Dashboard']);
}

function web_productos(): void
{
    require_login();
    view('productos', ['titulo' => 'Inventario de Productos']);
}

function web_pos(): void
{
    require_login();
    view('pos', ['titulo' => 'Punto de Venta']);
}

function web_ventas(): void
{
    require_login();
    view('ventas', ['titulo' => 'Historial de Ventas']);
}

function web_venta_detalle(string $id): void
{
    require_login();
    $venta = obtener_venta_completa((int) $id);
    if (!$venta) {
        http_response_code(404);
        view('errors/404', [], 'layout/main');
        return;
    }
    // Vista de factura imprimible con codigo QR (API externa) - sin layout.
    view('factura', ['venta' => $venta], null);
}

function web_clientes(): void
{
    require_login();
    view('clientes', ['titulo' => 'Clientes']);
}

function web_reportes(): void
{
    require_login();
    view('reportes', ['titulo' => 'Reportes de Ventas']);
}

function web_usuarios(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        view('errors/403', [], 'layout/main');
        return;
    }
    // Listado simple de usuarios (renderizado en servidor).
    $usuarios = db()->query('SELECT id, nombre, correo, rol, activo, created_at FROM usuarios ORDER BY id')->fetchAll();
    view('usuarios', ['titulo' => 'Usuarios del Sistema', 'usuarios' => $usuarios]);
}
