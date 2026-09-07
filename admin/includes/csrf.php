<?php
/**
 * Devioz Creative Studio Admin - Helper de Seguridad y Protección CSRF
 */

require_once __DIR__ . '/session.php';
initAdminSession();

if (!function_exists('getCsrfToken')) {
    /**
     * Obtiene el token CSRF activo o genera uno nuevo si no existe.
     * @return string
     */
    function getCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    /**
     * Genera el campo HTML input oculto con el token CSRF.
     * @param bool $echo Si es true imprime en pantalla directamente.
     * @return string
     */
    function csrfField($echo = true) {
        $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
        $html = '<input type="hidden" name="csrf_token" value="' . $token . '">';
        if ($echo) {
            echo $html;
        }
        return $html;
    }
}

if (!function_exists('getCsrfField')) {
    /**
     * Alias de retorno de cadena para csrfField(false).
     * @return string
     */
    function getCsrfField() {
        return csrfField(false);
    }
}

if (!function_exists('validateCsrfToken')) {
    /**
     * Valida si el token CSRF recibido por POST coincide con el de la sesión.
     * @param string|null $token
     * @return bool
     */
    function validateCsrfToken($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? '';
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}
