<?php
/**
 * Devioz Creative Studio Admin - Gestión de Categorías (CRUD)
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
        header("Location: categorias.php");
        exit;
    }

    $postAction = sanitizeText($_POST['action'] ?? 'save');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($postAction === 'toggle' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE categorias SET estado = IF(estado=1, 0, 1) WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $stmtState = $pdo->prepare("SELECT estado, nombre FROM categorias WHERE id = :id");
            $stmtState->execute(['id' => $id]);
            $catRow = $stmtState->fetch();

            if ($catRow && $catRow['estado'] == 0) {
                $_SESSION['flash_message'] = "La categoría '{$catRow['nombre']}' fue DESACTIVADA. Esta categoría y sus proyectos asociados han dejado de mostrarse en la web pública.";
            } else {
                $_SESSION['flash_message'] = "La categoría '{$catRow['nombre']}' fue ACTIVADA y vuelve a ser visible en la web pública.";
            }
            header("Location: categorias.php");
            exit;
        } elseif ($postAction === 'delete' && $id > 0) {
            $stmtCheckProjects = $pdo->prepare("SELECT COUNT(*) FROM proyectos WHERE categoria_id = :id");
            $stmtCheckProjects->execute(['id' => $id]);
            $count = (int)$stmtCheckProjects->fetchColumn();

            if ($count > 0) {
                $_SESSION['flash_error'] = "No es posible eliminar la categoría #{$id} porque tiene {$count} proyecto(s) asociado(s). Para ocultarla del sitio web de clientes, utiliza el botón 'Desactivar'.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $_SESSION['flash_message'] = "La categoría #{$id} fue eliminada correctamente.";
            }
            header("Location: categorias.php");
            exit;
        } elseif ($postAction === 'save') {
            $nombre = sanitizeText($_POST['nombre'] ?? '');
            $slug = sanitizeText($_POST['slug'] ?? '');
            $estado = isset($_POST['estado']) ? 1 : 0;

            $validationError = '';
            if (empty($nombre)) {
                $validationError = 'El nombre de la categoría es obligatorio.';
            } elseif (!validateMaxLength($nombre, 100)) {
                $validationError = 'El nombre de la categoría no puede superar los 100 caracteres.';
            } elseif (!isValidStatus($estado, [0, 1])) {
                $validationError = 'El estado proporcionado no es válido.';
            } else {
                // Generación y garantía de unicidad automática del Slug
                if (empty($slug)) {
                    $slug = generateUniqueSlug($pdo, 'categorias', $nombre, $id);
                } else {
                    $slug = generateUniqueSlug($pdo, 'categorias', $slug, $id);
                }

                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE categorias SET nombre = :nombre, slug = :slug, estado = :estado WHERE id = :id");
                    $stmt->execute(['nombre' => $nombre, 'slug' => $slug, 'estado' => $estado, 'id' => $id]);
                    $_SESSION['flash_message'] = 'Categoría actualizada correctamente.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug, estado) VALUES (:nombre, :slug, :estado)");
                    $stmt->execute(['nombre' => $nombre, 'slug' => $slug, 'estado' => $estado]);
                    $_SESSION['flash_message'] = 'Categoría creada correctamente.';
                }
                header("Location: categorias.php");
                exit;
            }

            if (!empty($validationError)) {
                $_SESSION['flash_error'] = $validationError;
                $_SESSION['form_data'] = $_POST;
                $redirectUrl = $id > 0 ? "categorias.php?action=edit&id={$id}" : "categorias.php?action=create";
                header("Location: {$redirectUrl}");
                exit;
            }
        }
        header("Location: categorias.php");
        exit;
    } catch (Exception $e) {
        error_log("[Devioz Admin Categorias Error] " . $e->getMessage());
        $_SESSION['flash_error'] = 'Ocurrió un error interno al procesar la solicitud de categoría.';
        header("Location: categorias.php");
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
$editCategory = null;

// ACCIONES GET SEGURAS (CARGAR VISTA DE EDICIÓN)
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editCategory = $stmt->fetch();
}

if ($formData) {
    if (!$editCategory) {
        $editCategory = [];
    }
    $editCategory['nombre'] = $formData['nombre'] ?? ($editCategory['nombre'] ?? '');
    $editCategory['slug'] = $formData['slug'] ?? ($editCategory['slug'] ?? '');
    $editCategory['estado'] = isset($formData['estado']) ? 1 : 0;
}

// LISTA DE CATEGORÍAS CON CONTEO DE PROYECTOS
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS total_proyectos 
    FROM categorias c 
    LEFT JOIN proyectos p ON c.id = p.categoria_id 
    GROUP BY c.id 
    ORDER BY c.id ASC
")->fetchAll();

$pageTitle = 'Gestión de Categorías';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-box">
  <h1 class="page-title">Gestión de Categorías</h1>
  <?php if ($action === 'list'): ?>
    <a href="categorias.php?action=create" class="btn-admin btn-admin-primary">+ Nueva Categoría</a>
  <?php else: ?>
    <a href="categorias.php" class="btn-admin btn-admin-secondary">&larr; Volver a la Lista</a>
  <?php endif; ?>
</div>

<div class="alert-admin alert-admin-info" style="background: rgba(99, 102, 241, 0.1); border-left: 4px solid var(--devioz-accent); color: #e2e8f0; margin-bottom: 1.5rem; padding: 0.9rem 1.2rem; border-radius: 6px; font-size: 0.9rem;">
  💡 <strong>¿Qué ocurre al Desactivar una categoría?</strong> Al marcar una categoría como inactiva, su pestaña desaparece inmediatamente del menú de filtros de la web de clientes y todos sus proyectos vinculados quedan ocultos del portafolio público, sin tener que eliminarlos.
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
      <?php echo $editCategory ? 'Editar Categoría #' . $editCategory['id'] : 'Crear Nueva Categoría'; ?>
    </h2>

    <form method="POST" action="categorias.php">
      <?php csrfField(); ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editCategory): ?>
        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group-admin full">
          <label for="nombre">Nombre de la Categoría *</label>
          <input type="text" id="nombre" name="nombre" class="form-control-admin" maxlength="100" required 
                 placeholder="Ej. Diseño Gráfico, Animación 3D"
                 value="<?php echo htmlspecialchars($editCategory['nombre'] ?? ''); ?>">
          <input type="hidden" id="slug" name="slug" value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>">
          <small style="color: var(--devioz-gray); font-size: 0.82rem; margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>🔗 Enlace web permanente (automático):</span>
            <span id="slug-preview" style="color: var(--devioz-primary); font-family: monospace; font-size: 0.85rem;">
              <?php echo htmlspecialchars($editCategory['slug'] ?? 'generado-al-escribir'); ?>
            </span>
          </small>
        </div>

        <div class="form-group-admin full">
          <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="estado" value="1" <?php echo (!isset($editCategory) || $editCategory['estado'] == 1) ? 'checked' : ''; ?>>
            <span>Categoría Activa (Visible en web pública y filtros)</span>
          </label>
        </div>
      </div>

      <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <button type="submit" class="btn-admin btn-admin-primary">Guardar Categoría</button>
        <a href="categorias.php" class="btn-admin btn-admin-secondary">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <!-- TABLA LISTADO -->
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Slug</th>
          <th>Proyectos Asociados</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($cat['nombre']); ?></strong></td>
            <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
            <td>
              <span class="badge badge-info"><?php echo (int)$cat['total_proyectos']; ?> proyecto(s)</span>
            </td>
            <td>
              <?php if ($cat['estado'] == 1): ?>
                <span class="badge badge-success">Activa</span>
              <?php else: ?>
                <span class="badge badge-danger">Inactiva (Oculta en web)</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="table-actions">
                <a href="categorias.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Editar</a>
                
                <!-- TOGGLE ESTADO (POST + CSRF) -->
                <form method="POST" action="categorias.php" class="inline-form">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" title="Alternar visibilidad en la web">
                    <?php echo $cat['estado'] == 1 ? 'Desactivar' : 'Activar'; ?>
                  </button>
                </form>

                <!-- ELIMINAR (POST + CSRF + CONFIRM) -->
                <form method="POST" action="categorias.php" class="inline-form" onsubmit="return confirm('¿Está seguro de eliminar esta categoría? Si contiene proyectos asociados la acción será prevenida.');">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                  <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm" style="color: var(--devioz-danger);" title="Eliminar">Eliminar</button>
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
