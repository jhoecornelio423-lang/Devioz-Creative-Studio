<?php
/**
 * Devioz Creative Studio Admin - Gestión de Contactos y Cotizaciones
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';

$pdo = getPDOConnection();

// PROCESAR CAMBIO DE ESTADO POST (PROTEGIDO CON CSRF Y PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $_SESSION['flash_error'] = 'Error de seguridad: Solicitud rechazada por token CSRF inválido o expirado. Por favor, recarga la página e intenta de nuevo.';
        header("Location: contactos.php");
        exit;
    }

    $postAction = sanitizeText($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = sanitizeText($_POST['status'] ?? '');
    $allowedStatuses = ['nuevo', 'en_proceso', 'atendido', 'archivado'];

    if ($postAction === 'update_status' && $id > 0 && isValidStatus($newStatus, $allowedStatuses)) {
        try {
            $adminId = $_SESSION['admin_user']['id'] ?? null;
            $stmt = $pdo->prepare("UPDATE contactos SET estado = :estado, atendido_por = :atendido_por WHERE id = :id");
            $stmt->execute([
                'estado'       => $newStatus,
                'atendido_por' => $adminId,
                'id'           => $id
            ]);
            $_SESSION['flash_message'] = "El estado de la solicitud #{$id} fue actualizado a '{$newStatus}'.";
            header("Location: contactos.php?action=view&id={$id}");
            exit;
        } catch (Exception $e) {
            error_log("[Devioz Admin Contactos Error] " . $e->getMessage());
            $_SESSION['flash_error'] = 'Ocurrió un error interno al actualizar el estado de la solicitud.';
            header("Location: contactos.php?action=view&id={$id}");
            exit;
        }
    } else {
        $_SESSION['flash_error'] = 'Estado o identificador de solicitud no válido.';
        header("Location: contactos.php");
        exit;
    }
}

// RECUPERAR FLASH MESSAGES (PRG)
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$action = sanitizeText($_GET['action'] ?? 'list');
$selectedContact = null;

// PROCESAR DETALLE GET (SOLO LECTURA / VISTA DETALLADA)
if ($action === 'view' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT c.*, s.titulo AS servicio_oficial, u.nombre AS admin_nombre, u.email AS admin_email 
        FROM contactos c 
        LEFT JOIN servicios s ON c.servicio_id = s.id 
        LEFT JOIN usuarios u ON c.atendido_por = u.id 
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $selectedContact = $stmt->fetch();

    // Si estaba como nuevo, marcar automáticamente en_proceso al revisar y asignar al usuario actual
    if ($selectedContact && $selectedContact['estado'] === 'nuevo') {
        try {
            $adminId = $_SESSION['admin_user']['id'] ?? null;
            $stmtUp = $pdo->prepare("UPDATE contactos SET estado = 'en_proceso', atendido_por = :atendido_por WHERE id = :id");
            $stmtUp->execute(['atendido_por' => $adminId, 'id' => $id]);
            $selectedContact['estado'] = 'en_proceso';
            $selectedContact['admin_nombre'] = $_SESSION['admin_user']['nombre'] ?? 'Administrador';
            $selectedContact['admin_email'] = $_SESSION['admin_user']['email'] ?? '';
        } catch (Exception $e) {
            error_log("[Devioz Admin Contactos View Auto-Update Error] " . $e->getMessage());
        }
    }
}

// LISTA DE CONTACTOS CON RELACIÓN A SERVICIOS Y USUARIOS
$contacts = $pdo->query("
    SELECT c.*, s.titulo AS servicio_oficial, u.nombre AS admin_nombre 
    FROM contactos c 
    LEFT JOIN servicios s ON c.servicio_id = s.id 
    LEFT JOIN usuarios u ON c.atendido_por = u.id 
    ORDER BY c.id DESC
")->fetchAll();

$pageTitle = 'Gestión de Solicitudes de Contacto';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-box">
  <h1 class="page-title">Solicitudes de Cotización</h1>
  <?php if ($action === 'view'): ?>
    <a href="contactos.php" class="btn-admin btn-admin-secondary">&larr; Volver a la Lista</a>
  <?php endif; ?>
</div>

<?php if (!empty($message)): ?>
  <div class="alert-admin alert-admin-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert-admin alert-admin-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($action === 'view' && $selectedContact): ?>
  <!-- DETALLE DEL MENSAJE -->
  <div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; border-bottom: 1px solid var(--devioz-border); padding-bottom: 1rem;">
      <div>
        <h2 style="font-family: var(--font-heading); font-size: 1.5rem;">Solicitud #<?php echo $selectedContact['id']; ?> - <?php echo htmlspecialchars($selectedContact['nombre']); ?></h2>
        <span style="color: var(--devioz-gray); font-size: 0.9rem;">Recibido el <?php echo date('d/m/Y H:i:s', strtotime($selectedContact['creado_en'])); ?></span>
      </div>
      <div>
        <span class="badge badge-info" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;"><?php echo strtoupper(htmlspecialchars($selectedContact['estado'])); ?></span>
      </div>
    </div>

    <div class="form-grid" style="margin-bottom: 2rem;">
      <div>
        <strong style="color: var(--devioz-primary);">Empresa / Proyecto:</strong>
        <p><?php echo htmlspecialchars($selectedContact['empresa'] ?: 'No especificado'); ?></p>
      </div>

      <div>
        <strong style="color: var(--devioz-primary);">Email de Contacto:</strong>
        <p><a href="mailto:<?php echo htmlspecialchars($selectedContact['email']); ?>" style="color: var(--devioz-primary); font-weight: 600;"><?php echo htmlspecialchars($selectedContact['email']); ?></a></p>
      </div>

      <div>
        <strong style="color: var(--devioz-primary);">Teléfono / WhatsApp:</strong>
        <p><?php echo htmlspecialchars($selectedContact['telefono'] ?: 'No especificado'); ?></p>
      </div>

      <div>
        <strong style="color: var(--devioz-primary);">Servicio de Interés:</strong>
        <p>
          <?php echo htmlspecialchars($selectedContact['servicio_interes']); ?>
          <?php if (!empty($selectedContact['servicio_oficial'])): ?>
            <span class="badge badge-info" style="margin-left: 0.5rem; font-size: 0.75rem;">Servicio Oficial Vinculado</span>
          <?php endif; ?>
        </p>
      </div>

      <div>
        <strong style="color: var(--devioz-primary);">Atendido / Gestionado por:</strong>
        <p>
          <?php if (!empty($selectedContact['admin_nombre'])): ?>
            <span class="badge badge-success"><?php echo htmlspecialchars($selectedContact['admin_nombre']); ?></span>
            <small style="color: var(--devioz-gray);"><?php echo htmlspecialchars($selectedContact['admin_email']); ?></small>
          <?php else: ?>
            <span style="color: var(--devioz-gray);">Sin asignar aún</span>
          <?php endif; ?>
        </p>
      </div>

      <div class="full">
        <strong style="color: var(--devioz-primary);">Detalles del Proyecto / Mensaje:</strong>
        <div style="background: var(--devioz-dark); border: 1px solid var(--devioz-border); padding: 1.25rem; border-radius: 8px; margin-top: 0.5rem; white-space: pre-wrap;">
          <?php echo htmlspecialchars($selectedContact['mensaje']); ?>
        </div>
      </div>
    </div>

    <!-- CAMBIAR ESTADO (POST + CSRF) -->
    <div style="border-top: 1px solid var(--devioz-border); padding-top: 1.25rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
      <span style="font-weight: 600; margin-right: 0.5rem;">Cambiar Estado:</span>
      
      <form method="POST" action="contactos.php?action=view&id=<?php echo $selectedContact['id']; ?>" class="inline-form">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo $selectedContact['id']; ?>">
        <input type="hidden" name="status" value="en_proceso">
        <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm">En Proceso</button>
      </form>

      <form method="POST" action="contactos.php?action=view&id=<?php echo $selectedContact['id']; ?>" class="inline-form">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo $selectedContact['id']; ?>">
        <input type="hidden" name="status" value="atendido">
        <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">Marcar como Atendido</button>
      </form>

      <form method="POST" action="contactos.php?action=view&id=<?php echo $selectedContact['id']; ?>" class="inline-form">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo $selectedContact['id']; ?>">
        <input type="hidden" name="status" value="archivado">
        <button type="submit" class="btn-admin btn-admin-secondary btn-admin-sm">Archivar</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <!-- TABLA DE LISTADO -->
  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Remitente</th>
          <th>Contacto</th>
          <th>Servicio Interés</th>
          <th>Estado</th>
          <th>Atendido Por</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($contacts)): ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--devioz-gray);">No hay solicitudes de contacto registradas.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($contacts as $c): ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($c['nombre']); ?></strong><br>
                <small style="color: var(--devioz-gray);"><?php echo htmlspecialchars($c['empresa'] ?? ''); ?></small>
              </td>
              <td>
                <small><?php echo htmlspecialchars($c['email']); ?></small><br>
                <small style="color: var(--devioz-gray);"><?php echo htmlspecialchars($c['telefono'] ?? ''); ?></small>
              </td>
              <td>
                <?php echo htmlspecialchars($c['servicio_interes']); ?>
                <?php if (!empty($c['servicio_oficial'])): ?>
                  <br><span class="badge badge-info" style="font-size: 0.7rem;">Vinculado</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['estado'] === 'nuevo'): ?>
                  <span class="badge badge-warning">Nuevo</span>
                <?php elseif ($c['estado'] === 'en_proceso'): ?>
                  <span class="badge badge-info">En Proceso</span>
                <?php elseif ($c['estado'] === 'atendido'): ?>
                  <span class="badge badge-success">Atendido</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><?php echo htmlspecialchars($c['estado']); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($c['admin_nombre'])): ?>
                  <small style="color: #e2e8f0; font-weight: 500;"><?php echo htmlspecialchars($c['admin_nombre']); ?></small>
                <?php else: ?>
                  <small style="color: var(--devioz-gray);">-</small>
                <?php endif; ?>
              </td>
              <td><?php echo date('d/m/Y H:i', strtotime($c['creado_en'])); ?></td>
              <td>
                <div class="table-actions">
                  <a href="contactos.php?action=view&id=<?php echo $c['id']; ?>" class="btn-admin btn-admin-primary btn-admin-sm">Ver Detalle</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
