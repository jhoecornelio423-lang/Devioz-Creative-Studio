<?php
/**
 * Devioz Creative Studio - Endpoint API: Categorías (GET)
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
        SELECT id, nombre, slug, estado 
        FROM categorias 
        WHERE estado = 1 
        ORDER BY id ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    successResponse($categories, 'Categorías obtenidas correctamente', 200);

} catch (Exception $e) {
    error_log("[Devioz API Categories Error] " . $e->getMessage());
    errorResponse('Error interno al consultar las categorías.', 500);
}
