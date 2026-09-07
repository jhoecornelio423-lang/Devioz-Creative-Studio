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
        SELECT 
            s.id, 
            s.categoria_id, 
            c.nombre AS categoria_nombre, 
            c.slug AS categoria_slug, 
            s.titulo, 
            s.slug, 
            s.descripcion, 
            s.imagen, 
            s.beneficios, 
            s.estado 
        FROM servicios s
        LEFT JOIN categorias c ON s.categoria_id = c.id
        WHERE s.estado = 1 
        ORDER BY s.id ASC
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
