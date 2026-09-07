<?php
/**
 * Devioz Creative Studio - Configuración de Conexión a Base de Datos (PDO)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'devioz_creative_studio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtiene la instancia de conexión PDO reutilizable.
 * @return PDO
 */
function getPDOConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("[Devioz DB Error] " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error interno: No se pudo establecer conexión con la base de datos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    return $pdo;
}
