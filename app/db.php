<?php
/**
 * Conexion a la base de datos usando PDO (patron singleton simple).
 * Usa consultas preparadas para prevenir inyeccion SQL.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        $respuesta = [
            'ok'      => false,
            'mensaje' => 'Error de conexion a la base de datos',
            'detalle' => $e->getMessage(),
        ];

        // Sin variables de entorno la app cae a 127.0.0.1, donde no hay ningun
        // MySQL: el error real es de configuracion, no de red. Se dice cual es
        // sin filtrar credenciales ni el host del servidor.
        if (empty($db['from_env'])) {
            $respuesta['causa'] = 'No hay variables de base de datos definidas; '
                . 'se uso la configuracion por defecto de desarrollo (127.0.0.1:3306).';
            $respuesta['solucion'] = 'Definir MYSQL_URL (o MYSQLHOST, MYSQLPORT, '
                . 'MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD) en el servicio web. '
                . 'Ver docs/DESPLIEGUE_RAILWAY.md, paso 3.';
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $pdo;
}
