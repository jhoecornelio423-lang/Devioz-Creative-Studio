<?php
/**
 * Devioz Creative Studio - Endpoint API: Portafolio de Proyectos (GET)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido. Use GET.', 405);
}

try {
    $pdo = getPDOConnection();
    
    $categoryFilter = isset($_GET['category']) ? sanitizeText($_GET['category']) : '';

    $sql = "
        SELECT 
            p.id, 
            p.categoria_id, 
            c.nombre AS categoria_nombre, 
            c.slug AS categoria_slug, 
            p.titulo, 
            p.slug, 
            p.descripcion, 
            p.imagen, 
            p.tipo, 
            p.cliente, 
            p.fecha, 
            p.destacado, 
            p.estado
        FROM proyectos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.estado = 1 AND c.estado = 1
    ";

    $params = [];

    // Filtrar opcionalmente por slug de categoría
    if (!empty($categoryFilter) && strtolower($categoryFilter) !== 'todos') {
        $sql .= " AND (c.slug = :cat_slug OR LOWER(c.nombre) = LOWER(:cat_name))";
        $params['cat_slug'] = $categoryFilter;
        $params['cat_name'] = $categoryFilter;
    }

    $sql .= " ORDER BY p.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    successResponse($projects, 'Proyectos del portafolio obtenidos correctamente', 200);

} catch (Exception $e) {
    error_log("[Devioz API Portfolio Error] " . $e->getMessage());
    errorResponse('Error interno al consultar el portafolio.', 500);
}
