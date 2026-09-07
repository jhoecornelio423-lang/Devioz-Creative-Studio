<?php
/**
 * Devioz Creative Studio Admin - Gestión de Servicios (CRUD)
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';

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
            $_SESSION['flash_message'] = "El estado del servicio #{$id} fue actualizado correctamente.";
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
            $imagen = sanitizeText($_POST['imagen'] ?? '');
            $beneficios = sanitizeText($_POST['beneficios'] ?? '');
            $estado = isset($_POST['estado']) ? 1 : 0;

            $categoriaId = (!empty($_POST['categoria_id']) && (int)$_POST['categoria_id'] > 0) ? (int)$_POST['categoria_id'] : null;

            $validationError = '';
            if (empty($titulo) || empty($slug) || empty($descripcion)) {
                $validationError = 'Título, Slug y Descripción son campos obligatorios.';
            } elseif (!validateMaxLength($titulo, 150)) {
                $validationError = 'El título no puede superar los 150 caracteres.';
            } elseif (!isValidSlug($slug, 180)) {
                $validationError = 'El slug URL no es válido. Solo debe contener letras minúsculas, números y guiones sencillos (máx 180 caracteres).';
            } elseif (!validateMaxLength($descripcion, 5000)) {
                $validationError = 'La descripción es demasiado larga (máximo 5000 caracteres).';
            } elseif (!empty($imagen) && !validateMaxLength($imagen, 255)) {
                $validationError = 'La ruta de imagen no puede superar los 255 caracteres.';
            } elseif (!empty($beneficios) && !validateMaxLength($beneficios, 1000)) {
                $validationError = 'Los beneficios no pueden superar los 1000 caracteres.';
            } elseif (!isValidStatus($estado, [0, 1])) {
                $validationError = 'El estado proporcionado no es válido.';
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM servicios WHERE slug = :slug AND id != :id LIMIT 1");
                $stmtCheck->execute(['slug' => $slug, 'id' => $id]);

                if ($stmtCheck->fetch()) {
                    $validationError = "El slug '{$slug}' ya está registrado en otro servicio. Por favor, elige uno diferente.";
                } else {
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

    <form method="POST" action="servicios.php">
      <?php csrfField(); ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editService): ?>
        <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group-admin">
          <label for="titulo">Título del Servicio *</label>
          <input type="text" id="titulo" name="titulo" class="form-control-admin" maxlength="150" required value="<?php echo htmlspecialchars($editService['titulo'] ?? ''); ?>">
        </div>

        <div class="form-group-admin">
          <label for="slug">Slug URL *</label>
          <input type="text" id="slug" name="slug" class="form-control-admin" maxlength="180" placeholder="ej-diseno-grafico" required value="<?php echo htmlspecialchars($editService['slug'] ?? ''); ?>">
          <small style="color: var(--devioz-gray); font-size: 0.8rem;">Solo letras minúsculas, números y guiones.</small>
        </div>

        <div class="form-group-admin">
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

        <div class="form-group-admin">
          <label for="imagen">Ruta Imagen / Icono</label>
          <input type="text" id="imagen" name="imagen" class="form-control-admin" maxlength="255" value="<?php echo htmlspecialchars($editService['imagen'] ?? 'assets/img/services/diseno-grafico.svg'); ?>">
        </div>

        <div class="form-group-admin full">
          <label for="descripcion">Descripción *</label>
          <textarea id="descripcion" name="descripcion" class="form-control-admin" maxlength="5000" required><?php echo htmlspecialchars($editService['descripcion'] ?? ''); ?></textarea>
        </div>

        <div class="form-group-admin full">
          <label for="beneficios">Beneficios (Separados por pipe |)</label>
          <input type="text" id="beneficios" name="beneficios" class="form-control-admin" maxlength="1000" placeholder="Beneficio 1|Beneficio 2|Beneficio 3" value="<?php echo htmlspecialchars($editService['beneficios'] ?? ''); ?>">
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
          <th>Título</th>
          <th>Categoría Vinculada</th>
          <th>Slug</th>
          <th>Beneficios</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($s['titulo']); ?></strong></td>
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
