<?php
/**
 * Devioz Creative Studio Admin - Cierre de Sesión
 */

require_once __DIR__ . '/includes/session.php';

destroyAdminSession();

header("Location: login.php?logout=1");
exit;
