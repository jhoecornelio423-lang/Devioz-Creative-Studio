# Documentación Fase 6 - Validación, Seguridad y Optimización

## 1. Resumen Ejecutivo de la Fase 6

La **Fase 6** de **Devioz Creative Studio** consolida la robustez, estabilidad y seguridad del sistema sin alterar la arquitectura desacoplada establecida (Frontend HTML/CSS/JS independiente, Backend API REST PHP y Panel Administrativo modular).

En esta fase se implementaron mecanismos de defensa en profundidad:
- Protección contra ataques **CSRF (Cross-Site Request Forgery)** en todos los formularios y operaciones de estado.
- Conversión de **todas las acciones sensibles de GET a POST**.
- Política de **desactivación lógica preferida** (`estado = 0`) para preservar la integridad referencial y el historial de datos.
- Blindaje contra filtración de información técnica: **eliminación de `$e->getMessage()` hacia usuarios y clientes**, redirigiendo excepciones a `error_log()` interno.
- Configuración reforzada de **sesiones PHP**, soporte de fallback para directorios temporales en entornos Windows/XAMPP y prevención de **Session Fixation** mediante `session_regenerate_id(true)`.
- Compatibilidad de caracteres UTF-8 (`SET NAMES utf8mb4`) e indexación de columnas críticas en MySQL.
- Validaciones estrictas de servidor para slugs, fechas, longitudes máximas y existencia de claves foráneas.

---

## 2. Cambios de Seguridad Aplicados

### 2.1 Gestión y Protección CSRF (Cross-Site Request Forgery)
- **Archivos creados:**
  - [`admin/includes/session.php`](../admin/includes/session.php): Administrador centralizado de sesiones con configuración de cookies segura (`httponly`, `samesite=Lax`, `use_strict_mode`) y ruta de almacenamiento dedicada del proyecto en `admin/storage/sessions/` con protección `.htaccess`, erradicando por completo el fallo de permisos con `C:\xampp\tmp`.
  - [`admin/includes/csrf.php`](../admin/includes/csrf.php): Generación criptográficamente segura de tokens y validación estricta contra manipulaciones en peticiones entrantes.
- **Mecanismo de funcionamiento:**
  1. **Generación:** Al inicializar la sesión o autenticarse, `getCsrfToken()` genera una cadena pseudoaleatoria de 32 bytes criptográficos (`random_bytes(32)`) codificada en hexadecimal (64 caracteres) y la almacena en `$_SESSION['csrf_token']`.
  2. **Inyección en Formulario:** El helper `csrfField()` inserta un campo oculto `<input type="hidden" name="csrf_token" value="...">` en todos los formularios del panel.
  3. **Validación:** En el controlador destino, antes de ejecutar cualquier mutación, `validateCsrfToken()` compara el token enviado en `$_POST['csrf_token']` contra `$_SESSION['csrf_token']` utilizando `hash_equals()` para prevenir ataques de temporización (timing attacks).
  4. **Rechazo:** Si el token falta, está vacío o no coincide exactamente, la petición es abortada de inmediato y se muestra una alerta de seguridad al usuario.

---

### 2.2 Migración de Acciones Sensibles: De GET a POST

Previamente, ciertas acciones de mutación de base de datos se invocaban mediante parámetros en URLs GET (por ejemplo, `servicios.php?action=toggle&id=1` o `contactos.php?action=update_status&id=1&status=atendido`). Esto exponía al sistema a ejecuciones accidentales, rastreo de enlaces por bots o ataques CSRF mediante imágenes incrustadas.

Todas las acciones sensibles se han transformado a **formularios y botones POST protegidos con CSRF**:

| Módulo | Acción Antigua (Insegura GET) | Acción Nueva (Segura POST + CSRF) | Tipo de Operación |
| :--- | :--- | :--- | :--- |
| **Login** | POST sin token | POST + CSRF + `session_regenerate_id(true)` | Autenticación |
| **Servicios** | `GET ?action=toggle&id=X` | `POST action=toggle, id=X, csrf_token` | Alternar Activo / Inactivo |
| **Servicios** | `GET ?action=delete&id=X` | `POST action=delete, id=X, csrf_token, confirm` | Eliminación con confirmación |
| **Categorías** | `GET ?action=toggle&id=X` | `POST action=toggle, id=X, csrf_token` | Alternar Activo / Inactivo |
| **Categorías** | `GET ?action=delete&id=X` | `POST action=delete, id=X, csrf_token, confirm` | Eliminación prevenida si hay proyectos |
| **Portafolio** | `GET ?action=toggle&id=X` | `POST action=toggle, id=X, csrf_token` | Alternar Activo / Inactivo |
| **Portafolio** | `GET ?action=toggle_featured&id=X` | `POST action=toggle_featured, id=X, csrf_token` | Alternar Destacado (★) |
| **Portafolio** | `GET ?action=delete&id=X` | `POST action=delete, id=X, csrf_token, confirm` | Eliminación con confirmación |
| **Contactos** | `GET ?action=update_status&id=X&status=Y` | `POST action=update_status, id=X, status=Y, csrf_token` | Cambio de estado de seguimiento |

> [!NOTE]
> Las peticiones GET a los archivos administrativos ahora se reservan **estrictamente para lectura**: listar registros y abrir formularios de edición (`action=edit` o `action=create`). Cualquier intento de enviar parámetros de mutación por GET es ignorado.

---

### 2.3 Política de Eliminación y Desactivación Lógica
- **Servicios, Categorías y Proyectos:** En la interfaz se priorizan los botones de **"Desactivar" / "Activar"** (`estado = 0`), permitiendo retirar elementos del sitio público sin romper relaciones de base de datos. Si se requiere eliminación física, se exige método POST, token CSRF válido y confirmación explícita mediante diálogo de advertencia en el navegador.
- **Categorías con Proyectos Asociados:** Se implementó una verificación previa: si una categoría tiene proyectos vinculados en la tabla `proyectos`, el sistema **bloquea la eliminación** y sugiere al administrador desactivarla o reasignar los proyectos.
- **Contactos:** No existe opción de eliminación física en la interfaz ni en el controlador de `contactos.php`. Las solicitudes solo pueden cambiar de estado (`nuevo`, `en_proceso`, `atendido`, `archivado`), preservando el historial comercial de la agencia.

---

### 2.4 Manejo Seguro de Excepciones y Enmascaramiento de Errores
Se erradicó completamente la exposición de `$e->getMessage()` hacia clientes web y respuestas JSON:
- **En la API pública (`backend/api/*.php` y `backend/config/database.php`):** Ante fallas de conexión o sentencias SQL, los endpoints devuelven un código HTTP 500 y un mensaje amigable genérico (`"Error interno: No se pudo establecer conexión con la base de datos."`). El detalle técnico se registra únicamente en el registro interno del servidor con `error_log("[Devioz ...] " . $e->getMessage())`.
- **En el Panel Administrativo (`admin/*.php`):** Se capturan las excepciones mostrando avisos legibles al administrador en lugar de volcados de base de datos o rutas de carpetas del servidor.

---

### 2.5 Seguridad en Sesiones PHP (`admin/includes/session.php`)
- **Regeneración tras Login:** En `login.php`, al comprobar credenciales válidas con `password_verify()`, se invoca `session_regenerate_id(true)` para destruir el identificador de sesión anterior y emitir uno nuevo, mitigando ataques de fijación de sesión.
- **Cierre Limpio:** En `logout.php`, se vacía `$_SESSION = []`, se elimina la cookie de sesión con parámetros de caducidad en el pasado (`time() - 42000`) y se invoca `session_destroy()`.
- **Cookie Security:** Cookies con directivas `httponly = true` (inaccesibles vía JavaScript contra ataques XSS) y `samesite = 'Lax'`.
- **Ruta de Almacenamiento Propia (`admin/storage/sessions/`):** Asignación obligatoria previa a `session_start()` de una ruta de sesiones interna del proyecto con permisos garantizados, eliminando la dependencia y los errores de permisos con `C:\xampp\tmp`. Incluye fallback seguro y archivo `.htaccess` (`Require all denied`).

---

## 3. Validaciones Agregadas (`backend/helpers/validation.php`)

Se introdujeron funciones de validación reutilizables en [`backend/helpers/validation.php`](../backend/helpers/validation.php):

1. **`isValidSlug($slug, $maxLength = 180)`:**
   - Valida que el slug cumpla con la expresión regular `/^[a-z0-9]+(?:-[a-z0-9]+)*$/` (solo caracteres alfanuméricos en minúsculas y guiones sencillos).
   - Impide espacios, caracteres especiales y guiones dobles.
2. **`validateMaxLength($value, $maxLength)`:**
   - Verifica la longitud máxima en caracteres UTF-8 usando `mb_strlen()`.
   - Aplicado a títulos (150), nombres (100), clientes (120), rutas de imagen (255) y descripciones (5000).
3. **`isValidDate($dateStr, $format = 'Y-m-d')`:**
   - Comprueba la correspondencia con el formato `AAAA-MM-DD` y verifica mediante `DateTime::createFromFormat` que sea una fecha válida del calendario (rechazando fechas inexistentes como `2026-02-30`).
4. **`isValidStatus($status, array $allowed)`:**
   - Valida que el estado coincida con los dominios permitidos (`[0, 1]` para catálogos y `['nuevo', 'en_proceso', 'atendido', 'archivado']` para contactos).
5. **`categoryExists($pdo, $categoryId)`:**
   - Consulta si el `categoria_id` seleccionado en proyectos existe efectivamente en la tabla `categorias`, previniendo errores de clave foránea o datos huérfanos.

---

## 4. Optimización de Base de Datos y Rendimiento

### 4.1 Script SQL Oficial Actualizado
En [`database/devioz_creative_studio.sql`](../database/devioz_creative_studio.sql) se agregaron las siguientes instrucciones:
```sql
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
```

### 4.2 Nuevos Índices Aplicados
Para acelerar las consultas más frecuentes de la API y del panel administrativo (filtros de visibilidad, ordenamiento cronológico y joins):
- **`servicios`:** `KEY idx_servicios_estado (estado)`
- **`categorias`:** `KEY idx_categorias_estado (estado)`
- **`proyectos`:** `KEY idx_proyectos_categoria (categoria_id)`, `KEY idx_proyectos_estado (estado)`, `KEY idx_proyectos_destacado (destacado)`
- **`contactos`:** `KEY idx_contactos_estado (estado)`, `KEY idx_contactos_creado_en (creado_en)`

---

## 5. Pruebas y Validación Ejecutadas

Se ejecutó una batería de validaciones automáticas y manuales con **100% de éxito**:

1. **Sintaxis PHP (`php -l`):**
   - Ejecutado sobre los 22 archivos PHP del proyecto. Resultado: **0 errores de sintaxis**.
2. **Autenticación y Sesiones:**
   - Login correcto con credenciales de prueba (`admin@devioz.com` / `Admin123!`).
   - Verificación de regeneración de ID de sesión tras login.
   - Rechazo de contraseñas inválidas.
   - Destrucción completa de sesión y eliminación de cookie en `logout.php`.
3. **Protección CSRF:**
   - Comprobación de generación de tokens criptográficos de 64 caracteres.
   - Verificación de rechazo inmediato de peticiones POST si el token es omitido o manipulado.
   - Aprobación de peticiones con token válido.
4. **Bloqueo de Mutaciones GET:**
   - Confirmado que enviar `?action=toggle`, `?action=delete` o `?action=update_status` vía GET **no altera** los registros de la base de datos.
5. **CRUD y Desactivación Lógica:**
   - Pruebas de alternancia de estado (`estado = 0` / `estado = 1`) en servicios, categorías y proyectos.
   - Confirmación de prevención de borrado para categorías con proyectos vinculados.
   - Confirmación de actualización de estados de contactos (`nuevo` &rarr; `en_proceso` &rarr; `atendido` &rarr; `archivado`) sin borrado físico.
6. **Integridad de Endpoints API:**
   - Verificación de respuestas JSON (`success: true`) para `/backend/api/services.php`, `/backend/api/categories.php`, `/backend/api/portfolio.php` y `/backend/api/config.php`.
   - Verificación de inserción validada y estructurada para `/backend/api/contact.php`.
   - Confirmación de que las respuestas de error ante datos inválidos no exponen excepciones del motor de base de datos.
7. **Frontend Público:**
   - El frontend desacoplado en `frontend/` se mantiene íntegro y funcional, listo para consumir la API.

---

## 6. Recomendaciones Pendientes para Entorno de Producción

1. **Certificado SSL / HTTPS:**
   - En el entorno de producción, forzar HTTPS y activar la directiva `session.cookie_secure = true` para garantizar que las cookies de sesión y tokens CSRF solo viajen por canales cifrados.
2. **Variables de Entorno para Credenciales de BD:**
   - Extraer las credenciales de conexión (`DB_USER`, `DB_PASS`, `DB_HOST`) de [`backend/config/database.php`](../backend/config/database.php) hacia un archivo `.env` fuera del DocumentRoot público.
3. **Paginación en Tablas Extensas:**
   - Para tablas con cientos de registros (especialmente en `contactos`), implementar paginación basada en `LIMIT` y `OFFSET`.
4. **Límite de Tasa de Peticiones (Rate Limiting):**
   - Implementar control de peticiones en el endpoint público `backend/api/contact.php` y en `admin/login.php` (ej. máximo 5 intentos fallidos de login por IP cada 15 minutos) para mitigar ataques de fuerza bruta.
5. **Subida Directa de Archivos:**
   - Implementar un módulo de carga de imágenes con validación MIME real (`finfo_file`), reescalado de imágenes y almacenamiento en carpeta dedicada con ejecución de scripts deshabilitada (`.htaccess` o directiva de servidor web).
