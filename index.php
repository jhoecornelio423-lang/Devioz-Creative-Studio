<?php
/**
 * Devioz Creative Studio - Front Controller / Router
 */

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($requestUri, '/');

// Raíz o index.php -> redirigir a /inicio
if ($path === '' || $path === 'index.php') {
    header('Location: /inicio');
    exit;
}

// Redireccionar contacto a cotización
if ($path === 'contacto') {
    header('Location: /cotizacion', true, 301);
    exit;
}

// Rutas amigables del sistema
if ($path === 'inicio') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/frontend/index.html');
    exit;
}

if ($path === 'portafolio') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/frontend/portafolio.html');
    exit;
}

if ($path === 'cotizacion') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/frontend/cotizacion.html');
    exit;
}

// Redireccionar planes a preguntas
if ($path === 'planes') {
    header('Location: /preguntas', true, 301);
    exit;
}

if ($path === 'preguntas') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/frontend/preguntas.html');
    exit;
}

if ($path === 'blog') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/frontend/blog.html');
    exit;
}

// Soporte para assets solicitados directamente como /assets/...
if (str_starts_with($path, 'assets/')) {
    $filePath = __DIR__ . '/frontend/' . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'json'  => 'application/json'
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($filePath);
        exit;
    }
}

// Rutas no reconocidas: fallback a /inicio
header('Location: /inicio');
exit;

