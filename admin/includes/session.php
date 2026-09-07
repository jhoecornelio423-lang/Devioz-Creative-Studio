<?php
/**
 * Devioz Creative Studio Admin - Manejador Seguro de Sesiones PHP
 */

if (!function_exists('initAdminSession')) {
    /**
     * Inicializa la sesión con una ruta de almacenamiento propia del proyecto,
     * garantizando permisos de escritura y configurando cookies seguras.
     */
    function initAdminSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // 1. Crear directorios de almacenamiento si no existen (sin silenciadores ciegos)
        $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
        $sessionsDirRaw = $storageDir . DIRECTORY_SEPARATOR . 'sessions';

        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
                error_log("[Devioz Session] Error al crear directorio storage: " . $storageDir);
            }
        }

        if (!is_dir($sessionsDirRaw)) {
            if (!mkdir($sessionsDirRaw, 0777, true) && !is_dir($sessionsDirRaw)) {
                error_log("[Devioz Session] Error al crear directorio sessions: " . $sessionsDirRaw);
            }
        }

        // Proteger storage contra acceso web directo en Apache/XAMPP
        $htaccess = $storageDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            $writtenHt = file_put_contents($htaccess, "Require all denied\n");
            if ($writtenHt === false) {
                error_log("[Devioz Session] Error al escribir .htaccess en: " . $storageDir);
            }
        }

        // 2. Resolver ruta absoluta real con realpath
        $sessionsDir = realpath($sessionsDirRaw);

        // 3. Probar escritura real en admin/storage/sessions antes de session_start()
        $canWrite = false;
        if ($sessionsDir && is_dir($sessionsDir)) {
            $testFile = $sessionsDir . DIRECTORY_SEPARATOR . 'test_perm_' . uniqid() . '.tmp';
            $bytesWritten = file_put_contents($testFile, 'test');
            if ($bytesWritten !== false) {
                $canWrite = true;
                unlink($testFile);
            } else {
                error_log("[Devioz Session] Prueba de escritura falló en: " . $sessionsDir);
            }
        }

        $finalDir = '';
        if ($canWrite && $sessionsDir) {
            $finalDir = $sessionsDir;
        } else {
            // Fallback a sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devioz_sessions'
            $fallbackRaw = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devioz_sessions';
            if (!is_dir($fallbackRaw)) {
                if (!mkdir($fallbackRaw, 0777, true) && !is_dir($fallbackRaw)) {
                    error_log("[Devioz Session] Error al crear directorio fallback: " . $fallbackRaw);
                }
            }
            $finalDir = realpath($fallbackRaw) ?: $fallbackRaw;

            // Probar escritura en fallback
            $fbTestFile = $finalDir . DIRECTORY_SEPARATOR . 'test_perm_' . uniqid() . '.tmp';
            $fbBytes = file_put_contents($fbTestFile, 'test');
            if ($fbBytes !== false) {
                unlink($fbTestFile);
            } else {
                error_log("[Devioz Session] Prueba de escritura falló en fallback: " . $finalDir);
            }
        }

        // 4. Forzar la ruta con AMBAS formas antes de session_start()
        session_save_path($finalDir);
        ini_set('session.save_path', $finalDir);

        // 5. Verificar inmediatamente que session_save_path() coincida
        if (session_save_path() !== $finalDir) {
            error_log("[Devioz Session] session_save_path() no coincide: " . session_save_path() . " !== " . $finalDir);
            ini_set('session.save_path', $finalDir);
            session_save_path($finalDir);
        }

        // 6. Parámetros de seguridad de cookies de sesión
        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // 7. Iniciar sesión de forma limpia sin warnings en HTML
        session_start();
    }
}

if (!function_exists('destroyAdminSession')) {
    /**
     * Destruye la sesión de forma completa y segura, invalidando cookies.
     */
    function destroyAdminSession() {
        if (session_status() === PHP_SESSION_NONE) {
            initAdminSession();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}

// Inicializar sesión inmediatamente al incluir este archivo
initAdminSession();
