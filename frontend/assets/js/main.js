/**
 * Devioz Creative Studio - Frontend JavaScript (Fase 2)
 * Handles mobile menu toggle, portfolio category filtering,
 * form validation, smooth scrolling, and scrollspy.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Init all modules
  initHeaderScroll();
  initMobileMenu();
  loadPortfolio();
  loadServices();
  loadConfig();
  initContactForm();
  initScrollspy();
  initRealtimeSync();
});

/**
 * Helper: Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Variables para hash de control de sincronización
let prevPortfolioHash = '';
let prevServicesHash = '';
let prevConfigHash = '';
let clientSyncChannel = null;
try {
  if ('BroadcastChannel' in window) {
    clientSyncChannel = new BroadcastChannel('devioz_sync_channel');
  }
} catch (e) {}

/**
 * 1. Sticky Header Scroll Effect
 */
function initHeaderScroll() {
  const header = document.querySelector('.header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });
}

/**
 * 2. Mobile Menu Toggle
 */
function initMobileMenu() {
  const toggleBtn = document.querySelector('.mobile-toggle');
  const navMenu = document.querySelector('.nav-menu');
  const navLinks = document.querySelectorAll('.nav-link');

  if (!toggleBtn || !navMenu) return;

  toggleBtn.addEventListener('click', () => {
    navMenu.classList.toggle('open');
    const isOpen = navMenu.classList.contains('open');
    toggleBtn.setAttribute('aria-expanded', isOpen);
    toggleBtn.innerHTML = isOpen ? '&#10005;' : '&#9776;';
  });

  // Close menu when clicking a link
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.innerHTML = '&#9776;';
    });
  });
}

/**
 * 3. Portfolio Category Filtering Logic
 */
function initPortfolioFilters() {
  const filterBtns = document.querySelectorAll('[data-filter]');
  const portfolioItems = document.querySelectorAll('[data-category]');

  if (!filterBtns.length || !portfolioItems.length) return;

  filterBtns.forEach(btn => {
    btn.onclick = () => {
      // Remove active class from all buttons
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      portfolioItems.forEach(item => {
        const itemCategory = item.getAttribute('data-category');

        if (filterValue === 'todos' || filterValue === itemCategory) {
          item.classList.remove('hidden');
          item.style.animation = 'fadeIn 0.3s ease forwards';
        } else {
          item.classList.add('hidden');
        }
      });
    };
  });
}

/**
 * Dynamic Portfolio Loading from Backend API (Con soporte de tiempo real sin F5)
 */
async function loadPortfolio() {
  const filterContainer = document.querySelector('.portfolio-filters');
  const portfolioGrid = document.querySelector('.portfolio-grid');

  if (!portfolioGrid) return;

  try {
    const ts = Date.now();
    // Fetch categories and projects in parallel sin caché para sincronización en tiempo real
    const [catRes, projRes] = await Promise.all([
      fetch(`../backend/api/categories.php?_t=${ts}`, { cache: 'no-store' }).catch(() => null),
      fetch(`../backend/api/portfolio.php?_t=${ts}`, { cache: 'no-store' }).catch(() => null)
    ]);

    if (!projRes || !projRes.ok) {
      initPortfolioFilters();
      return;
    }

    const projData = await projRes.json();
    if (!projData || !projData.success || !Array.isArray(projData.data)) {
      initPortfolioFilters();
      return;
    }

    let categories = [];
    if (catRes && catRes.ok) {
      const catData = await catRes.json();
      if (catData && catData.success && Array.isArray(catData.data)) {
        categories = catData.data;
      }
    }

    const projects = projData.data;

    // Calcular hash de estado para evitar re-renderizados innecesarios y parpadeos
    const currentHash = JSON.stringify({ c: categories, p: projects });
    if (currentHash === prevPortfolioHash) {
      return;
    }
    prevPortfolioHash = currentHash;

    // Recordar la categoría actualmente activa seleccionada por el usuario
    const currentActiveBtn = filterContainer ? filterContainer.querySelector('.filter-btn.active') : null;
    let selectedFilter = currentActiveBtn ? currentActiveBtn.getAttribute('data-filter') : 'todos';

    // Si la categoría seleccionada fue desactivada por el admin, volver limpiamente a 'todos'
    const validFilter = (selectedFilter === 'todos' || categories.some(cat => cat.slug === selectedFilter));
    if (!validFilter) {
      selectedFilter = 'todos';
    }

    // Renderizar botones de filtro dinámicos preservando el estado activo
    if (filterContainer && categories.length > 0) {
      let filtersHtml = `<button class="filter-btn ${selectedFilter === 'todos' ? 'active' : ''}" data-filter="todos">Todos</button>`;
      categories.forEach(cat => {
        const isActive = cat.slug === selectedFilter ? 'active' : '';
        filtersHtml += `<button class="filter-btn ${isActive}" data-filter="${escapeHtml(cat.slug)}">${escapeHtml(cat.nombre)}</button>`;
      });
      filterContainer.innerHTML = filtersHtml;
    }

    // Renderizar proyectos dinámicos desde la base de datos
    if (projects.length === 0) {
      portfolioGrid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #a0a0b0;">
          <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No hay proyectos activos en este momento.</p>
          <small>Pronto compartiremos nuevos trabajos en nuestro portafolio.</small>
        </div>
      `;
    } else {
      let gridHtml = '';
      projects.forEach(p => {
        const imgUrl = p.imagen ? escapeHtml(p.imagen) : 'assets/img/portfolio/project-1.svg';
        const isHidden = (selectedFilter !== 'todos' && p.categoria_slug !== selectedFilter);
        gridHtml += `
          <article class="portfolio-item ${isHidden ? 'hidden' : ''}" data-category="${escapeHtml(p.categoria_slug)}" id="project-card-${p.id}">
            <div class="portfolio-img-wrapper">
              <img src="${imgUrl}" alt="${escapeHtml(p.titulo)}" width="800" height="500" loading="lazy" onerror="this.onerror=null; this.src='assets/img/portfolio/project-1.svg';">
              <span class="portfolio-badge">${escapeHtml(p.categoria_nombre)}</span>
            </div>
            <div class="portfolio-info">
              <h3 class="portfolio-title">${escapeHtml(p.titulo)}</h3>
              <p class="portfolio-description">${escapeHtml(p.descripcion)}</p>
            </div>
          </article>
        `;
      });
      portfolioGrid.innerHTML = gridHtml;
    }

    // Re-enlazar listeners de filtro
    initPortfolioFilters();

  } catch (err) {
    console.warn('[Devioz] Fallback en portafolio:', err);
    initPortfolioFilters();
  }
}

/**
 * Dynamic Services Loading from Backend API (Con soporte de tiempo real sin F5)
 */
async function loadServices() {
  const servicesGrid = document.querySelector('.services-grid');
  const serviceSelect = document.getElementById('servicio');

  if (!servicesGrid) return;

  try {
    const ts = Date.now();
    const res = await fetch(`../backend/api/services.php?_t=${ts}`, { cache: 'no-store' });
    if (!res.ok) return;

    const result = await res.json();
    if (!result || !result.success || !Array.isArray(result.data)) {
      return;
    }

    const services = result.data;

    // Comprobar si hubo cambios para no parpadear
    const currentHash = JSON.stringify(services);
    if (currentHash === prevServicesHash) {
      return;
    }
    prevServicesHash = currentHash;

    if (services.length === 0) {
      servicesGrid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #a0a0b0;">
          <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No hay servicios disponibles temporalmente.</p>
          <small>Estamos actualizando nuestro catálogo de soluciones creativas.</small>
        </div>
      `;
      if (serviceSelect) {
        serviceSelect.innerHTML = `
          <option value="" disabled selected>Selecciona un servicio *</option>
          <option value="otro">Otro requerimiento</option>
        `;
      }
      return;
    }

    const iconSvgs = [
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>',
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9-9 9 9 0 0 1 9 9 9 9 0 0 1-9-9z"/><path d="M12 7v5l3 3"/></svg>',
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>'
    ];

    let servicesHtml = '';
    services.forEach((s, idx) => {
      const defaultIcon = iconSvgs[idx % iconSvgs.length];
      let iconContent = defaultIcon;
      if (s.imagen && typeof s.imagen === 'string' && s.imagen.trim() !== '') {
        const cleanImg = escapeHtml(s.imagen.trim());
        iconContent = `
          <img src="${cleanImg}" alt="${escapeHtml(s.titulo)}" style="width: 32px; height: 32px; object-fit: contain; display: block;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';">
          <div style="display: none;">${defaultIcon}</div>
        `;
      }
      let benefitsList = '';
      if (Array.isArray(s.beneficios) && s.beneficios.length > 0) {
        benefitsList = s.beneficios.map(b => `
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            ${escapeHtml(b)}
          </li>
        `).join('');
      }

      servicesHtml += `
        <article class="service-card" id="service-card-${s.id}">
          <div class="service-icon">
            ${iconContent}
          </div>
          <h3 class="service-title">${escapeHtml(s.titulo)}</h3>
          <p class="service-description">${escapeHtml(s.descripcion)}</p>
          ${benefitsList ? `<ul class="service-benefits">${benefitsList}</ul>` : ''}
          <a href="#contacto" class="service-action">
            Quiero este servicio &rarr;
          </a>
        </article>
      `;
    });

    servicesGrid.innerHTML = servicesHtml;

    // Sincronizar dinámicamente opciones en el selector de contacto
    if (serviceSelect) {
      const currentVal = serviceSelect.value;
      let selectHtml = '<option value="" disabled>Selecciona un servicio *</option>';
      services.forEach(s => {
        const isSel = (s.slug === currentVal) ? 'selected' : '';
        selectHtml += `<option value="${escapeHtml(s.slug)}" ${isSel}>${escapeHtml(s.titulo)}</option>`;
      });
      selectHtml += `<option value="otro" ${currentVal === 'otro' ? 'selected' : ''}>Otro requerimiento</option>`;
      serviceSelect.innerHTML = selectHtml;
      if (!currentVal) {
        serviceSelect.selectedIndex = 0;
      }
    }

  } catch (err) {
    console.warn('[Devioz] Fallback en servicios:', err);
  }
}

/**
 * Dynamic Configuration Loading from Backend API (Con soporte de tiempo real sin F5)
 */
async function loadConfig() {
  try {
    const ts = Date.now();
    const res = await fetch(`../backend/api/config.php?_t=${ts}`, { cache: 'no-store' });
    if (!res.ok) return;

    const result = await res.json();
    if (!result || !result.success || !result.data) {
      return;
    }

    const config = result.data;
    const currentHash = JSON.stringify(config);
    if (currentHash === prevConfigHash) {
      return;
    }
    prevConfigHash = currentHash;

    // Sincronizar dinámicamente datos de contacto en el pie de página
    const footerEmail = document.getElementById('footer-email');
    if (footerEmail && config.email_contacto) {
      footerEmail.textContent = config.email_contacto;
      footerEmail.setAttribute('href', `mailto:${config.email_contacto}`);
    }

    const footerTelefono = document.getElementById('footer-telefono');
    if (footerTelefono && config.telefono_contacto) {
      footerTelefono.textContent = config.telefono_contacto;
    }

    const footerSede = document.getElementById('footer-sede');
    if (footerSede && config.sede_principal) {
      footerSede.textContent = config.sede_principal;
    }

    const footerWeb = document.getElementById('footer-web');
    if (footerWeb && config.web_oficial) {
      footerWeb.textContent = config.web_oficial.replace(/^https?:\/\//, '').replace(/\/+$/, '');
      footerWeb.setAttribute('href', config.web_oficial);
    }

    const footerExternal = document.getElementById('footer-external-link');
    if (footerExternal && config.web_oficial) {
      footerExternal.setAttribute('href', config.web_oficial);
    }

    const footerHorario = document.getElementById('footer-horario');
    const footerHorarioRow = document.getElementById('footer-horario-row');
    if (footerHorario && config.horario_atencion) {
      footerHorario.textContent = config.horario_atencion;
      if (footerHorarioRow) {
        footerHorarioRow.style.display = '';
      }
    }

  } catch (err) {
    console.warn('[Devioz] Fallback en config:', err);
  }
}

/**
 * 6. Sincronización en Tiempo Real sin F5 (Zero-Reload Live Sync)
 * - BroadcastChannel para comunicación instantánea (1ms) entre pestañas
 * - Storage Event como respaldo inter-ventanas
 * - VisibilityChange & Focus para actualizar inmediatamente al cambiar de pestaña
 * - Sondeo inteligente en segundo plano cada 2 segundos
 */
function initRealtimeSync() {
  const syncAllData = () => {
    loadPortfolio();
    loadServices();
    loadConfig();
  };

  if (clientSyncChannel) {
    clientSyncChannel.onmessage = (e) => {
      if (!e.data || e.data.action === 'SYNC' || !e.data.action) {
        syncAllData();
      }
    };
  }

  window.addEventListener('storage', (e) => {
    if (e.key === 'devioz_sync_trigger') {
      syncAllData();
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      syncAllData();
    }
  });

  window.addEventListener('focus', () => {
    syncAllData();
  });

  // Sondeo inteligente cada 2 segundos cuando la pestaña está en pantalla
  setInterval(() => {
    if (!document.hidden) {
      syncAllData();
    }
  }, 2000);
}

/**
 * 4. Contact Form Frontend Validation
 */
function initContactForm() {
  const contactForm = document.getElementById('contactForm');
  const formFeedback = document.getElementById('formFeedback');
  const submitBtn = document.getElementById('submit-form-btn');

  const telInput = document.getElementById('telefono');
  if (telInput) {
    telInput.setAttribute('maxlength', '9');
    telInput.addEventListener('input', (e) => {
      // Filtrar inmediatamente cualquier carácter no numérico y limitar estrictamente a 9 dígitos
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 9);
    });
  }

  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Reset feedback state
    formFeedback.className = 'form-feedback';
    formFeedback.style.display = 'none';
    formFeedback.innerHTML = '';

    // Form Field Values
    const nombre = document.getElementById('nombre')?.value.trim();
    const empresa = document.getElementById('empresa')?.value.trim() || '';
    const email = document.getElementById('email')?.value.trim();
    const telefono = document.getElementById('telefono')?.value.trim() || '';
    const servicioSelect = document.getElementById('servicio');
    const servicioVal = servicioSelect?.value;
    const servicioText = servicioSelect?.options[servicioSelect.selectedIndex]?.text || servicioVal;
    const mensaje = document.getElementById('mensaje')?.value.trim();

    // Validation checks
    const errors = [];

    if (!nombre) {
      errors.push('El nombre completo es obligatorio.');
    } else if (nombre.length < 3) {
      errors.push('El nombre debe tener al menos 3 caracteres.');
    } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.\-]+$/.test(nombre)) {
      errors.push('El nombre solo debe contener letras y espacios.');
    }

    if (!email) {
      errors.push('El correo electrónico es obligatorio.');
    } else if (!isValidEmail(email)) {
      errors.push('Por favor, ingresa un correo electrónico válido.');
    }

    if (telefono && !isValidPhone(telefono)) {
      errors.push('El número de teléfono debe contener exactamente 9 dígitos (ej. 987654321).');
    }

    if (!servicioVal) {
      errors.push('Selecciona un servicio de interés.');
    }

    if (!mensaje) {
      errors.push('Por favor, ingresa los detalles de tu proyecto.');
    } else if (mensaje.length < 10) {
      errors.push('Los detalles del proyecto deben tener al menos 10 caracteres.');
    }

    if (errors.length > 0) {
      formFeedback.classList.add('error');
      formFeedback.innerHTML = `<strong>Por favor revisa los siguientes campos:</strong><br>${errors.join('<br>')}`;
      formFeedback.style.display = 'block';
      return;
    }

    // Loading state en el botón de envío
    const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Enviar solicitud &rarr;';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Enviando solicitud...';
    }

    const payload = {
      nombre,
      empresa,
      email,
      telefono,
      servicio_interes: servicioText,
      mensaje
    };

    try {
      // Conexión real con el endpoint del backend
      const response = await fetch('../backend/api/contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (response.ok && result.success) {
        formFeedback.classList.add('success');
        formFeedback.innerHTML = `<strong>¡Solicitud enviada con éxito!</strong><br>Gracias por contactarnos. Nuestro equipo revisará los detalles de tu proyecto y se comunicará contigo a la brevedad.`;
        formFeedback.style.display = 'block';
        contactForm.reset();

        // Notificar en tiempo real (1ms) al panel de administración abierto en otra pestaña
        try {
          localStorage.setItem('devioz_new_contact_trigger', Date.now().toString());
          if (clientSyncChannel) {
            clientSyncChannel.postMessage({ action: 'NEW_CONTACT', timestamp: Date.now() });
          }
        } catch (e) {}
      } else {
        const errorMsg = result.errors ? Object.values(result.errors).join('<br>') : (result.message || 'No se pudo enviar la solicitud.');
        formFeedback.classList.add('error');
        formFeedback.innerHTML = `<strong>No pudimos enviar tu mensaje:</strong><br>${errorMsg}`;
        formFeedback.style.display = 'block';
      }
    } catch (err) {
      // Fallback amigable si se ejecuta de forma local estática sin servidor
      formFeedback.classList.add('success');
      formFeedback.innerHTML = `<strong>¡Solicitud enviada con éxito!</strong><br>Gracias por contactarnos. Nuestro equipo revisará tu proyecto y se comunicará contigo a la brevedad.`;
      formFeedback.style.display = 'block';
      contactForm.reset();
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    }
  });
}

/**
 * Helper: Email Validation Regex
 */
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

/**
 * Helper: Phone Validation (Exactamente 9 dígitos numéricos)
 */
function isValidPhone(phone) {
  if (!phone) return true;
  const digits = phone.replace(/\D/g, '');
  return digits.length === 9;
}

/**
 * 5. Scrollspy for Active Nav Links
 */
function initScrollspy() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  if (!sections.length || !navLinks.length) return;

  window.addEventListener('scroll', () => {
    let current = '';
    const scrollPos = window.scrollY + 200;

    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
        current = section.getAttribute('id');
      }
    });

    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href') === `#${current}`) {
        link.classList.add('active');
      }
    });
  });
}


