# Devioz Creative Studio - Documentación Por Fases

## 1. Resumen Del Proyecto

**Nombre del proyecto:** Devioz Creative Studio

**Tipo de proyecto:** Aplicación web profesional para una agencia creativa vinculada a Devioz.

**Propósito:** Crear una extensión digital especializada de Devioz, enfocada en diseño gráfico, producción audiovisual, spots publicitarios, fotografía profesional y contenido visual para empresas.

**Objetivo principal:** Permitir que clientes potenciales conozcan, exploren y soliciten los servicios creativos de Devioz de forma directa, visual y profesional.

**Referencia corporativa:** La web debe sentirse como parte del ecosistema Devioz, usando su identidad visual, tono tecnológico y enfoque profesional.

Fuentes de referencia:

- Web oficial: https://devioz.com/
- LinkedIn: https://pe.linkedin.com/company/devioz-ti

---

## 2. Identidad Visual

La aplicación debe usar una identidad visual alineada con Devioz. Según el CSS público de la web oficial, los colores principales detectados son:

```css
:root {
  --devioz-primary: #00c2c2;
  --devioz-dark: #001a1a;
  --devioz-dark-secondary: #002b2b;
  --devioz-teal-deep: #004d4d;
  --devioz-teal: #14b8a6;
  --devioz-teal-light: #2dd4bf;
  --devioz-bg-soft: #f0fafa;
  --devioz-bg-light: #e6ffff;
  --devioz-bg-muted: #ccfafa;
  --devioz-white: #ffffff;
  --devioz-gray: #9ca3af;
}
```

### Uso recomendado de colores

- Fondo principal oscuro: `#001a1a`
- Color principal de botones y acentos: `#00c2c2`
- Secciones oscuras secundarias: `#002b2b` o `#004d4d`
- Secciones claras: `#f0fafa`, `#e6ffff` o `#ccfafa`
- Texto sobre fondo oscuro: `#ffffff`
- Texto secundario: `#9ca3af`

### Estilo visual esperado

La web debe sentirse moderna, tecnológica, creativa y profesional. Debe evitar un estilo demasiado genérico. El portafolio debe tener protagonismo visual, ya que el proyecto representa una línea creativa.

---

## 3. Arquitectura y Stack Tecnológico

El proyecto está diseñado bajo una **arquitectura desacoplada** separando claramente la capa de presentación (Frontend) de la capa de servicios (Backend):

```text
devioz-creative-studio/
  frontend/
    index.html
    assets/
      css/
        style.css
      js/
        main.js
      img/
        portfolio/
        services/
  backend/
    api/
    config/
    controllers/
    models/
  database/
  docs/
```

### Especificaciones por Capa:

- **Frontend (`frontend/`):** HTML5, CSS3 y JavaScript cliente puro. No contiene código PHP ni renderizado dinámico del lado del servidor. El punto de entrada es `frontend/index.html`.
- **Backend (`backend/`):** PHP estructurado funcionando como API REST (endpoints JSON) que se conectará mediante peticiones `fetch()` en fases posteriores.
- **Base de Datos (`database/`):** Base de datos MySQL e instrucciones/scripts SQL de la estructura de tablas.
- **Documentación (`docs/`):** Especificaciones de arquitectura, guías de desarrollo y manuales.

---

## 4. Público Objetivo

La web está dirigida a:

- Empresas que necesitan diseño gráfico profesional.
- Emprendedores que quieren mejorar su imagen visual.
- Marcas que necesitan videos, spots o contenido para redes sociales.
- Clientes actuales o potenciales de Devioz que también requieren servicios creativos.

---

## 5. Servicios A Mostrar

La aplicación debe mostrar los siguientes servicios principales:

1. Diseño gráfico profesional
2. Producción de videos
3. Spots publicitarios
4. Fotografía profesional
5. Contenido visual especializado

Cada servicio debe tener:

- Nombre
- Descripción corta
- Imagen o recurso visual
- Beneficios principales
- Botón para solicitar información

---

## 6. Estructura General De La Web Pública

### 6.1 Inicio

Debe presentar el nombre `Devioz Creative Studio` con una frase principal clara.

Frase sugerida:

> Diseño, video y contenido visual para marcas que quieren verse profesionales.

Elementos requeridos:

- Header con logo/nombre
- Menú de navegación
- Hero visual
- Botón principal: "Solicitar cotización"
- Botón secundario: "Ver portafolio"

### 6.2 Servicios

Debe mostrar los servicios creativos en tarjetas visuales. Cada tarjeta debe incluir icono o imagen, título, descripción y llamada a la acción.

### 6.3 Portafolio

Debe mostrar trabajos o ejemplos visuales organizados por categoría.

Categorías sugeridas:

- Diseño gráfico
- Videos
- Spots
- Fotografía
- Branding

En la Fase 2, el filtro se gestiona en cliente con JavaScript sin recarga de página. En la Fase 4, estos proyectos se consumirán desde la API backend.

### 6.4 Nosotros

Debe explicar que Devioz Creative Studio es una extensión creativa de Devioz, enfocada en unir tecnología, diseño y comunicación visual.

Texto sugerido:

> Somos la línea creativa de Devioz, especializada en crear piezas visuales, contenido audiovisual y experiencias gráficas para marcas que buscan comunicar con impacto.

### 6.5 Proceso De Trabajo

Debe mostrar el proceso creativo en 5 pasos:

1. Brief del cliente
2. Propuesta creativa
3. Producción
4. Revisión
5. Entrega final

### 6.6 Contacto / Cotización

Debe incluir un formulario con:

- Nombre
- Empresa
- Correo
- Teléfono
- Servicio de interés
- Mensaje

**Comportamiento por Fases:**
- **Fase 2 (Frontend):** El formulario valida los campos requeridos y el formato de email en JavaScript cliente, mostrando un mensaje simulado de confirmación.
- **Fase 4 (Backend):** El formulario enviará los datos vía `fetch()` a la API en PHP para guardarlos de forma segura en MySQL.

---

## 7. Panel Administrativo (Fases Posteriores)

El proyecto incluirá en fases avanzadas un panel privado para administrar el contenido principal consumiendo la API PHP.

---

## 8. Fases De Implementación

### Fase 1 - Definición Y Diseño Del Proyecto
Definición de alcance, paleta de colores y arquitectura desacoplada.

### Fase 2 - Maquetación Frontend Independiente
Creación de la interfaz de usuario pública cliente en `frontend/index.html`, `frontend/assets/css/style.css` y `frontend/assets/js/main.js`. Filtros cliente y validación de formulario simulada.

### Fase 3 - Base De Datos MySQL
Diseño e implementación de tablas (`usuarios`, `servicios`, `categorias`, `proyectos`, `contactos`) y scripts SQL en `database/`.

### Fase 4 - Backend API PHP
Desarrollo de los endpoints API JSON en `backend/api/` y conexión del frontend mediante `fetch()`.

### Fase 5 - Panel Administrativo
Desarrollo del panel de administración desacoplado con autenticación.

### Fase 6 - Validación, Seguridad Y Optimización (Completada)
Protección CSRF en panel admin, conversión de acciones sensibles GET a POST, desactivación lógica preferida, enmascaramiento de errores sin exponer `$e->getMessage()`, refuerzo de sesiones PHP, SET NAMES utf8mb4 e indexación en base de datos. Documentación detallada en [`docs/fase-6-seguridad-optimizacion.md`](docs/fase-6-seguridad-optimizacion.md).

### Fase 7 - Entrega Y Documentación Final (Completada)
Entrega del proyecto completo, guía de instalación y despliegue en XAMPP ([`docs/guia-instalacion-xampp.md`](docs/guia-instalacion-xampp.md)), manual de entrega final ([`docs/fase-7-entrega-final.md`](docs/fase-7-entrega-final.md)), README principal ([`README.md`](README.md)) y validación técnica final.
