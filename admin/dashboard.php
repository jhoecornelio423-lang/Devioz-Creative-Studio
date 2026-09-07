<?php
/**
 * Devioz Creative Studio Admin - Dashboard Principal
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getPDOConnection();

// Conteos principales
$countServicios = $pdo->query("SELECT COUNT(*) FROM servicios WHERE estado = 1")->fetchColumn();
$countCategorias = $pdo->query("SELECT COUNT(*) FROM categorias WHERE estado = 1")->fetchColumn();
$countProyectos = $pdo->query("SELECT COUNT(*) FROM proyectos WHERE estado = 1")->fetchColumn();
$countContactosNuevos = $pdo->query("SELECT COUNT(*) FROM contactos WHERE estado = 'nuevo'")->fetchColumn();

// Mensajes recientes de contacto
$stmtRecent = $pdo->query("SELECT * FROM contactos ORDER BY id DESC LIMIT 5");
$recentContacts = $stmtRecent->fetchAll();
?>

<div class="page-title-box">
  <h1 class="page-title">Dashboard Principal</h1>
  <a href="../frontend/index.html" target="_blank" class="btn-admin btn-admin-secondary">
    🌐 Ver Web Pública
  </a>
</div>

<!-- TARJETAS DE ESTADÍSTICAS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-info">
      <h3>Servicios Activos</h3>
      <div class="stat-number"><?php echo $countServicios; ?></div>
    </div>
    <div style="font-size: 2rem;">🛠️</div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Categorías Activas</h3>
      <div class="stat-number"><?php echo $countCategorias; ?></div>
    </div>
    <div style="font-size: 2rem;">🏷️</div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Proyectos Activos</h3>
      <div class="stat-number"><?php echo $countProyectos; ?></div>
    </div>
    <div style="font-size: 2rem;">📁</div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Contactos Nuevos</h3>
      <div class="stat-number" style="color: var(--devioz-warning);"><?php echo $countContactosNuevos; ?></div>
    </div>
    <div style="font-size: 2rem;">✉️</div>
  </div>
</div>

<!-- MENSAJES RECIENTES -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <h2 style="font-family: var(--font-heading); font-size: 1.3rem;">Últimas Solicitudes de Cotización</h2>
    <a href="contactos.php" class="btn-admin btn-admin-primary btn-admin-sm">Ver Todos</a>
  </div>

  <div class="table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Servicio Consultada</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentContacts)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: var(--devioz-gray);">No hay solicitudes registradas aún.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentContacts as $c): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong><br><small style="color: var(--devioz-gray);"><?php echo htmlspecialchars($c['empresa'] ?? ''); ?></small></td>
              <td><?php echo htmlspecialchars($c['email']); ?></td>
              <td><?php echo htmlspecialchars($c['servicio_interes']); ?></td>
              <td>
                <?php if ($c['estado'] === 'nuevo'): ?>
                  <span class="badge badge-warning">Nuevo</span>
                <?php elseif ($c['estado'] === 'atendido'): ?>
                  <span class="badge badge-success">Atendido</span>
                <?php else: ?>
                  <span class="badge badge-info"><?php echo htmlspecialchars($c['estado']); ?></span>
                <?php endif; ?>
              </td>
              <td><?php echo date('d/m/Y H:i', strtotime($c['creado_en'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
