# Documentación Fase 3 - Base de Datos MySQL

## 1. Resumen de la Base de Datos

**Nombre de la base de datos:** `devioz_creative_studio`  
**Cotejamiento (Charset):** `utf8mb4_unicode_ci`  
**Motor de almacenamiento:** InnoDB  
**Ubicación del script SQL:** [`database/devioz_creative_studio.sql`](../database/devioz_creative_studio.sql)

La base de datos de **Devioz Creative Studio** gestiona los accesos administrativos, los servicios creativos ofertados, las categorías y proyectos del portafolio, las solicitudes recibidas desde el formulario de contacto y las configuraciones globales de la plataforma.

---

## 2. Estructura de Tablas y Esquema

### 2.1 Tabla `usuarios`
Guarda las credenciales y roles para el panel administrativo.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nombre` (VARCHAR 100, NOT NULL): Nombre del usuario.
- `email` (VARCHAR 150, UNIQUE, NOT NULL): Correo electrónico de acceso.
- `password` (VARCHAR 255, NOT NULL): Hash Bcrypt generado con `password_hash()` de PHP.
- `rol` (VARCHAR 50, DEFAULT 'admin'): Rol de acceso.
- `estado` (TINYINT, DEFAULT 1): 1 para Activo, 0 para Inactivo.
- `creado_en` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP): Fecha de registro.

### 2.2 Tabla `servicios`
Guarda la información de los servicios creativos ofrecidos.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `titulo` (VARCHAR 150, NOT NULL): Nombre del servicio.
- `slug` (VARCHAR 180, UNIQUE, NOT NULL): Identificador amigable para URL.
- `descripcion` (TEXT, NOT NULL): Descripción resumida.
- `imagen` (VARCHAR 255, NULL): Ruta del icono o imagen ilustrativa.
- `beneficios` (TEXT, NULL): Beneficios principales separados por pipe (`|`).
- `estado` (TINYINT, DEFAULT 1): 1 para Activo, 0 para Inactivo.
- `creado_en` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### 2.3 Tabla `categorias`
Guarda las categorías del portafolio.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nombre` (VARCHAR 100, NOT NULL): Nombre de la categoría (Diseño Gráfico, Video, Spots, etc.).
- `slug` (VARCHAR 120, UNIQUE, NOT NULL): Slug amigable.
- `estado` (TINYINT, DEFAULT 1): Status activo.
- `creado_en` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### 2.4 Tabla `proyectos`
Guarda los trabajos del portafolio vinculados a una categoría.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `categoria_id` (INT, NOT NULL): **Llave foránea** referenciada a `categorias.id`.
- `titulo` (VARCHAR 150, NOT NULL): Título del proyecto.
- `slug` (VARCHAR 180, UNIQUE, NOT NULL): Identificador amigable.
- `descripcion` (TEXT, NOT NULL): Descripción del proyecto.
- `imagen` (VARCHAR 255, NULL): Ruta de la imagen del proyecto.
- `tipo` (VARCHAR 80, NULL): Tipo de entregable.
- `cliente` (VARCHAR 120, NULL): Cliente o marca beneficiaria.
- `fecha` (DATE, NULL): Fecha de realización.
- `destacado` (TINYINT, DEFAULT 0): 1 si aparece destacado en el inicio.
- `estado` (TINYINT, DEFAULT 1): Status de visibilidad.
- `creado_en` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### 2.5 Tabla `contactos`
Almacena los mensajes enviados desde el formulario web.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nombre` (VARCHAR 120, NOT NULL): Nombre del remitente.
- `empresa` (VARCHAR 150, NULL): Nombre de la empresa o proyecto.
- `email` (VARCHAR 150, NOT NULL): Email de contacto.
- `telefono` (VARCHAR 40, NULL): Teléfono/WhatsApp.
- `servicio_interes` (VARCHAR 150, NOT NULL): Servicio consultado.
- `mensaje` (TEXT, NOT NULL): Detalle de la solicitud.
- `estado` (VARCHAR 50, DEFAULT 'nuevo'): Estado de atención ('nuevo', 'en_proceso', 'atendido', 'archivado').
- `creado_en` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### 2.6 Tabla `configuracion`
Almacena parámetros generales editables de la plataforma.
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `clave` (VARCHAR 100, UNIQUE, NOT NULL): Clave de configuración.
- `valor` (TEXT, NOT NULL): Valor del parámetro.

---

## 3. Relaciones (Foreign Keys)

- **`proyectos.categoria_id` &rarr; `categorias.id`**:
  - `CONSTRAINT fk_proyectos_categorias`
  - `ON DELETE CASCADE ON UPDATE CASCADE`
  - Garantiza la integridad referencial de los proyectos con su categoría correspondiente.

---

## 4. Datos Iniciales Incluidos (Seed Data)

El script SQL inserta automáticamente los siguientes datos de prueba:

1. **Usuario Administrador Inicial**:
   - **Email:** `admin@devioz.com`
   - **Contraseña:** `Admin123!` *(Almacenada con hash Bcrypt `password_hash()` de PHP)*
2. **5 Categorías**: `Diseño Gráfico`, `Video`, `Spots`, `Fotografía`, `Branding`.
3. **5 Servicios Principales**: Datos estructurados de la línea creativa.
4. **6 Proyectos del Portafolio**: Con rutas de imagen SVG locales vinculadas a sus respectivas categorías.
5. **Configuración Global**: Nombre de proyecto, email oficial (`contacto@devioz.com`), URL de Devioz (`https://devioz.com/`), sede y teléfono.

---

## 5. Guía de Importación en phpMyAdmin / XAMPP

1. Iniciar los servicios de **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. Abrir el navegador e ingresar a **phpMyAdmin**: `http://localhost/phpmyadmin/`.
3. Ir a la pestaña **Importar** (Import).
4. Hacer clic en **Seleccionar archivo** y elegir:
   `database/devioz_creative_studio.sql`
5. Presionar el botón **Continuar** (Go) en la parte inferior.
6. Verificar que la base de datos `devioz_creative_studio` se haya creado con sus 6 tablas y datos iniciales.

---

## 6. Pendientes para la Fase 4 (Backend API PHP)

En la **Fase 4** se implementará la capa del servidor Backend:
- Conexión a la base de datos PDO/MySQLi en `backend/config/database.php`.
- Endpoints API REST JSON en `backend/api/` (`services.php`, `portfolio.php`, `contact.php`).
- Conexión del frontend estático `frontend/assets/js/main.js` mediante peticiones `fetch()`.
