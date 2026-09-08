<?php
/**
 * Devioz Creative Studio Admin - Gestión de Servicios (CRUD)
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';
require_once __DIR__ . '/includes/upload.php';

$pdo = getPDOConnection();

// PROCESAR ACCIONES POST (GUARDAR / TOGGLE / DELETE) CON PATRÓN PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $_SESSION['flash_error'] = 'Error de seguridad: Solicitud rechazada por token CSRF inválido o expirado. Por favor, recarga la página e intenta de nuevo.';
        header("Location: servicios.php");
        exit;
    }

    $postAction = sanitizeText($_POST['action'] ?? 'save');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($postAction === 'toggle' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE servicios SET estado = IF(estado=1, 0, 1) WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['flash_message'] = "El estado del servicio #{$id} fue modificado.";
            header("Location: servicios.php");
            exit;
        } elseif ($postAction === 'delete' && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['flash_message'] = "El servicio #{$id} fue eliminado correctamente.";
            header("Location: servicios.php");
            exit;
        } elseif ($postAction === 'save') {
            $titulo = sanitizeText($_POST['titulo'] ?? '');
            $slug = sanitizeText($_POST['slug'] ?? '');
            $descripcion = sanitizeText($_POST['descripcion'] ?? '');
            $estado = isset($_POST['estado']) ? 1 : 0;
            $categoriaId = (!empty($_POST['categoria_id']) && (int)$_POST['categoria_id'] > 0) ? (int)$_POST['categoria_id'] : null;

            // Procesar beneficios ingresados línea por línea de forma amigable
            $rawBeneficios = trim($_POST['beneficios'] ?? '');
            $beneficios = '';
            if (!empty($rawBeneficios)) {
                $lines = preg_split('/[\r\n\|]+/', $rawBeneficios);
                $cleanLines = [];
                foreach ($lines as $line) {
                    $trimmed = trim(sanitizeText($line));
                    if (!empty($trimmed)) {
                        $cleanLines[] = $trimmed;
                    }
                }
                $beneficios = implode('|', $cleanLines);
            }

            // Procesar subida de archivo de imagen con fallback
            $imagenActual = sanitizeText($_POST['imagen_actual'] ?? 'assets/img/services/diseno-grafico.svg');
            $uploadResult = handleImageUpload('imagen_archivo', 'serv', $imagenActual);

            $validationError = '';
            if (!$uploadResult['success']) {
                $validationError = $uploadResult['error'];
            } elseif (empty($titulo) || empty($descripcion)) {
                $validationError = 'El título y la descripción del servicio son obligatorios.';
            } elseif (!validateMaxLength($titulo, 150)) {
                $validationError = 'El título no puede superar los 150 caracteres.';
            } elseif (!validateMaxLength($descripcion, 5000)) {
                $validationError = 'La descripción es demasiado larga (máximo 5000 caracteres).';
            } elseif (!empty($beneficios) && !validateMaxLength($beneficios, 1000)) {
                $validationError = 'Los beneficios no pueden superar los 1000 caracteres en total.';
            } elseif (!isValidStatus($estado, [0, 1])) {
                $validationError = 'El estado proporcionado no es válido.';
            } else {
                $imagen = $uploadResult['path'];

                // Generación y garantía de unicidad automática del Slug
                if (empty($slug)) {
                    $slug = generateUniqueSlug($pdo, 'servicios', $titulo, $id);
                } else {
                    $slug = generateUniqueSlug($pdo, 'servicios', $slug, $id);
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE servicios 
                        SET categoria_id = :categoria_id, titulo = :titulo, slug = :slug, 
                            descripcion = :descripcion, imagen = :imagen, beneficios = :beneficios, estado = :estado 
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'categoria_id' => $categoriaId,
                        'titulo'       => $titulo,
                        'slug'         => $slug,
                        'descripcion'  => $descripcion,
                        'imagen'       => $imagen,
                        'beneficios'   => $beneficios,
                        'estado'       => $estado,
                        'id'           => $id
                    ]);
                    $_SESSION['flash_message'] = 'Servicio actualizado correctamente.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO servicios (categoria_id, titulo, slug, descripcion, imagen, beneficios, estado) 
                        VALUES (:categoria_id, :titulo, :slug, :descripcion, :imagen, :beneficios, :estado)
                    ");
                    $stmt->execute([
                        'categoria_id' => $categoriaId,
                        'titulo'       => $titulo,
                        'slug'         => $slug,
                        'descripcion'  => $descripcion,
                        'imagen'       => $imagen,
                        'beneficios'   => $beneficios,
                        'estado'       => $estado
                    ]);
                    $_SESSION['flash_message'] = 'Servicio creado correctamente.';
                }
                header("Location: servicios.php");
                exit;
            }

            if (!empty($validationError)) {
                $_SESSION['flash_error'] = $validationError;
                $_SESSION['form_data'] = $_POST;
                $redirectUrl = $id > 0 ? "servicios.php?action=edit&id={$id}" : "servicios.php?action=create";
                header("Location: {$redirectUrl}");
                exit;
            }
        }
        header("Location: servicios.php");
        exit;
    } catch (Exception $e) {
        error_log("[Devioz Admin Servicios Error] " . $e->getMessage());
        $_SESSION['flash_error'] = 'Ocurrió un error interno al procesar la solicitud del servicio.';
        header("Location: servicios.php");
        exit;
    }
}

// RECUPERAR FLASH MESSAGES (PRG)
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
$formData = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_data']);

$action = sanitizeText($_GET['action'] ?? 'list');
$editService = null;

// ACCIONES GET SEGURAS (ÚNICAMENTE LECTURA/VISTAS)
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editService = $stmt->fetch();
}

// Si hubo error de validación, restaurar datos previos del formulario
if ($formData) {
    if (!$editService) {
        $editService = [];
    }
    $editService['titulo'] = $formData['titulo'] ?? ($editService['titulo'] ?? '');
    $editService['slug'] = $formData['slug'] ?? ($editService['slug'] ?? '');
    $editService['descripcion'] = $formData['descripcion'] ?? ($editService['descripcion'] ?? '');
    $editService['imagen'] = $formData['imagen'] ?? ($editService['imagen'] ?? '');
    $editService['beneficios'] = $formData['beneficios'] ?? ($editService['beneficios'] ?? '');
    $editService['estado'] = isset($formData['estado']) ? 1 : 0;
}

// OBTENER LISTA DE SERVICIOS Y CATEGORÍAS
$stmtList = $pdo->query("
    SELECT s.*, c.nombre AS categoria_nombre 
    FROM servicios s 
    LEFT JOIN categorias c ON s.categoria_id = c.id 
    ORDER BY s.id ASC
");
$services = $stmtList->fetchAll();

$categories = $pdo->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY id ASC")->fetchAll();

$pageTitle = 'Gestión de Servicios';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-box">
  <h1 class="page-title">Gestión de Servicios</h1>
  <?php if ($action === 'list'): ?>
    <a href="servicios.php?action=create" class="btn-admin btn-admin-primary">+ Nuevo Servicio</a>
  <?php else: ?>
    <a href="servicios.php" class="btn-admin btn-admin-secondary">&larr; Volver a la Lista</a>
  <?php endif; ?>
</div>

<?php if (!empty($message)): ?>
  <div class="alert-admin alert-admin-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert-admin alert-admin-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
  <!-- FORMULARIO CREAR / EDITAR -->
  <div class="admin-card">
    <h2 style="font-family: var(--font-heading); margin-bottom: 1.5rem;">
      <?php echo $editService ? 'Editar Servicio #' . $editService['id'] : 'Crear Nuevo Servicio'; ?>
    </h2>

    <form method="POST" action="servicios.php" enctype="multipart/form-data">
      <?php csrfField(); ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editService): ?>
        <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group-admin full">
          <label for="titulo">Título del Servicio *</label>
          <input type="text" id="titulo" name="titulo" class="form-control-admin" maxlength="150" required 
                 placeholder="Ej. Diseño Gráfico Profesional"
                 value="<?php echo htmlspecialchars($editService['titulo'] ?? ''); ?>">
          <input type="hidden" id="slug" name="slug" value="<?php echo htmlspecialchars($editService['slug'] ?? ''); ?>">
          <small style="color: var(--devioz-gray); font-size: 0.82rem; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>🔗 Enlace web permanente (automático):</span>
            <span id="slug-preview" style="color: var(--devioz-primary); font-family: monospace; font-size: 0.85rem;">
              <?php echo htmlspecialchars($editService['slug'] ?? 'generado-al-escribir'); ?>
            </span>
          </small>
        </div>

        <div class="form-group-admin full">
          <label for="categoria_id">Categoría del Portafolio Vinculada</label>
          <select id="categoria_id" name="categoria_id" class="form-control-admin">
            <option value="">-- Sin categoría vinculada --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo (isset($editService['categoria_id']) && $editService['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small style="color: var(--devioz-gray); font-size: 0.8rem;">Relación formal con las categorías de la base de datos.</small>
        </div>

        <div class="form-group-admin full">
          <label for="imagen_archivo">Icono o Imagen Ilustrativa del Servicio</label>
          <div style="display: flex; gap: 1.25rem; align-items: center; background: rgba(0,0,0,0.25); border: 1px dashed var(--devioz-border); border-radius: 8px; padding: 1rem; flex-wrap: wrap;">
            <?php 
              $currentImg = $editService['imagen'] ?? 'assets/img/services/diseno-grafico.svg';
              $previewSrc = '../frontend/' . ltrim($currentImg, '/');
            ?>
            <div id="image-preview-box" style="width: 70px; height: 70px; border-radius: 8px; overflow: hidden; background: #001a1a; display: flex; align-items: center; justify-content: center; border: 1px solid var(--devioz-border); flex-shrink: 0; padding: 8px;">
              <img id="image-preview" src="<?php echo htmlspecialchars($previewSrc); ?>" alt="Icono" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.src='../frontend/assets/img/services/diseno-grafico.svg'">
            </div>
            <div style="flex-grow: 1; min-width: 240px;">
              <input type="file" id="imagen_archivo" name="imagen_archivo" accept="image/*" class="form-control-admin" style="padding: 0.5rem; background: var(--devioz-dark);">
              <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($currentImg); ?>">
              <small style="color: var(--devioz-gray); font-size: 0.8rem; display: block; margin-top: 0.35rem;">
                Selecciona una imagen o SVG desde tu equipo. Si no seleccionas una nueva, se mantendrá la actual.
              </small>
            </div>
          </div>
        </div>

        <div class="form-group-admin full">
          <label for="descripcion">Descripción *</label>
          <textarea id="descripcion" name="descripcion" class="form-control-admin" maxlength="5000" required rows="3" placeholder="Describe brevemente la propuesta de valor del servicio..."><?php echo htmlspecialchars($editService['descripcion'] ?? ''); ?></textarea>
        </div>

        <div class="form-group-admin full">
          <label for="beneficios">Beneficios Destacados (Un beneficio por línea)</label>
          <?php $beneficiosTxt = isset($editService['beneficios']) ? str_replace('|', "\n", $editService['beneficios']) : ''; ?>
          <textarea id="beneficios" name="beneficios" class="form-control-admin" rows="3" placeholder="Branding e Identidad Corporativa&#10;Piezas para Redes & Campañas&#10;Presentaciones de Alto Impacto"><?php echo htmlspecialchars($beneficiosTxt); ?></textarea>
          <small style="color: var(--devioz-gray); font-size: 0.8rem;">Escribe cada característica clave en una línea distinta (se mostrarán con checks en la web).</small>
        </div>

        <div class="form-group-admin full">
          <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="estado" value="1" <?php echo (!isset($editService) || $editService['estado'] == 1) ? 'checked' : ''; ?>>
            <span>Servicio Activo (Visible en la web pública)</span>
          </label>
        </div>
      </div>

      <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <button type="submit" class="btn-admin btn-admin-primary">Guardar Servicio</button>
        <a href="servicios.php" class="btn-admin btn-admin-secondary">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <!-- TABLA DE LISTADO -->
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Icono</th>
          <th>Título</th>
          <th>Categoría Vinculada</th>
          <th>Beneficios</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td style="width: 50px; text-align: center;">
              <img src="../frontend/<?php echo htmlspecialchars($s['imagen']); ?>" alt="Icono" style="width: 32px; height: 32px; object-fit: contain; display: inline-block;" onerror="this.src='../frontend/assets/img/services/diseno-grafico.svg'">
            </td>
            <td><strong><?php echo htmlspecialchars($s['titulo']); ?></strong><br><small style="color: var(--devioz-gray); font-family: monospace;"><?php echo htmlspecialchars($s['slug']); ?></small></td>
            <td>
              <?php if (!empty($s['categoria_nombre'])): ?>
                <span class="badge badge-info"><?php echo htmlspecialchars($s['categoria_nombre']); ?></span>
              <?php else: ?>
                <span style="color: var(--devioz-gray);">-</span>
              <?php endif; ?>
            </td>
            <td><code><?php echo htmlspecialchars($s['slug']); ?></code></td>
            <td><small><?php echo htmlspecialchars($s['beneficios']); ?></small></td>
            <td>
              <?php if ($s['estado'] == 1): ?>
                <span class="badge badge-success">Activo</span>
              <?php else: ?>
                <span class="badge badge-danger">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="table-actions">
                <a href="servicios.php?action=edit&id=<?php echo $s['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Editar</a>
                
                <!-- TOGGLE ESTADO (POST + CSRF) -->
                <form method="POST" action="servicios.php" class="inline-form">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" title="Alternar visibilidad">
                    <?php echo $s['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>
                  </button>
                </form>

                <!-- ELIMINAR (POST + CSRF + CONFIRM) -->
                <form method="POST" action="servicios.php" class="inline-form" onsubmit="return confirm('¿Está seguro de eliminar definitivamente este servicio?');">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" style="color: var(--devioz-danger);" title="Eliminar servicio">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
