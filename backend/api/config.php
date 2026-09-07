<?php
/**
 * Devioz Creative Studio - Endpoint API: Configuración Global (GET)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido. Use GET.', 405);
}

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Transformar lista de filas a un objeto asociativo clave => valor
    $config = [];
    foreach ($rows as $row) {
        $config[$row['clave']] = $row['valor'];
    }

    successResponse($config, 'Configuración obtenida correctamente', 200);

} catch (Exception $e) {
    error_log("[Devioz API Config Error] " . $e->getMessage());
    errorResponse('Error interno al consultar la configuración.', 500);
}
