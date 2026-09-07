<?php
/**
 * Devioz Creative Studio - Endpoint API: Servicios (GET)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido. Use GET.', 405);
}

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, titulo, slug, descripcion, imagen, beneficios, estado 
        FROM servicios 
        WHERE estado = 1 
        ORDER BY id ASC
    ");
    $stmt->execute();
    $services = $stmt->fetchAll();

    // Transformar beneficios separados por pipe '|' a un array JSON
    foreach ($services as &$service) {
        if (!empty($service['beneficios'])) {
            $service['beneficios'] = array_map('trim', explode('|', $service['beneficios']));
        } else {
            $service['beneficios'] = [];
        }
    }

    successResponse($services, 'Servicios obtenidos correctamente', 200);

} catch (Exception $e) {
    error_log("[Devioz API Services Error] " . $e->getMessage());
    errorResponse('Error interno al consultar los servicios.', 500);
}
