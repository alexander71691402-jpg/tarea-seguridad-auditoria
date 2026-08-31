<?php
/**
 * Diagnostico de despliegue.
 *
 * Responde SOLO con nombres de variables y booleanos: nunca con sus valores,
 * para que sea seguro consultarlo en una URL publica. Sirve para distinguir
 * "la variable no llega al contenedor" de "la variable llega pero es incorrecta".
 */

declare(strict_types=1);

function api_diag_env(): void
{
    $candidatas = [
        'MYSQL_URL', 'DATABASE_URL', 'MYSQLHOST', 'MYSQLPORT',
        'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD',
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
        'PORT', 'RAILWAY_ENVIRONMENT_NAME', 'RAILWAY_SERVICE_NAME',
    ];

    $visibles = [];
    foreach ($candidatas as $nombre) {
        $enGetenv = getenv($nombre);
        $visibles[$nombre] = [
            'getenv'  => $enGetenv !== false && $enGetenv !== '',
            'env'     => isset($_ENV[$nombre]) && $_ENV[$nombre] !== '',
            'server'  => isset($_SERVER[$nombre]) && $_SERVER[$nombre] !== '',
        ];
    }

    // Nombres (no valores) de todo lo que empiece por MYSQL/DB/RAILWAY, para
    // detectar que la variable existe pero con otro nombre del esperado.
    $relacionadas = [];
    foreach (array_keys(getenv()) as $nombre) {
        if (preg_match('/^(MYSQL|DB_|DATABASE|RAILWAY)/i', (string) $nombre)) {
            $relacionadas[] = $nombre;
        }
    }
    sort($relacionadas);

    json_response([
        'ok'                     => true,
        'mensaje'                => 'Diagnostico de variables de entorno (sin valores).',
        'data'                   => [
            'variables_buscadas'     => $visibles,
            'nombres_relacionados'   => $relacionadas,
            'total_env_visibles'     => count(getenv()),
            'sapi'                   => PHP_SAPI,
            'variables_order'        => ini_get('variables_order'),
        ],
    ]);
}
