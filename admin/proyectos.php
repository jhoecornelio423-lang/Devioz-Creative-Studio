<?php
/**
 * Devioz Creative Studio Admin - Gestión de Proyectos del Portafolio (CRUD)
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';
require_once __DIR__ . '/includes/upload.php';

$pdo = getPDOConnection();

// PROCESAR ACCIONES POST (GUARDAR / TOGGLE / TOGGLE_FEATURED / DELETE) CON PATRÓN PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $_SESSION['flash_error'] = 'Error de seguridad: Solicitud rechazada por token CSRF inválido o expirado. Por favor, recarga la página e intenta de nuevo.';
        header("Location: proyectos.php");
        exit;
    }

    $postAction = sanitizeText($_POST['action'] ?? 'save');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($postAction === 'toggle' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE proyectos SET estado = IF(estado=1, 0, 1) WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['flash_message'] = "El estado del proyecto #{$id} fue modificado.";
            header("Location: proyectos.php");
            exit;
        } elseif ($postAction === 'toggle_featured' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE proyectos SET destacado = IF(destacado=1, 0, 1) WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['flash_message'] = "La condición de destacado del proyecto #{$id} fue actualizada.";
            header("Location: proyectos.php");
            exit;
        } elseif ($postAction === 'delete' && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM proyectos WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['flash_message'] = "El proyecto #{$id} fue eliminado correctamente.";
            header("Location: proyectos.php");
            exit;
        } elseif ($postAction === 'save') {
            $categoriaId = (int)($_POST['categoria_id'] ?? 0);
            $titulo = sanitizeText($_POST['titulo'] ?? '');
            $slug = sanitizeText($_POST['slug'] ?? '');
            $descripcion = sanitizeText($_POST['descripcion'] ?? '');
            $tipo = sanitizeText($_POST['tipo'] ?? '');
            $cliente = sanitizeText($_POST['cliente'] ?? '');
            $fecha = !empty($_POST['fecha']) ? trim($_POST['fecha']) : null;
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            $estado = isset($_POST['estado']) ? 1 : 0;

            // Procesar subida de archivo de imagen con fallback a la existente
            $imagenActual = sanitizeText($_POST['imagen_actual'] ?? 'assets/img/portfolio/project-1.svg');
            $uploadResult = handleImageUpload('imagen_archivo', 'proj', $imagenActual);

            $validationError = '';
            if (!$uploadResult['success']) {
                $validationError = $uploadResult['error'];
            } elseif (empty($titulo) || $categoriaId <= 0 || empty($descripcion)) {
                $validationError = 'El título, la categoría y la descripción del proyecto son obligatorios.';
            } elseif (!validateMaxLength($titulo, 150)) {
                $validationError = 'El título no puede superar los 150 caracteres.';
            } elseif (!categoryExists($pdo, $categoriaId)) {
                $validationError = 'La categoría seleccionada no existe en el sistema. Por favor, selecciona una categoría válida.';
            } elseif (!validateMaxLength($descripcion, 5000)) {
                $validationError = 'La descripción no puede superar los 5000 caracteres.';
            } elseif (!empty($tipo) && !validateMaxLength($tipo, 80)) {
                $validationError = 'El tipo de entregable no puede superar los 80 caracteres.';
            } elseif (!empty($cliente) && !validateMaxLength($cliente, 120)) {
                $validationError = 'El nombre del cliente no puede superar los 120 caracteres.';
            } elseif (!empty($fecha) && !isValidDate($fecha, 'Y-m-d')) {
                $validationError = 'La fecha ingresada no tiene un formato válido (debe ser AAAA-MM-DD).';
            } elseif (!isValidStatus($destacado, [0, 1]) || !isValidStatus($estado, [0, 1])) {
                $validationError = 'Los valores de estado o destacado no son válidos.';
            } else {
                $imagen = $uploadResult['path'];

                // Generación y garantía de unicidad automática del Slug (sin pedirle al admin que lo invente)
                if (empty($slug)) {
                    $slug = generateUniqueSlug($pdo, 'proyectos', $titulo, $id);
                } else {
                    $slug = generateUniqueSlug($pdo, 'proyectos', $slug, $id);
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE proyectos 
                        SET categoria_id = :cat_id, titulo = :titulo, slug = :slug, 
                            descripcion = :descripcion, imagen = :imagen, tipo = :tipo, 
                            cliente = :cliente, fecha = :fecha, destacado = :destacado, estado = :estado
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'cat_id'      => $categoriaId,
                        'titulo'      => $titulo,
                        'slug'        => $slug,
                        'descripcion' => $descripcion,
                        'imagen'      => $imagen,
                        'tipo'        => $tipo,
                        'cliente'     => $cliente,
                        'fecha'       => $fecha,
                        'destacado'   => $destacado,
                        'estado'      => $estado,
                        'id'          => $id
                    ]);
                    $_SESSION['flash_message'] = 'Proyecto actualizado correctamente.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO proyectos (categoria_id, titulo, slug, descripcion, imagen, tipo, cliente, fecha, destacado, estado)
                        VALUES (:cat_id, :titulo, :slug, :descripcion, :imagen, :tipo, :cliente, :fecha, :destacado, :estado)
                    ");
                    $stmt->execute([
                        'cat_id'      => $categoriaId,
                        'titulo'      => $titulo,
                        'slug'        => $slug,
                        'descripcion' => $descripcion,
                        'imagen'      => $imagen,
                        'tipo'        => $tipo,
                        'cliente'     => $cliente,
                        'fecha'       => $fecha,
                        'destacado'   => $destacado,
                        'estado'      => $estado
                    ]);
                    $_SESSION['flash_message'] = 'Proyecto creado correctamente.';
                }
                header("Location: proyectos.php");
                exit;
            }

            if (!empty($validationError)) {
                $_SESSION['flash_error'] = $validationError;
                $_SESSION['form_data'] = $_POST;
                $redirectUrl = $id > 0 ? "proyectos.php?action=edit&id={$id}" : "proyectos.php?action=create";
                header("Location: {$redirectUrl}");
                exit;
            }
        }
        header("Location: proyectos.php");
        exit;
    } catch (Exception $e) {
        error_log("[Devioz Admin Proyectos Error] " . $e->getMessage());
        $_SESSION['flash_error'] = 'Ocurrió un error interno al procesar la solicitud del proyecto.';
        header("Location: proyectos.php");
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
$editProject = null;

// ACCIONES GET SEGURAS (CARGAR VISTA DE EDICIÓN)
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editProject = $stmt->fetch();
}

if ($formData) {
    if (!$editProject) {
        $editProject = [];
    }
    $editProject['categoria_id'] = (int)($formData['categoria_id'] ?? ($editProject['categoria_id'] ?? 0));
    $editProject['titulo'] = $formData['titulo'] ?? ($editProject['titulo'] ?? '');
    $editProject['slug'] = $formData['slug'] ?? ($editProject['slug'] ?? '');
    $editProject['descripcion'] = $formData['descripcion'] ?? ($editProject['descripcion'] ?? '');
    $editProject['imagen'] = $formData['imagen'] ?? ($editProject['imagen'] ?? '');
    $editProject['tipo'] = $formData['tipo'] ?? ($editProject['tipo'] ?? '');
    $editProject['cliente'] = $formData['cliente'] ?? ($editProject['cliente'] ?? '');
    $editProject['fecha'] = $formData['fecha'] ?? ($editProject['fecha'] ?? '');
    $editProject['destacado'] = isset($formData['destacado']) ? 1 : 0;
    $editProject['estado'] = isset($formData['estado']) ? 1 : 0;
}

// OBTENER LISTA DE CATEGORÍAS (CON SU ESTADO)
$categories = $pdo->query("SELECT id, nombre, estado FROM categorias ORDER BY nombre ASC")->fetchAll();

// OBTENER LISTA DE PROYECTOS CON JOIN A CATEGORÍAS
$stmtList = $pdo->query("
    SELECT p.*, c.nombre AS categoria_nombre, c.estado AS categoria_estado 
    FROM proyectos p 
    INNER JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.id ASC
");
$projects = $stmtList->fetchAll();

$pageTitle = 'Gestión de Proyectos';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-box">
  <h1 class="page-title">Gestión de Proyectos</h1>
  <?php if ($action === 'list'): ?>
    <a href="proyectos.php?action=create" class="btn-admin btn-admin-primary">+ Nuevo Proyecto</a>
  <?php else: ?>
    <a href="proyectos.php" class="btn-admin btn-admin-secondary">&larr; Volver a la Lista</a>
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
      <?php echo $editProject ? 'Editar Proyecto #' . $editProject['id'] : 'Crear Nuevo Proyecto'; ?>
    </h2>

    <form method="POST" action="proyectos.php" enctype="multipart/form-data">
      <?php csrfField(); ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editProject): ?>
        <input type="hidden" name="id" value="<?php echo $editProject['id']; ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group-admin full">
          <label for="titulo">Título del Proyecto *</label>
          <input type="text" id="titulo" name="titulo" class="form-control-admin" maxlength="150" required 
                 placeholder="Ej. Campaña Visual para Redes Sociales" 
                 value="<?php echo htmlspecialchars($editProject['titulo'] ?? ''); ?>">
          <input type="hidden" id="slug" name="slug" value="<?php echo htmlspecialchars($editProject['slug'] ?? ''); ?>">
          <small style="color: var(--devioz-gray); font-size: 0.82rem; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>🔗 Enlace web permanente (automático):</span>
            <span id="slug-preview" style="color: var(--devioz-primary); font-family: monospace; font-size: 0.85rem;">
              <?php echo htmlspecialchars($editProject['slug'] ?? 'generado-al-escribir'); ?>
            </span>
          </small>
        </div>

        <div class="form-group-admin">
          <label for="categoria_id">Categoría *</label>
          <select id="categoria_id" name="categoria_id" class="form-control-admin" required>
            <option value="" disabled <?php echo empty($editProject) ? 'selected' : ''; ?>>-- Seleccionar Categoría --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo (isset($editProject) && $editProject['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['nombre']); ?><?php echo $cat['estado'] == 0 ? ' [Inactiva]' : ''; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group-admin">
          <label for="tipo">Tipo de Entregable</label>
          <input type="text" id="tipo" name="tipo" class="form-control-admin" maxlength="80" placeholder="Ej. Diseño Gráfico, Spot, Video" value="<?php echo htmlspecialchars($editProject['tipo'] ?? ''); ?>">
        </div>

        <div class="form-group-admin">
          <label for="cliente">Cliente / Marca</label>
          <input type="text" id="cliente" name="cliente" class="form-control-admin" maxlength="120" placeholder="Ej. Devioz Corp" value="<?php echo htmlspecialchars($editProject['cliente'] ?? ''); ?>">
        </div>

        <div class="form-group-admin">
          <label for="fecha">Fecha de Realización</label>
          <input type="date" id="fecha" name="fecha" class="form-control-admin" value="<?php echo htmlspecialchars($editProject['fecha'] ?? ''); ?>">
        </div>

        <div class="form-group-admin full">
          <label for="imagen_archivo">Fotografía o Imagen del Proyecto</label>
          <div style="display: flex; gap: 1.25rem; align-items: center; background: rgba(0,0,0,0.25); border: 1px dashed var(--devioz-border); border-radius: 8px; padding: 1rem; flex-wrap: wrap;">
            <?php 
              $currentImg = !empty($editProject['imagen']) ? $editProject['imagen'] : 'assets/img/portfolio/project-1.svg';
              $previewSrc = '../frontend/' . ltrim($currentImg, '/');
            ?>
            <div id="image-preview-box" style="width: 120px; height: 80px; border-radius: 6px; overflow: hidden; background: #001a1a; display: flex; align-items: center; justify-content: center; border: 1px solid var(--devioz-border); flex-shrink: 0;">
              <img id="image-preview" src="<?php echo htmlspecialchars($previewSrc); ?>" alt="Vista previa" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='../frontend/assets/img/portfolio/project-1.svg';">
            </div>
            <div style="flex-grow: 1; min-width: 240px;">
              <input type="file" id="imagen_archivo" name="imagen_archivo" accept="image/*" class="form-control-admin" style="padding: 0.5rem; background: var(--devioz-dark);">
              <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($currentImg); ?>">
              <small style="color: var(--devioz-gray); font-size: 0.8rem; display: block; margin-top: 0.35rem;">
                Selecciona una imagen desde tu equipo (JPG, PNG, WEBP, SVG). Si no seleccionas una nueva, se conservará la imagen actual.
              </small>
            </div>
          </div>
        </div>

        <div class="form-group-admin full">
          <label for="descripcion">Descripción del Proyecto *</label>
          <textarea id="descripcion" name="descripcion" class="form-control-admin" maxlength="5000" required rows="4" placeholder="Describe brevemente el alcance y objetivos del proyecto..."><?php echo htmlspecialchars($editProject['descripcion'] ?? ''); ?></textarea>
        </div>

        <div class="form-group-admin">
          <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="destacado" value="1" <?php echo (isset($editProject) && $editProject['destacado'] == 1) ? 'checked' : ''; ?>>
            <span>Destacar Proyecto en Inicio</span>
          </label>
        </div>

        <div class="form-group-admin">
          <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="estado" value="1" <?php echo (!isset($editProject) || $editProject['estado'] == 1) ? 'checked' : ''; ?>>
            <span>Proyecto Activo (Visible en Portafolio)</span>
          </label>
        </div>
      </div>

      <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <button type="submit" class="btn-admin btn-admin-primary">Guardar Proyecto</button>
        <a href="proyectos.php" class="btn-admin btn-admin-secondary">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <!-- TABLA LISTADO -->
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Vista</th>
          <th>Título</th>
          <th>Categoría</th>
          <th>Cliente</th>
          <th>Destacado</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td style="width: 60px;">
              <?php 
                $projImg = !empty($p['imagen']) ? '../frontend/' . ltrim($p['imagen'], '/') : '../frontend/assets/img/portfolio/project-1.svg';
              ?>
              <img src="<?php echo htmlspecialchars($projImg); ?>" alt="Img" style="width: 48px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid var(--devioz-border); display: block;" onerror="this.onerror=null; this.src='../frontend/assets/img/portfolio/project-1.svg';">
            </td>
            <td><strong><?php echo htmlspecialchars($p['titulo']); ?></strong><br><small style="color: var(--devioz-gray); font-family: monospace;"><?php echo htmlspecialchars($p['slug']); ?></small></td>
            <td>
              <span class="badge badge-info"><?php echo htmlspecialchars($p['categoria_nombre']); ?></span>
              <?php if (isset($p['categoria_estado']) && $p['categoria_estado'] == 0): ?>
                <br><span class="badge badge-warning" style="font-size: 0.72rem; margin-top: 4px; display: inline-block;" title="La categoría está inactiva; este proyecto no se muestra en la web pública">⚠️ Cat. Inactiva (Oculto en web)</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($p['cliente'] ?? '-'); ?></td>
            <td>
              <?php if ($p['destacado'] == 1): ?>
                <span class="badge badge-warning">★ Destacado</span>
              <?php else: ?>
                <span style="color: var(--devioz-gray);">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['estado'] == 1): ?>
                <span class="badge badge-success">Activo</span>
              <?php else: ?>
                <span class="badge badge-danger">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="table-actions">
                <a href="proyectos.php?action=edit&id=<?php echo $p['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Editar</a>
                
                <!-- TOGGLE DESTACADO (POST + CSRF) -->
                <form method="POST" action="proyectos.php" class="inline-form">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="toggle_featured">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" title="Alternar condición de destacado">
                    ★ <?php echo $p['destacado'] == 1 ? 'Quitar' : 'Destacar'; ?>
                  </button>
                </form>

                <!-- TOGGLE ESTADO (POST + CSRF) -->
                <form method="POST" action="proyectos.php" class="inline-form">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" title="Alternar visibilidad">
                    <?php echo $p['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>
                  </button>
                </form>

                <!-- ELIMINAR (POST + CSRF + CONFIRM) -->
                <form method="POST" action="proyectos.php" class="inline-form" onsubmit="return confirm('¿Está seguro de eliminar definitivamente este proyecto?');">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" style="color: var(--devioz-danger);" title="Eliminar proyecto">Eliminar</button>
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
