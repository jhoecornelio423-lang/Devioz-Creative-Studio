<?php
/**
 * Devioz Creative Studio Admin - Middleware de Autenticación
 */

require_once __DIR__ . '/session.php';
initAdminSession();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_user']) || empty($_SESSION['admin_user']['id'])) {
    header("Location: login.php");
    exit;
}

$currentUser = $_SESSION['admin_user'];
