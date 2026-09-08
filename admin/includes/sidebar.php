<?php
/**
 * Devioz Creative Studio Admin - Sidebar Navigation
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="assets/img/logo-devioz-3d.jpg" alt="Devioz Logo 3D" class="sidebar-logo-3d" onerror="this.onerror=null; this.src='assets/img/logo-devioz.png';">
    <div class="sidebar-brand-text">
      Devioz <span>Admin</span>
    </div>
  </div>

  <ul class="sidebar-menu">
    <li>
      <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
        📊 Dashboard
      </a>
    </li>
    <li>
      <a href="servicios.php" class="<?php echo $currentPage === 'servicios.php' ? 'active' : ''; ?>">
        🛠️ Servicios
      </a>
    </li>
    <li>
      <a href="categorias.php" class="<?php echo $currentPage === 'categorias.php' ? 'active' : ''; ?>">
        🏷️ Categorías
      </a>
    </li>
    <li>
      <a href="proyectos.php" class="<?php echo $currentPage === 'proyectos.php' ? 'active' : ''; ?>">
        📁 Portafolio
      </a>
    </li>
    <li>
      <a href="contactos.php" class="<?php echo $currentPage === 'contactos.php' ? 'active' : ''; ?>">
        ✉️ Contactos
      </a>
    </li>
    <li>
      <a href="configuracion.php" class="<?php echo $currentPage === 'configuracion.php' ? 'active' : ''; ?>">
        ⚙️ Configuración del Footer
      </a>
    </li>
    <li style="margin-top: 1.5rem; border-top: 1px solid var(--devioz-border); padding-top: 1rem;">
      <a href="../frontend/index.html" target="_blank">
        🌐 Ver Web Pública &rarr;
      </a>
    </li>
    <li>
      <a href="logout.php" style="color: var(--devioz-danger);">
        🚪 Cerrar Sesión
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    Devioz Creative Studio v1.0
  </div>
</aside>
