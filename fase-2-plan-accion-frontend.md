# Devioz Creative Studio - Fase 2: Plan De Accion Frontend

## Objetivo De La Fase 2

Crear el frontend publico independiente de `Devioz Creative Studio` usando HTML5, CSS3 y JavaScript. El frontend debe estar separado del backend y preparado para conectarse despues a endpoints PHP mediante `fetch`.

En esta fase no se debe usar HTML dentro de PHP. El archivo principal debe ser `index.html`, no `index.php`.

---

## Arquitectura Obligatoria

El proyecto debe separarse en dos capas:

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
        hero-creative-studio.png
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

Para la Fase 2 solo se trabaja dentro de:

```text
frontend/
```

La carpeta `backend/` queda reservada para fases posteriores.

---

## Separacion Frontend / Backend

Reglas obligatorias:

- El frontend debe ser HTML, CSS y JavaScript puro.
- El frontend no debe contener codigo PHP.
- El backend PHP no debe renderizar HTML en esta fase.
- En fases posteriores, PHP debe funcionar como API.
- El frontend consumira datos usando `fetch`.
- Los servicios, proyectos y contactos ahora pueden estar simulados en JavaScript o escritos directamente en HTML.
- Cuando llegue la fase backend, esos datos se reemplazaran por respuestas JSON desde PHP/MySQL.

Ejemplo futuro de conexion:

```js
fetch('../backend/api/services.php')
  .then((response) => response.json())
  .then((services) => {
    // Renderizar servicios en el frontend
  });
```

---

## Stack De Esta Fase

- HTML5
- CSS3
- JavaScript
- Sin PHP en la interfaz
- Sin MySQL
- Sin frameworks complejos

Archivo principal:

```text
frontend/index.html
```

---

## Paleta De Colores Obligatoria

Usar la paleta detectada desde la web oficial de Devioz:

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

Uso recomendado:

- Fondo principal: `#001a1a`
- Botones principales: `#00c2c2`
- Fondos secundarios oscuros: `#002b2b` y `#004d4d`
- Acentos: `#14b8a6` y `#2dd4bf`
- Secciones claras: `#f0fafa`, `#e6ffff`, `#ccfafa`
- Texto en fondos oscuros: `#ffffff`
- Texto secundario: `#9ca3af`

---

## Secciones Del Frontend

## 1. Header / Navegacion

Crear una barra superior fija o sticky.

Debe incluir:

- Nombre: `Devioz Creative Studio`
- Links internos:
  - Inicio
  - Servicios
  - Portafolio
  - Nosotros
  - Proceso
  - Contacto
- Boton destacado: `Solicitar cotizacion`
- Menu responsive para movil

Comportamiento:

- En desktop, navegacion horizontal.
- En movil, menu hamburguesa.
- Los links deben hacer scroll suave a cada seccion.

---

## 2. Hero Principal

Debe presentar de inmediato la identidad del proyecto.

Texto principal:

```text
Devioz Creative Studio
```

Subtitulo:

```text
Diseno, video y contenido visual para marcas que quieren verse profesionales.
```

Texto de apoyo:

```text
Creamos piezas graficas, contenido audiovisual, spots publicitarios y fotografia profesional con una mirada estrategica, moderna y alineada a la identidad de tu marca.
```

Botones:

- `Solicitar cotizacion`
- `Ver portafolio`

Requisitos visuales:

- Fondo oscuro.
- Imagen hero o fondo visual creativo.
- Buen contraste de texto.
- Estilo premium, tecnologico y creativo.

---

## 3. Servicios

Mostrar 5 servicios en una grilla responsive:

1. Diseno grafico profesional
2. Produccion de videos
3. Spots publicitarios
4. Fotografia profesional
5. Contenido visual especializado

Cada tarjeta debe tener:

- Titulo
- Descripcion corta
- Icono o recurso visual
- Beneficios breves
- Link o boton: `Quiero este servicio`

---

## 4. Portafolio

Crear una galeria visual con filtros en JavaScript.

Categorias:

- Todos
- Diseno grafico
- Video
- Spots
- Fotografia
- Branding

Cada proyecto debe tener:

- Imagen o placeholder visual
- Categoria
- Titulo
- Descripcion corta

Proyectos simulados sugeridos:

- Campana visual para redes sociales
- Spot promocional de lanzamiento
- Fotografia corporativa de equipo
- Branding para marca emergente
- Video institucional empresarial
- Piezas graficas para campana digital

Comportamiento:

- Botones con `data-filter`
- Items con `data-category`
- El filtro `Todos` muestra todo.
- Cada categoria muestra solo sus proyectos.
- No debe recargar la pagina.

---

## 5. Nosotros

Explicar la relacion con Devioz.

Texto sugerido:

```text
Somos la linea creativa de Devioz, especializada en crear piezas visuales, contenido audiovisual y experiencias graficas para marcas que buscan comunicar con impacto.
```

Debe comunicar:

- Pertenencia al ecosistema Devioz.
- Union entre tecnologia, diseno y comunicacion visual.
- Enfoque profesional para empresas y emprendedores.

---

## 6. Proceso De Trabajo

Mostrar los 5 pasos del proceso creativo:

1. Brief del cliente
2. Propuesta creativa
3. Produccion
4. Revision
5. Entrega final

Cada paso debe incluir numero, titulo y descripcion corta.

---

## 7. Contacto / Cotizacion

Crear un formulario visual. En esta fase no debe enviar datos al backend todavia.

Campos:

- Nombre
- Empresa
- Correo
- Telefono
- Servicio de interes
- Mensaje

Validacion frontend:

- Nombre obligatorio
- Correo obligatorio
- Correo con formato valido
- Servicio obligatorio
- Mensaje obligatorio

Al enviar correctamente:

```text
Solicitud preparada. En la siguiente fase conectaremos este formulario con el backend PHP y MySQL.
```

---

## 8. Footer

Debe incluir:

- `Devioz Creative Studio`
- Descripcion corta
- Links rapidos
- Enlace a `https://devioz.com/`
- Copyright

Texto sugerido:

```text
Devioz Creative Studio, una extension creativa de Devioz para diseno, video y contenido visual profesional.
```

---

## Tareas Para La IA Desarrolladora

## Tarea 1 - Crear Estructura Frontend

Crear:

```text
frontend/index.html
frontend/assets/css/style.css
frontend/assets/js/main.js
frontend/assets/img/
frontend/assets/img/portfolio/
frontend/assets/img/services/
```

Criterios de aceptacion:

- El frontend abre directamente desde `frontend/index.html`.
- `index.html` enlaza correctamente `assets/css/style.css`.
- `index.html` enlaza correctamente `assets/js/main.js`.
- No existe codigo PHP dentro del frontend.

---

## Tarea 2 - Crear HTML Semantico

En `frontend/index.html`, crear:

```html
<header></header>
<main>
  <section id="inicio"></section>
  <section id="servicios"></section>
  <section id="portafolio"></section>
  <section id="nosotros"></section>
  <section id="proceso"></section>
  <section id="contacto"></section>
</main>
<footer></footer>
```

Criterios de aceptacion:

- Todas las secciones existen.
- Los links del menu apuntan a IDs internos.
- La pagina menciona `Devioz Creative Studio`.

---

## Tarea 3 - Crear Sistema Visual CSS

En `frontend/assets/css/style.css`, definir:

- Variables de color Devioz.
- Reset basico.
- Tipografia.
- Contenedores.
- Botones.
- Tarjetas.
- Grillas responsive.
- Estados hover/focus.

Criterios de aceptacion:

- La web usa la paleta Devioz.
- No hay texto ilegible.
- La interfaz se siente tecnologica, creativa y profesional.

---

## Tarea 4 - Construir Hero

Crear el hero con:

- Nombre del proyecto.
- Subtitulo.
- Texto de apoyo.
- Dos botones.
- Imagen o fondo visual creativo.

Criterios de aceptacion:

- El primer pantallazo comunica claramente el servicio.
- Los botones principales son visibles.
- En movil no hay superposicion de textos.

---

## Tarea 5 - Construir Servicios

Crear una grilla con los 5 servicios principales.

Criterios de aceptacion:

- Cada tarjeta tiene titulo, descripcion y beneficios.
- En desktop se ve en grilla.
- En movil se apila correctamente.

---

## Tarea 6 - Construir Portafolio Con Filtros

Crear minimo 6 proyectos simulados y filtros con JavaScript.

Requisitos JS:

```js
const filterButtons = document.querySelectorAll('[data-filter]');
const portfolioItems = document.querySelectorAll('[data-category]');
```

Criterios de aceptacion:

- `Todos` muestra todos los proyectos.
- Cada filtro muestra solo su categoria.
- No hay recarga de pagina.

---

## Tarea 7 - Construir Nosotros Y Proceso

Crear las secciones `Nosotros` y `Proceso`.

Criterios de aceptacion:

- El texto deja clara la conexion con Devioz.
- El proceso tiene 5 pasos.
- La seccion conserva la identidad visual.

---

## Tarea 8 - Construir Formulario Con Validacion

Crear formulario con validacion JavaScript.

Requisitos:

- Evitar envio si faltan campos obligatorios.
- Validar formato de correo.
- Mostrar mensaje de error.
- Mostrar mensaje de exito simulado.
- No enviar a PHP todavia.

Criterios de aceptacion:

- Formulario vacio muestra error.
- Email invalido muestra error.
- Datos validos muestran confirmacion.

---

## Tarea 9 - Responsive Design

Agregar media queries para:

- Desktop
- Tablet
- Movil

Revisar:

- Header
- Hero
- Servicios
- Portafolio
- Contacto

Criterios de aceptacion:

- Se ve bien en 1366px, 1024px, 768px y 390px.
- No hay scroll horizontal.
- No hay textos montados.

---

## Tarea 10 - Validacion Final

Antes de entregar, comprobar:

- `frontend/index.html` abre correctamente.
- No hay errores en consola.
- Los links del menu funcionan.
- Los filtros del portafolio funcionan.
- El formulario valida correctamente.
- El frontend no contiene PHP.
- La carpeta backend no se usa en esta fase.

---

## Resultado Esperado

Al finalizar la Fase 2 debe existir un frontend publico completo, visualmente profesional y responsive, ubicado en `frontend/`, sin PHP mezclado con HTML.

El backend PHP y la base de datos MySQL se implementaran en fases posteriores como una capa separada.

---

## Prompt Para Darle A Otra IA

```text
Necesito que desarrolles la Fase 2 del proyecto Devioz Creative Studio.

El proyecto es una aplicacion web para una linea creativa de Devioz enfocada en diseno grafico, produccion de videos, spots publicitarios, fotografia profesional y contenido visual especializado.

En esta fase debes construir solo el frontend publico usando HTML5, CSS3 y JavaScript.

Importante: el HTML debe ser independiente. No uses HTML dentro de PHP. No uses index.php. El archivo principal debe ser frontend/index.html.

El proyecto debe quedar separado por arquitectura:
- frontend/ para HTML, CSS, JavaScript e imagenes
- backend/ para la API PHP que se creara en fases posteriores
- database/ para scripts SQL posteriores
- docs/ para documentacion

Crea esta estructura:
- frontend/index.html
- frontend/assets/css/style.css
- frontend/assets/js/main.js
- frontend/assets/img/
- frontend/assets/img/portfolio/
- frontend/assets/img/services/

La web debe tener estas secciones:
- Header con navegacion
- Inicio / Hero
- Servicios
- Portafolio con filtros usando JavaScript
- Nosotros
- Proceso de trabajo
- Contacto / Cotizacion con validacion frontend
- Footer

Usa esta paleta basada en Devioz:
- #00c2c2 color principal
- #001a1a fondo oscuro principal
- #002b2b fondo oscuro secundario
- #004d4d verde petroleo
- #14b8a6 color secundario
- #2dd4bf acento claro
- #f0fafa, #e6ffff y #ccfafa fondos claros
- #ffffff texto en fondos oscuros
- #9ca3af texto secundario

El resultado debe verse moderno, tecnologico, creativo y profesional. Debe ser responsive para desktop, tablet y movil.

El formulario debe validar campos con JavaScript, pero todavia no debe enviar datos a PHP ni guardar en base de datos.

Entrega el frontend listo para abrir desde frontend/index.html y deja preparado el codigo para que en una fase posterior pueda consumir endpoints PHP con fetch.
```
