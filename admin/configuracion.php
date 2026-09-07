<?php
/**
 * Devioz Creative Studio Admin - Configuración Global del Negocio
 * Permite modificar los datos institucionales (nombre, email, teléfono, sede, web, horario)
 * que se reflejan de forma dinámica en la web pública.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/validation.php';

$pdo = getPDOConnection();
$pageTitle = 'Configuración General';

// PROCESAR GUARDADO DE CONFIGURACIÓN CON PATRÓN PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $_SESSION['flash_error'] = 'Error de seguridad: Token CSRF inválido o expirado. Por favor, intente nuevamente.';
        header("Location: configuracion.php");
        exit;
    }

    $nombreProyecto   = sanitizeText($_POST['nombre_proyecto'] ?? '');
    $emailContacto    = sanitizeText($_POST['email_contacto'] ?? '');
    $telefonoContacto = sanitizeText($_POST['telefono_contacto'] ?? '');
    $sedePrincipal    = sanitizeText($_POST['sede_principal'] ?? '');
    $webOficial       = sanitizeText($_POST['web_oficial'] ?? '');
    $horarioAtencion  = sanitizeText($_POST['horario_atencion'] ?? '');

    $errors = [];

    if (empty($nombreProyecto)) {
        $errors[] = 'El nombre de la empresa / proyecto es obligatorio.';
    } elseif (!validateMaxLength($nombreProyecto, 100)) {
        $errors[] = 'El nombre del proyecto no debe superar los 100 caracteres.';
    }

    if (empty($emailContacto)) {
        $errors[] = 'El correo electrónico de contacto es obligatorio.';
    } elseif (!filter_var($emailContacto, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico ingresado no tiene un formato válido.';
    }

    if (!empty($telefonoContacto) && !preg_match('/^[0-9\+\-\s\(\)]{7,30}$/', $telefonoContacto)) {
        $errors[] = 'El teléfono o WhatsApp contiene un formato no válido.';
    }

    if (!empty($webOficial) && !filter_var($webOficial, FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL de la web oficial debe tener un formato válido con protocolo (ej. https://devioz.com/).';
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST;
        header("Location: configuracion.php");
        exit;
    }

    try {
        $configsToSave = [
            'nombre_proyecto'   => $nombreProyecto,
            'email_contacto'    => $emailContacto,
            'telefono_contacto' => $telefonoContacto,
            'sede_principal'    => $sedePrincipal,
            'web_oficial'       => $webOficial,
            'horario_atencion'  => $horarioAtencion,
        ];

        $stmt = $pdo->prepare("
            INSERT INTO configuracion (clave, valor) 
            VALUES (:clave, :valor)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");

        foreach ($configsToSave as $clave => $valor) {
            $stmt->execute(['clave' => $clave, 'valor' => $valor]);
        }

        $_SESSION['flash_message'] = 'Parámetros del sistema y datos de contacto actualizados correctamente. Los cambios se sincronizarán en la web pública en tiempo real.';
        header("Location: configuracion.php");
        exit;

    } catch (Exception $e) {
        error_log("[Devioz Admin Config Error] " . $e->getMessage());
        $_SESSION['flash_error'] = 'Ocurrió un error al guardar la configuración: ' . $e->getMessage();
        header("Location: configuracion.php");
        exit;
    }
}

// OBTENER CONFIGURACIÓN ACTUAL DE LA BASE DE DATOS
$stmt = $pdo->query("SELECT clave, valor FROM configuracion");
$currentConfig = [];
while ($row = $stmt->fetch()) {
    $currentConfig[$row['clave']] = $row['valor'];
}

// Mensajes de retroalimentación
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// Restauración en caso de error
$formData = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_data']);

$valNombre   = $formData['nombre_proyecto']   ?? ($currentConfig['nombre_proyecto'] ?? 'Devioz Creative Studio');
$valEmail    = $formData['email_contacto']    ?? ($currentConfig['email_contacto'] ?? 'contacto@devioz.com');
$valTelefono = $formData['telefono_contacto'] ?? ($currentConfig['telefono_contacto'] ?? '999 999 999');
$valSede     = $formData['sede_principal']    ?? ($currentConfig['sede_principal'] ?? 'Lima, Perú');
$valWeb      = $formData['web_oficial']       ?? ($currentConfig['web_oficial'] ?? 'https://devioz.com/');
$valHorario  = $formData['horario_atencion']  ?? ($currentConfig['horario_atencion'] ?? 'Lun - Vie: 9:00 AM - 6:00 PM');

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-box">
  <div>
    <h1 class="page-title">⚙️ Configuración del Sistema</h1>
    <p style="color: var(--devioz-gray); font-size: 0.9rem; margin-top: 0.25rem;">
      Administra los parámetros de la empresa, datos de contacto oficiales y presencia digital.
    </p>
  </div>
</div>

<?php if ($flashMessage): ?>
  <div class="alert-admin alert-admin-success">
    <strong>¡Éxito!</strong> <?php echo $flashMessage; ?>
  </div>
<?php endif; ?>

<?php if ($flashError): ?>
  <div class="alert-admin alert-admin-danger">
    <strong>Atención:</strong><br><?php echo $flashError; ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <form action="configuracion.php" method="POST">
    <?php csrfField(); ?>

    <div class="form-grid">
      <!-- Nombre del Proyecto -->
      <div class="form-group-admin">
        <label for="nombre_proyecto">Nombre del Proyecto / Estudio *</label>
        <input type="text" id="nombre_proyecto" name="nombre_proyecto" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valNombre); ?>" required maxlength="100">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Nombre mostrado en encabezados, pie de página y metadatos.</small>
      </div>

      <!-- Email Oficial -->
      <div class="form-group-admin">
        <label for="email_contacto">Correo Electrónico Oficial *</label>
        <input type="email" id="email_contacto" name="email_contacto" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valEmail); ?>" required maxlength="150">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Bandeja principal para recepción de cotizaciones y dudas.</small>
      </div>

      <!-- Teléfono / WhatsApp -->
      <div class="form-group-admin">
        <label for="telefono_contacto">Teléfono / WhatsApp de Contacto</label>
        <input type="text" id="telefono_contacto" name="telefono_contacto" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valTelefono); ?>" placeholder="Ej: 999 999 999 o +51 999 999 999" maxlength="30">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Número mostrado al público para contacto directo.</small>
      </div>

      <!-- Sede Principal -->
      <div class="form-group-admin">
        <label for="sede_principal">Sede Principal / Ciudad</label>
        <input type="text" id="sede_principal" name="sede_principal" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valSede); ?>" placeholder="Ej: Lima, Perú" maxlength="100">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Ubicación geográfica de la agencia.</small>
      </div>

      <!-- Web Oficial -->
      <div class="form-group-admin">
        <label for="web_oficial">Sitio Web Oficial (URL)</label>
        <input type="url" id="web_oficial" name="web_oficial" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valWeb); ?>" placeholder="https://devioz.com/" maxlength="200">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Enlace principal al ecosistema Devioz.</small>
      </div>

      <!-- Horario de Atención -->
      <div class="form-group-admin">
        <label for="horario_atencion">Horario de Atención</label>
        <input type="text" id="horario_atencion" name="horario_atencion" class="form-control-admin" 
               value="<?php echo htmlspecialchars($valHorario); ?>" placeholder="Ej: Lun - Vie: 9:00 AM - 6:00 PM" maxlength="100">
        <small style="color: var(--devioz-gray); font-size: 0.8rem;">Horas laborales para soporte y cotizaciones.</small>
      </div>
    </div>

    <div style="margin-top: 2rem; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--devioz-border); padding-top: 1.5rem;">
      <div style="color: var(--devioz-gray); font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: var(--devioz-success);"></span>
        Sincronización en vivo activa (Zero-Reload Live Sync)
      </div>
      <button type="submit" class="btn-admin btn-admin-primary">
        💾 Guardar Cambios
      </button>
    </div>
  </form>
</div>

<!-- PANEL INFORMATIVO DE BASE DE DATOS -->
<div class="admin-card" style="margin-top: 1rem; background: rgba(0, 43, 43, 0.4); border-style: dashed;">
  <h3 style="font-size: 1rem; color: var(--devioz-primary); margin-bottom: 0.75rem;">
    💡 ¿Cómo opera la tabla <code>configuracion</code>?
  </h3>
  <p style="color: var(--devioz-gray); font-size: 0.88rem; line-height: 1.6;">
    Esta tabla almacena los parámetros como pares <strong>clave &rarr; valor</strong> en MySQL. 
    Al guardar, el endpoint <code>backend/api/config.php</code> expone los datos actualizados y el frontend los consume dinámicamente mediante <strong>BroadcastChannel</strong> y sondeo inteligente, permitiendo que cualquier ajuste se refleje de inmediato en la pantalla de los clientes sin necesidad de recargar la página (F5).
  </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
