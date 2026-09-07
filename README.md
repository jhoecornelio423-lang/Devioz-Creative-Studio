# Devioz Creative Studio

Plataforma web profesional para la división creativa y de marketing digital de **Devioz**, orientada a diseño gráfico, producción audiovisual, spots publicitarios, fotografía profesional y contenido visual corporativo.

El sistema está diseñado bajo una **arquitectura desacoplada**: interfaz de usuario independiente (Frontend HTML5/CSS3/JavaScript), API REST en PHP orientada a servicios JSON (Backend) y un Panel de Control Administrativo seguro para la gestión dinámica de contenidos y cotizaciones.

---

## 1. Stack Tecnológico

* **Frontend:** HTML5 semántico, CSS3 modular (Variables nativas, Flexbox, CSS Grid), JavaScript Vanilla (ES6+, sin dependencias externas).
* **Backend:** PHP 8.x puro, arquitectura orientada a servicios REST, respuestas `application/json`, PDO con *Prepared Statements*.
* **Base de Datos:** MySQL 5.7+ / 8.0 / MariaDB 10.4+ (XAMPP). Codificación nativa `utf8mb4_unicode_ci`.
* **Seguridad:** Tokens CSRF criptográficos (`random_bytes`), cookies de sesión blindadas (`httponly`, `samesite=Lax`, `use_strict_mode`), regeneración de sesión (`session_regenerate_id`), contraseñas hasheadas con Bcrypt (`password_hash`), validaciones estrictas en servidor y enmascaramiento de excepciones de base de datos.
* **Entorno Local:** XAMPP (Apache + MySQL/MariaDB) o servidor embebido de PHP (`php -S`).

---

## 2. Estructura del Proyecto

```text
Devioz/
├── admin/                         # Panel Administrativo Privado
│   ├── assets/                    # Estilos CSS y scripts JS del panel
│   ├── includes/                  # Middleware (auth, csrf, session, layout)
│   ├── storage/                   # Almacenamiento local de sesiones (protegido)
│   │   └── sessions/              # Archivos sess_* (excluidos de git)
│   ├── categorias.php             # CRUD de Categorías del Portafolio
│   ├── contactos.php              # Gestión de Solicitudes y Cotizaciones
│   ├── dashboard.php              # Métricas y resumen operativo
│   ├── login.php                  # Acceso autenticado al panel
│   ├── logout.php                 # Destrucción segura de sesión
│   ├── proyectos.php              # CRUD de Proyectos del Portafolio
│   └── servicios.php              # CRUD de Servicios Creativos
├── backend/                       # Capa de Servicios y API REST
│   ├── api/                       # Endpoints públicos JSON
│   │   ├── categories.php         # GET: Categorías activas
│   │   ├── config.php             # GET: Parámetros globales
│   │   ├── contact.php            # POST: Registro de cotizaciones
│   │   ├── portfolio.php          # GET: Proyectos filtrables
│   │   └── services.php           # GET: Servicios principales
│   ├── config/                    # Conexión PDO y cabeceras CORS
│   └── helpers/                   # Respuestas JSON estandarizadas y validaciones
├── database/                      # Base de Datos
│   └── devioz_creative_studio.sql # Script DDL/DML oficial idempotente
├── docs/                          # Documentación Técnica por Fases
│   ├── fase-3-base-datos.md
│   ├── fase-4-backend-api.md
│   ├── fase-5-panel-admin.md
│   ├── fase-6-seguridad-optimizacion.md
│   ├── fase-7-entrega-final.md
│   └── guia-instalacion-xampp.md
├── frontend/                      # Sitio Web Público Desacoplado
│   ├── assets/                    # Hojas de estilo, imágenes y scripts
│   └── index.html                 # Landing page institucional
└── README.md                      # Ficha principal del proyecto
```

---

## 3. Instalación y Despliegue en XAMPP

### Paso 1: Ubicación de Archivos
Copiar la carpeta completa `Devioz` dentro del directorio `htdocs` de XAMPP:
```text
C:\xampp\htdocs\Devioz\
```

### Paso 2: Iniciar Servicios
Abrir el **XAMPP Control Panel** e iniciar los módulos:
* **Apache** (Puerto 80 / 443)
* **MySQL** (Puerto 3306)

### Paso 3: Importar la Base de Datos
1. Ingresar a phpMyAdmin desde el navegador: `http://localhost/phpmyadmin/`.
2. Ir a la pestaña **Importar**.
3. Seleccionar el archivo oficial:
   ```text
   C:\xampp\htdocs\Devioz\database\devioz_creative_studio.sql
   ```
4. Hacer clic en **Importar**. El script crea automáticamente la base de datos `devioz_creative_studio`, sus tablas con claves foráneas, índices optimizados y datos iniciales (*seed data*).

> **Alternativa por Consola / Terminal:**
> ```bash
> C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\Devioz\database\devioz_creative_studio.sql
> ```

### Paso 4: Configuración de Credenciales (Opcional)
Por defecto, la conexión se realiza sin contraseña para el usuario `root` local en `localhost`. Si tu instalación de MySQL tiene contraseña, edita el archivo [`backend/config/database.php`](backend/config/database.php):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'devioz_creative_studio');
define('DB_USER', 'root');
define('DB_PASS', 'tu_contraseña');
```

---

## 4. Cómo Ejecutar y Probar el Sistema

### 4.1 Sitio Web Público (Frontend)
Abrir en cualquier navegador:
* **URL en XAMPP:** `http://localhost/Devioz/frontend/`
* **URL directa de archivo:** `C:/xampp/htdocs/Devioz/frontend/index.html`

### 4.2 Endpoints API REST (Backend)
Los endpoints devuelven respuestas JSON estructuradas (`success`, `message`, `data` o `errors`):
* **Servicios:** `GET http://localhost/Devioz/backend/api/services.php`
* **Categorías:** `GET http://localhost/Devioz/backend/api/categories.php`
* **Portafolio:** `GET http://localhost/Devioz/backend/api/portfolio.php`
  * Filtrado por categoría: `GET http://localhost/Devioz/backend/api/portfolio.php?category=branding`
* **Configuración:** `GET http://localhost/Devioz/backend/api/config.php`
* **Registro de Contacto:** `POST http://localhost/Devioz/backend/api/contact.php` (Payload JSON o x-www-form-urlencoded con `nombre`, `email`, `servicio_interes`, `mensaje`).

### 4.3 Panel Administrativo
* **Acceso:** `http://localhost/Devioz/admin/login.php`
* **Credenciales de prueba:**
  * **Email:** `admin@devioz.com`
  * **Contraseña:** `Admin123!`

---

## 5. Pruebas Rápidas de Seguridad y Funcionamiento

1. **Protección de Rutas:** Si intentas acceder a `http://localhost/Devioz/admin/dashboard.php` sin haber iniciado sesión, el middleware redirige automáticamente a `login.php`.
2. **Protección CSRF:** Toda acción de creación, edición, alternancia de estado o eliminación requiere token CSRF vía POST. Cualquier solicitud POST sin token válido es rechazada con código de seguridad.
3. **Inocuidad de GET:** Los enlaces de mutación vía GET están bloqueados; el método GET únicamente sirve para consulta y lectura de vistas.
4. **Almacenamiento Local de Sesiones:** Las sesiones de PHP se almacenan en `admin/storage/sessions/` para evitar problemas de permisos de escritura con `C:\xampp\tmp`.

---

## 6. Recomendaciones de Seguridad antes de Pasar a Producción

1. **Protocolo HTTPS:** Instalar un certificado SSL/TLS y forzar HTTPS para que las cookies de sesión y tokens viajen cifrados (`session.cookie_secure = true`).
2. **Variables de Entorno:** Mover las constantes de conexión a base de datos de `backend/config/database.php` a un archivo `.env` ubicado fuera de la raíz pública del servidor web.
3. **Cambio de Credenciales:** Cambiar inmediatamente la contraseña del usuario `admin@devioz.com` desde la base de datos o interfaz administrativa.
4. **Límite de Tasa de Peticiones (Rate Limiting):** Configurar control de peticiones en el servidor web o middleware para prevenir fuerza bruta en el login y spam en el formulario de contacto.
5. **Permisos de Archivos:** Asegurar que solo el directorio `admin/storage/sessions/` tenga permisos de escritura por el usuario del servidor web, manteniendo el resto de archivos en modo solo lectura (`chmod 644` / `chmod 755`).
