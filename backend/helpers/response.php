<?php
/**
 * Devioz Creative Studio - Helper para Respuestas JSON Estandarizadas
 */

/**
 * Emite una respuesta JSON exitosa y finaliza la ejecución.
 * 
 * @param mixed $data Datos payload de la respuesta.
 * @param string $message Mensaje informativo.
 * @param int $status Código HTTP de estado (200 por defecto).
 */
function successResponse($data = [], $message = 'OK', $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Emite una respuesta JSON de error y finaliza la ejecución.
 * 
 * @param string $message Mensaje principal de error.
 * @param int $status Código HTTP de estado (400 por defecto).
 * @param array $errors Detalle de errores específicos.
 */
function errorResponse($message = 'Error', $status = 400, $errors = []) {
    http_response_code($status);
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
