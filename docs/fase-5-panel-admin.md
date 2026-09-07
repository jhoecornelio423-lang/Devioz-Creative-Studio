# Documentación Fase 5 - Panel Administrativo

## 1. Resumen del Panel Administrativo

El Panel Administrativo de **Devioz Creative Studio** proporciona un entorno privado y seguro para gestionar el contenido dinámico de la web pública (Servicios, Categorías, Proyectos del Portafolio) y administrar las solicitudes de cotización recibidas desde el formulario de contacto.

- **Ubicación del código:** [`admin/`](../admin/)
- **URL de acceso local (PHP Dev Server):** `http://localhost:8088/admin/login.php`
- **URL de acceso local (XAMPP):** `http://localhost/Devioz/admin/login.php`
- **Estilo visual:** Tema oscuro alineado a la paleta oficial de Devioz (`#00c2c2`, `#001a1a`, `#002b2b`, `#004d4d`, `#14b8a6`, `#ffffff`).

---

## 2. Credenciales Iniciales de Prueba

Para ingresar al panel en entorno de desarrollo:

- **Correo Electrónico:** `admin@devioz.com`
- **Contraseña:** `Admin123!`
- **Verificación:** Autenticación segura mediante `password_verify()` comparando contra el hash Bcrypt en la tabla `usuarios`.

---

## 3. Estructura de Archivos del Panel Admin

```text
admin/
├── login.php          (Controlador y vista pública de inicio de sesión)
├── dashboard.php      (Métricas clave y solicitudes recientes)
├── servicios.php      (CRUD completo de Servicios Creativos)
├── categorias.php     (CRUD completo de Categorías del Portafolio)
├── proyectos.php      (CRUD completo de Proyectos del Portafolio)
├── contactos.php      (Gestión y cambio de estado de mensajes de cotización)
├── logout.php         (Destrucción de sesión y redirección)
├── assets/
│   ├── css/
│   │   └── admin.css  (Sistema de estilos visuales del panel)
│   └── js/
│       └── admin.js   (Generador automático de slugs y utilidades JS)
└── includes/
    ├── auth.php       (Middleware para protección de rutas privadas)
    ├── header.php     (Cabecera HTML y Topbar con usuario activo)
    ├── sidebar.php    (Menú lateral de navegación)
    └── footer.php     (Pie de página del layout admin)
```

---

## 4. Funcionalidades del Panel

### 4.1 Autenticación y Protección de Rutas (`login.php`, `auth.php`, `logout.php`)
- **Login Seguro:** Valida credenciales contra MySQL y crea la variable de sesión `$_SESSION['admin_user']`.
- **Protección de Rutas:** El middleware `auth.php` verifica la sesión activa al cargar cualquier sección administrativa. Si no existe sesión, redirige automáticamente a `login.php`.
- **Cierre de Sesión:** El archivo `logout.php` limpia las variables de sesión y redirige de forma segura.

### 4.2 Dashboard (`dashboard.php`)
Muestra un resumen cuantitativo en tiempo real:
- Total de servicios activos.
- Total de categorías.
- Total de proyectos en portafolio.
- Mensajes de contacto nuevos pendientes de atención.
- Tabla con las 5 solicitudes de cotización más recientes.

### 4.3 CRUD de Servicios (`servicios.php`)
- **Listar:** Muestra todos los servicios registrados.
- **Crear:** Formulario para agregar un servicio (título, slug, descripción, ruta de imagen, beneficios separados por `|`).
- **Editar:** Modificación de campos y beneficios de servicios existentes.
- **Cambiar Estado:** Alterna visibilidad pública (`Activo` / `Inactivo`).
- **Eliminar:** Eliminación segura mediante confirmación.

### 4.4 CRUD de Categorías (`categorias.php`)
- **Listar y Crear:** Registro de nuevas categorías validando que el `slug` sea único.
- **Editar:** Actualización de nombre y slug de categoría.
- **Cambiar Estado:** Alterna visibilidad.

### 4.5 CRUD de Proyectos (`proyectos.php`)
- **Listar:** Muestra proyectos con su categoría vinculada (`JOIN categorias`).
- **Crear y Editar:** Formulario completo (selección de categoría mediante menú desplegable, cliente, tipo, fecha, imagen y descripción).
- **Destacar:** Alterna la bandera `destacado` (1/0) para destacar proyectos principales.
- **Estado:** Alterna entre activo e inactivo.

### 4.6 Gestión de Contactos (`contactos.php`)
- **Listar:** Muestra los mensajes recibidos desde el formulario web.
- **Ver Detalle:** Muestra el contenido completo de la solicitud y actualiza automáticamente las solicitudes nuevas a estado `en_proceso`.
- **Actualizar Estado:** Permite cambiar el estado a `nuevo`, `en_proceso`, `atendido` o `archivado`.

---

## 5. Pendientes para la Fase 6 (Validación, Seguridad y Optimización)

En la **Fase 6** se realizarán las siguientes mejoras:
1. Paginación de tablas extensas en el panel administrativo.
2. Carga directa de imágenes mediante subida de archivos (File Upload PHP) con validación de extensión mime.
3. Sanitización adicional y protección CSRF mediante tokens en formularios POST.
4. Optimización de imágenes y caché del sitio público.
