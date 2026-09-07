<?php
/**
 * Devioz Creative Studio - Endpoint API: Formulario de Contacto (POST)
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido. Use POST.', 405);
}

// Obtener payload JSON o POST tradicional
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if (!is_array($inputData)) {
    $inputData = $_POST;
}

// Sanitizar datos recibidos
$nombre = sanitizeText($inputData['nombre'] ?? '');
$empresa = sanitizeText($inputData['empresa'] ?? '');
$email = sanitizeText($inputData['email'] ?? '');
$telefono = sanitizeText($inputData['telefono'] ?? '');
$servicioInteres = sanitizeText($inputData['servicio_interes'] ?? '');
$mensaje = sanitizeText($inputData['mensaje'] ?? '');

// Validaciones
$errors = [];

if (empty($nombre)) {
    $errors['nombre'] = 'El nombre es obligatorio.';
} elseif (mb_strlen($nombre, 'UTF-8') > 120) {
    $errors['nombre'] = 'El nombre no puede exceder los 120 caracteres.';
}

if (!empty($empresa) && mb_strlen($empresa, 'UTF-8') > 150) {
    $errors['empresa'] = 'El nombre de empresa no puede exceder los 150 caracteres.';
}

if (empty($email)) {
    $errors['email'] = 'El correo electrónico es obligatorio.';
} elseif (mb_strlen($email, 'UTF-8') > 150) {
    $errors['email'] = 'El correo electrónico no puede exceder los 150 caracteres.';
} elseif (!isValidEmail($email)) {
    $errors['email'] = 'El formato de correo electrónico no es válido.';
}

if (!empty($telefono)) {
    $digits = preg_replace('/\D/', '', $telefono);
    if (strlen($digits) > 9) {
        $errors['telefono'] = 'El número de teléfono no puede tener más de 9 dígitos.';
    } elseif (!isValidPhone($telefono)) {
        $errors['telefono'] = 'El teléfono no es válido. Debe contener exactamente 9 dígitos numéricos (ej. 987654321).';
    }
}

if (empty($servicioInteres)) {
    $errors['servicio_interes'] = 'El servicio de interés es obligatorio.';
} elseif (mb_strlen($servicioInteres, 'UTF-8') > 150) {
    $errors['servicio_interes'] = 'El servicio de interés no puede exceder los 150 caracteres.';
}

if (empty($mensaje)) {
    $errors['mensaje'] = 'El mensaje no puede estar vacío.';
} elseif (mb_strlen($mensaje, 'UTF-8') > 3000) {
    $errors['mensaje'] = 'El mensaje no puede exceder los 3000 caracteres.';
}

if (!empty($errors)) {
    errorResponse('Error de validación en la solicitud.', 422, $errors);
}

try {
    $pdo = getPDOConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO contactos (nombre, empresa, email, telefono, servicio_interes, mensaje, estado)
        VALUES (:nombre, :empresa, :email, :telefono, :servicio_interes, :mensaje, 'nuevo')
    ");

    $stmt->execute([
        'nombre'           => $nombre,
        'empresa'          => $empresa,
        'email'            => $email,
        'telefono'         => $telefono,
        'servicio_interes' => $servicioInteres,
        'mensaje'          => $mensaje
    ]);

    $insertedId = $pdo->lastInsertId();

    successResponse([
        'id' => (int)$insertedId
    ], 'Solicitud registrada correctamente', 201);

} catch (Exception $e) {
    error_log("[Devioz API Contact Error] " . $e->getMessage());
    errorResponse('Error interno al procesar la solicitud de contacto.', 500);
}
