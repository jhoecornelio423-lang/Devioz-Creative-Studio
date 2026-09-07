<?php
/**
 * Devioz Creative Studio - Helper para Sanitización y Validación de Datos
 */

/**
 * Sanitiza y limpia una cadena de texto.
 * 
 * @param string|null $data Texto de entrada.
 * @return string Texto limpio.
 */
function sanitizeText($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida si un email tiene un formato correcto.
 * 
 * @param string $email Correo a validar.
 * @return bool True si es válido, False en caso contrario.
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida la existencia y contenido no vacío de campos obligatorios.
 * 
 * @param array $data Arreglo asociativo con los datos enviados.
 * @param array $requiredFields Lista de nombres de campos requeridos.
 * @return array Arreglo con mensajes de error encontrados.
 */
function validateRequiredFields($data, $requiredFields) {
    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[$field] = "El campo '{$field}' es obligatorio.";
        }
    }
    return $errors;
}

/**
 * Valida si un slug tiene un formato URL-friendly válido.
 * Permite letras minúsculas, números y guiones sencillos.
 * 
 * @param string $slug
 * @param int $maxLength
 * @return bool
 */
function isValidSlug($slug, $maxLength = 180) {
    if (empty($slug) || mb_strlen($slug, 'UTF-8') > $maxLength) {
        return false;
    }
    return (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

/**
 * Valida la longitud máxima en caracteres UTF-8 de una cadena.
 * 
 * @param string $value
 * @param int $maxLength
 * @return bool
 */
function validateMaxLength($value, $maxLength) {
    return mb_strlen($value, 'UTF-8') <= $maxLength;
}

/**
 * Valida si una fecha cumple con un formato dado (por defecto Y-m-d) y es real.
 * 
 * @param string $dateStr
 * @param string $format
 * @return bool
 */
function isValidDate($dateStr, $format = 'Y-m-d') {
    if (empty($dateStr)) {
        return false;
    }
    $d = DateTime::createFromFormat($format, $dateStr);
    return $d && $d->format($format) === $dateStr;
}

/**
 * Valida si un estado está dentro de los permitidos.
 * 
 * @param mixed $status
 * @param array $allowed
 * @return bool
 */
function isValidStatus($status, array $allowed) {
    return in_array($status, $allowed, false);
}

/**
 * Valida si un ID de categoría existe en la base de datos.
 * 
 * @param PDO $pdo
 * @param int $categoryId
 * @return bool
 */
function categoryExists($pdo, $categoryId) {
    if ($categoryId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $categoryId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Valida si un número telefónico tiene un formato válido (exactamente 9 dígitos numéricos).
 * 
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    if (empty($phone)) {
        return false;
    }
    // Rechazar si contiene letras
    if (preg_match('/[a-zA-Z]/', $phone)) {
        return false;
    }
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) === 9;
}

