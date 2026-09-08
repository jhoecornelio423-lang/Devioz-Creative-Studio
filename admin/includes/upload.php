<?php
/**
 * Devioz Creative Studio Admin - Helper de Subida de Archivos y Generación de Slugs
 * Procesa la carga segura de imágenes a la carpeta del frontend y genera slugs automáticamente.
 */

if (!function_exists('slugify')) {
    /**
     * Convierte una cadena de texto en un slug URL amigable y seguro.
     * @param string $text
     * @return string
     */
    function slugify($text) {
        $text = (string)$text;
        // Normalizar y remover acentos
        $transliterator = 'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()';
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        } else {
            $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            $text = strtolower($text);
        }
        // Reemplazar cualquier caracter no alfanumérico por guión
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = trim($text, '-');
        return !empty($text) ? $text : 'item-' . time();
    }
}

if (!function_exists('generateUniqueSlug')) {
    /**
     * Garantiza un slug único en la base de datos sin requerir intervención manual del admin.
     * Si ya existe, añade un sufijo numérico incremental (-2, -3, etc.).
     * @param PDO $pdo
     * @param string $table
     * @param string $text
     * @param int $excludeId
     * @param string $slugColumn
     * @return string
     */
    function generateUniqueSlug($pdo, $table, $text, $excludeId = 0, $slugColumn = 'slug') {
        $baseSlug = slugify($text);
        $slug = $baseSlug;
        $counter = 1;

        // Lista blanca estricta de tablas permitidas
        $allowedTables = ['proyectos', 'servicios', 'categorias'];
        if (!in_array($table, $allowedTables, true)) {
            return $slug;
        }

        while (true) {
            $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$slugColumn}` = :slug";
            $params = ['slug' => $slug];
            if ($excludeId > 0) {
                $sql .= " AND id != :id";
                $params['id'] = $excludeId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = (int)$stmt->fetchColumn();

            if ($count === 0) {
                return $slug;
            }

            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }
    }
}

if (!function_exists('handleImageUpload')) {
    /**
     * Procesa la carga de una imagen subida por el usuario desde el navegador.
     * @param string $fileInputName Nombre del campo en $_FILES
     * @param string $prefix Prefijo para el archivo generado (ej. 'proj', 'serv')
     * @param string $fallbackDefault Ruta de imagen por defecto si no se sube nada nuevo
     * @return array ['success' => bool, 'path' => string, 'error' => string|null]
     */
    function handleImageUpload($fileInputName, $prefix = 'img', $fallbackDefault = '') {
        // Si no se envió ningún archivo nuevo o no hubo carga
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => true,
                'path'    => $fallbackDefault,
                'error'   => null
            ];
        }

        $file = $_FILES[$fileInputName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'path'    => $fallbackDefault,
                'error'   => 'Error al cargar el archivo en el servidor (código: ' . $file['error'] . ').'
            ];
        }

        // Validación de tamaño máximo (10 MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'path'    => $fallbackDefault,
                'error'   => 'La imagen supera el límite permitido de 10 MB.'
            ];
        }

        // Validación de extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return [
                'success' => false,
                'path'    => $fallbackDefault,
                'error'   => 'Formato de imagen no permitido. Utiliza JPG, PNG, WEBP, SVG o GIF.'
            ];
        }

        // Validación de tipo MIME
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg', 'image/pjpeg',
                'image/png', 'image/x-png',
                'image/webp',
                'image/svg+xml', 'text/xml', 'text/plain', // Fallbacks para SVG
                'image/gif'
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                return [
                    'success' => false,
                    'path'    => $fallbackDefault,
                    'error'   => 'El tipo de contenido del archivo no es una imagen válida.'
                ];
            }
        }

        // Carpeta física destino en el frontend
        $targetDir = realpath(__DIR__ . '/../../frontend/assets/img');
        if (!$targetDir) {
            $targetDir = __DIR__ . '/../../frontend/assets/img';
        }
        $uploadsDir = $targetDir . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Generar nombre de archivo único
        $uniqueName = sprintf('%s_%s_%s.%s', $prefix, date('Ymd_His'), bin2hex(random_bytes(4)), $extension);
        $destination = $uploadsDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'path'    => $fallbackDefault,
                'error'   => 'No se pudo guardar la imagen en el directorio del servidor.'
            ];
        }

        // Ruta relativa normalizada para guardar en MySQL y leer desde el frontend
        $relativePath = 'assets/img/uploads/' . $uniqueName;

        return [
            'success' => true,
            'path'    => $relativePath,
            'error'   => null
        ];
    }
}
