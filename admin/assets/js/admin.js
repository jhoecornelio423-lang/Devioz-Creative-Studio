/**
 * Devioz Creative Studio - Admin Panel JavaScript
 * Sincronización bidireccional en tiempo real sin F5 (Zero-Reload)
 */

let adminSyncChannel = null;
try {
  if ('BroadcastChannel' in window) {
    adminSyncChannel = new BroadcastChannel('devioz_sync_channel');
  }
} catch (e) {}

document.addEventListener('DOMContentLoaded', () => {
  // Auto-generación y previsualización dinámica de slug (sin requerir intervención manual)
  const titleInput = document.getElementById('titulo') || document.getElementById('nombre');
  const slugInput = document.getElementById('slug');
  const slugPreview = document.getElementById('slug-preview');

  if (titleInput && slugInput) {
    titleInput.addEventListener('input', () => {
      const generated = generateSlug(titleInput.value);
      slugInput.value = generated;
      if (slugPreview) {
        slugPreview.textContent = generated || 'generado-al-escribir';
      }
    });
  }

  // Previsualización instantánea de imagen al seleccionar archivo desde el equipo
  const fileInput = document.getElementById('imagen_archivo');
  const imgPreview = document.getElementById('image-preview');
  if (fileInput && imgPreview) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      if (file) {
        imgPreview.src = URL.createObjectURL(file);
        imgPreview.style.display = 'block';
      }
    });
  }

  // Sincronización instantánea con pestañas del frontend al completar cualquier acción admin
  const successAlert = document.querySelector('.alert-admin-success');
  if (successAlert) {
    notifyFrontendSync();
    setTimeout(notifyFrontendSync, 200);
  }

  // Validación estricta y restricción en tiempo real a 9 dígitos para el teléfono de configuración
  const configTelInput = document.getElementById('telefono_contacto');
  const configForm = document.getElementById('configFooterForm') || document.querySelector('form[action="configuracion.php"]');

  if (configTelInput) {
    configTelInput.setAttribute('maxlength', '9');
    configTelInput.setAttribute('inputmode', 'numeric');
    configTelInput.addEventListener('input', (e) => {
      // Filtrar letras y caracteres no numéricos, restringir estrictamente a 9 dígitos
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 9);
    });
  }

  if (configForm && configTelInput) {
    configForm.addEventListener('submit', (e) => {
      const cleanTel = configTelInput.value.replace(/\D/g, '');
      if (cleanTel.length > 0 && cleanTel.length !== 9) {
        e.preventDefault();
        alert('El número de teléfono o WhatsApp debe contener exactamente 9 dígitos numéricos (ej. 987654321).');
        configTelInput.focus();
        return false;
      }
    });
  }

  // Interceptar botones y formularios de acciones en línea para ejecutarlos vía AJAX (CERO F5)
  bindAjaxActionForms();

  // Escuchar cotizaciones y eventos en tiempo real sin recargar página (CERO F5)
  initAdminRealtimeListener();
});

let isRefreshingAdmin = false;

/**
 * Convierte formularios de acciones en línea (Activar, Desactivar, Destacar, Eliminar)
 * a peticiones asíncronas AJAX para que la pantalla del admin NUNCA recargue (CERO F5).
 */
function bindAjaxActionForms() {
  document.querySelectorAll('form.inline-form').forEach(form => {
    if (form.dataset.ajaxBound === 'true') return;
    form.dataset.ajaxBound = 'true';

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Si tiene confirmación (como eliminar), evaluar antes de continuar
      const onsubmitAttr = form.getAttribute('onsubmit');
      if (onsubmitAttr && onsubmitAttr.includes('confirm')) {
        const match = onsubmitAttr.match(/confirm\(['"]([^'"]+)['"]\)/);
        const confirmMsg = match ? match[1] : '¿Está seguro de realizar esta acción?';
        if (!confirm(confirmMsg)) {
          return;
        }
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
      }

      try {
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action') || window.location.href;

        // Ejecutar petición POST en segundo plano
        await fetch(actionUrl, {
          method: 'POST',
          body: formData,
          cache: 'no-store'
        });

        // Actualizar la tabla del administrador de forma invisible (CERO F5)
        await refreshAdminContentSilently(false);

        // Notificar inmediatamente a la web del usuario
        notifyFrontendSync();
        setTimeout(notifyFrontendSync, 200);

      } catch (err) {
        console.error('[Devioz Admin] Error en acción AJAX:', err);
        form.submit();
      }
    });
  });
}

/**
 * Escucha eventos en tiempo real e inserta/actualiza contenido de forma invisible (CERO F5)
 */
function initAdminRealtimeListener() {
  const handleRealtimeEvent = (action) => {
    const rawPath = window.location.pathname.replace(/\/+$/, '');
    const isContactList = rawPath.endsWith('contactos.php') && !window.location.search.includes('action=view');
    const isDashboard = rawPath.endsWith('dashboard.php') || rawPath.endsWith('admin') || rawPath.endsWith('admin/index.php');
    const isModuleList = rawPath.endsWith('proyectos.php') || rawPath.endsWith('servicios.php') || rawPath.endsWith('categorias.php') || rawPath.endsWith('configuracion.php');

    if (action === 'NEW_CONTACT' && (isContactList || isDashboard)) {
      // Actualizar silenciosamente la tabla y contadores sin forzar F5
      refreshAdminContentSilently(true);
    } else if (action === 'SYNC' && (isDashboard || isModuleList)) {
      // Si otra pestaña hizo cambios, sincronizar tabla sin F5
      refreshAdminContentSilently(false);
    }
  };

  // 1. Canal directo persistente entre pestañas (latencia < 1ms)
  if (adminSyncChannel) {
    adminSyncChannel.onmessage = (e) => {
      if (e.data && e.data.action) {
        handleRealtimeEvent(e.data.action);
      }
    };
  }

  // 2. Evento Storage para sincronización inter-ventanas
  window.addEventListener('storage', (e) => {
    if (e.key === 'devioz_new_contact_trigger') {
      handleRealtimeEvent('NEW_CONTACT');
    } else if (e.key === 'devioz_sync_trigger') {
      handleRealtimeEvent('SYNC');
    }
  });

  // 3. Al volver a enfocar la pestaña de administración
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      handleRealtimeEvent('NEW_CONTACT');
    }
  });

  window.addEventListener('focus', () => {
    handleRealtimeEvent('NEW_CONTACT');
  });
}

/**
 * Actualiza silenciosamente el DOM del panel administrativo sin recargar la página (CERO F5)
 */
async function refreshAdminContentSilently(highlightNew = false) {
  if (isRefreshingAdmin) return;
  isRefreshingAdmin = true;

  try {
    const res = await fetch(window.location.href, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    });

    if (!res.ok) return;

    const htmlText = await res.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlText, 'text/html');

    // 1. Actualizar cuerpo de la tabla si existe
    const currentTbody = document.querySelector('.admin-table tbody');
    const newTbody = doc.querySelector('.admin-table tbody');
    if (currentTbody && newTbody) {
      if (currentTbody.innerHTML !== newTbody.innerHTML) {
        currentTbody.innerHTML = newTbody.innerHTML;
        if (highlightNew) {
          const firstRow = currentTbody.querySelector('tr');
          if (firstRow) {
            firstRow.classList.add('new-row-pulse');
          }
        }
        // Re-enlazar acciones AJAX sobre los nuevos elementos insertados
        bindAjaxActionForms();
      }
    }

    // 2. Actualizar tarjetas de estadísticas del Dashboard si existen
    const currentStats = document.querySelector('.stats-grid');
    const newStats = doc.querySelector('.stats-grid');
    if (currentStats && newStats) {
      if (currentStats.innerHTML !== newStats.innerHTML) {
        currentStats.innerHTML = newStats.innerHTML;
      }
    }

  } catch (err) {
    console.warn('[Devioz Admin] Error en refresco silencioso:', err);
  } finally {
    isRefreshingAdmin = false;
  }
}

function notifyFrontendSync() {
  try {
    localStorage.setItem('devioz_sync_trigger', Date.now().toString());
    if (adminSyncChannel) {
      adminSyncChannel.postMessage({ action: 'SYNC', timestamp: Date.now() });
    }
  } catch (err) {
    // Modo privado o storage restringido
  }
}

function generateSlug(text) {
  return text
    .toString()
    .toLowerCase()
    .trim()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9 -]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}
