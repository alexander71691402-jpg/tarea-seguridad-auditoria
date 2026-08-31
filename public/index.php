<?php
/**
 * Front Controller / Router
 * -------------------------------------------------------------
 * Punto de entrada unico de la aplicacion. Todas las peticiones
 * pasan por aqui (ver .htaccess). Despacha:
 *   - Rutas web   -> paginas renderizadas con Bootstrap
 *   - Rutas /api  -> API REST propia (respuestas JSON)
 */

declare(strict_types=1);

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/helpers.php';

foreach (glob(__DIR__ . '/../app/controllers/*.php') as $controller) {
    require $controller;
}

/* ------------------------------------------------------------------ */
/*  Calculo de la ruta solicitada (relativa al directorio base)        */
/* ------------------------------------------------------------------ */
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$uriPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if ($scriptDir !== '' && strpos($uriPath, $scriptDir) === 0) {
    $uriPath = substr($uriPath, strlen($scriptDir));
}
$path   = '/' . trim($uriPath, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Method override: permite enviar PUT/DELETE desde un POST multipart
// (necesario para subir imagenes, ya que PHP solo llena $_FILES en POST).
if ($method === 'POST') {
    $override = $_POST['_method'] ?? ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '');
    $override = strtoupper((string) $override);
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

/* ------------------------------------------------------------------ */
/*  Mini enrutador                                                     */
/* ------------------------------------------------------------------ */
$routes = [];

function route(string $method, string $pattern, callable $handler): void
{
    global $routes;
    $routes[] = [strtoupper($method), $pattern, $handler];
}

/**
 * Intenta hacer coincidir la ruta actual. Convierte {param} en grupos
 * y pasa los valores capturados al handler.
 */
function dispatch(string $method, string $path): void
{
    global $routes;
    $matchedPathButNotMethod = false;

    foreach ($routes as [$rMethod, $pattern, $handler]) {
        $regex = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern) . '$#';
        if (preg_match($regex, $path, $m)) {
            if ($rMethod !== $method) {
                $matchedPathButNotMethod = true;
                continue;
            }
            array_shift($m);
            call_user_func_array($handler, $m);
            return;
        }
    }

    // No hubo coincidencia
    if (strpos($path, '/api') === 0) {
        json_error($matchedPathButNotMethod ? 'Metodo no permitido' : 'Endpoint no encontrado',
                   $matchedPathButNotMethod ? 405 : 404);
    }

    http_response_code(404);
    view('errors/404', [], 'layout/main');
}

/* ================================================================== */
/*  RUTAS WEB (paginas)                                                */
/* ================================================================== */
route('GET',  '/',           fn() => header('Location: ' . base_url('/dashboard')));
route('GET',  '/login',      'web_login_form');
route('POST', '/login',      'web_login_submit');
route('GET',  '/logout',     'web_logout');
route('GET',  '/dashboard',  'web_dashboard');
route('GET',  '/productos',  'web_productos');
route('GET',  '/pos',        'web_pos');
route('GET',  '/ventas',     'web_ventas');
route('GET',  '/ventas/{id}','web_venta_detalle');   // factura imprimible
route('GET',  '/clientes',   'web_clientes');
route('GET',  '/reportes',   'web_reportes');
route('GET',  '/usuarios',   'web_usuarios');         // solo admin

/* ================================================================== */
/*  API REST PROPIA  (todas responden JSON)                            */
/* ================================================================== */

// --- Diagnostico de despliegue (solo nombres de variables, sin valores) ---
route('GET',  '/api/diag/env',      'api_diag_env');

// --- Autenticacion ---
route('POST', '/api/auth/login',    'api_auth_login');
route('POST', '/api/auth/register', 'api_auth_register');
route('POST', '/api/auth/logout',   'api_auth_logout');
route('GET',  '/api/auth/me',       'api_auth_me');

// --- Categorias ---
route('GET',    '/api/categorias',      'api_categorias_list');
route('POST',   '/api/categorias',      'api_categorias_create');
route('PUT',    '/api/categorias/{id}', 'api_categorias_update');
route('DELETE', '/api/categorias/{id}', 'api_categorias_delete');

// --- Productos (inventario) ---
route('GET',    '/api/productos',      'api_productos_list');
route('GET',    '/api/productos/{id}', 'api_productos_get');
route('POST',   '/api/productos',      'api_productos_create');
route('PUT',    '/api/productos/{id}', 'api_productos_update');
route('DELETE', '/api/productos/{id}', 'api_productos_delete');

// --- Clientes ---
route('GET',    '/api/clientes',      'api_clientes_list');
route('POST',   '/api/clientes',      'api_clientes_create');
route('PUT',    '/api/clientes/{id}', 'api_clientes_update');
route('DELETE', '/api/clientes/{id}', 'api_clientes_delete');

// --- Ventas (POS) ---
route('GET',  '/api/ventas',      'api_ventas_list');
route('GET',  '/api/ventas/{id}', 'api_ventas_get');
route('POST', '/api/ventas',      'api_ventas_create');
route('POST', '/api/ventas/{id}/anular', 'api_ventas_anular');

// --- Reportes ---
route('GET', '/api/reportes/dashboard',           'api_reportes_dashboard');
route('GET', '/api/reportes/ventas',              'api_reportes_ventas');
route('GET', '/api/reportes/productos-vendidos',  'api_reportes_productos_vendidos');

/* ------------------------------------------------------------------ */
dispatch($method, $path);
