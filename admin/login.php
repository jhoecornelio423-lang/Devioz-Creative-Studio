<?php
/**
 * Devioz Creative Studio Admin - Login de Administración
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';

// Si ya hay sesión activa, redirigir al Dashboard
if (isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = 'Error de seguridad: Solicitud inválida (token CSRF expirado o inválido). Recarga la página.';
    } else {
        $emailValue = sanitizeText($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($emailValue) || empty($password)) {
            $error = 'Por favor, ingresa el correo y la contraseña.';
        } elseif (!isValidEmail($emailValue)) {
            $error = 'El formato de correo electrónico no es válido.';
        } else {
            try {
                $pdo = getPDOConnection();
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND estado = 1 LIMIT 1");
                $stmt->execute(['email' => $emailValue]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerar ID de sesión para prevenir Session Fixation
                    session_regenerate_id(true);

                    // Generar nuevo token CSRF tras autenticación exitosa
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Iniciar sesión
                    $_SESSION['admin_user'] = [
                        'id'     => $user['id'],
                        'nombre' => $user['nombre'],
                        'email'  => $user['email'],
                        'rol'    => $user['rol']
                    ];

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Credenciales incorrectas o usuario inactivo.';
                }
            } catch (Exception $e) {
                error_log("[Devioz Admin Login Error] " . $e->getMessage());
                $error = 'Ocurrió un error al procesar la autenticación. Intenta de nuevo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Administrativo - Devioz Creative Studio</title>
  <link rel="icon" type="image/png" href="assets/img/logo-devioz.png">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-body">

  <div class="login-card">
    <div class="login-logo">
      <img src="assets/img/logo-devioz-3d.jpg" alt="Devioz Logo 3D" class="login-logo-3d">
      <div class="login-brand-text">
        Devioz <span>Admin</span>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-admin alert-admin-danger">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout'])): ?>
      <div class="alert-admin alert-admin-success">
        Has cerrado sesión correctamente.
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <?php csrfField(); ?>
      <div class="form-group-admin" style="margin-bottom: 1.25rem;">
        <label for="email">Correo Electrónico</label>
        <input type="email" id="email" name="email" class="form-control-admin" placeholder="admin@devioz.com" value="<?php echo htmlspecialchars($emailValue); ?>" required autofocus>
      </div>

      <div class="form-group-admin" style="margin-bottom: 1.75rem;">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control-admin" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; justify-content: center; padding: 0.85rem;">
        Iniciar Sesión &rarr;
      </button>
    </form>
  </div>

</body>
</html>
