<?php
/**
 * Devioz Creative Studio Admin - Layout Header
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../../backend/config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' - Admin' : 'Devioz Creative Studio Admin'; ?></title>
  <link rel="icon" type="image/png" href="assets/img/logo-devioz.png">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-wrapper">
  <!-- TOP NAVBAR -->
  <header class="top-navbar">
    <div class="brand">
      <span style="color: var(--devioz-gray); font-size: 0.9rem;">Panel de Administración</span>
    </div>
    <div class="user-info">
      <span class="user-email"><?php echo htmlspecialchars($currentUser['email']); ?> (<?php echo htmlspecialchars($currentUser['nombre']); ?>)</span>
      <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
  </header>

  <!-- CONTENT CONTAINER -->
  <main class="content">
