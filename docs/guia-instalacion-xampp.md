# Guía de Instalación y Despliegue en XAMPP

Esta guía detalla los pasos exactos para instalar, configurar y ejecutar **Devioz Creative Studio** en un entorno local con **XAMPP (Windows / macOS / Linux)**.

---

## 1. Requisitos Previos

* **XAMPP** instalado con:
  * PHP 8.0 o superior.
  * MySQL 5.7+ o MariaDB 10.4+.
  * Servidor Web Apache.
* Navegador web moderno (Chrome, Edge, Firefox, Brave, Safari).

---

## 2. Paso a Paso de Instalación

### Paso 1: Copiar el Proyecto al Servidor Web
Ubica la carpeta del proyecto `Devioz` dentro del directorio público `htdocs` de tu instalación de XAMPP:

* **En Windows:**
  ```text
  C:\xampp\htdocs\Devioz\
  ```
* **En macOS (XAMPP-VM / Aplicaciones):**
  ```text
  /Applications/XAMPP/xamppfiles/htdocs/Devioz/
  ```
* **En Linux (/opt/lampp):**
  ```text
  /opt/lampp/htdocs/Devioz/
  ```

---

### Paso 2: Iniciar los Servicios en XAMPP
1. Abre el **XAMPP Control Panel**.
2. Haz clic en **Start** para el módulo **Apache**.
3. Haz clic en **Start** para el módulo **MySQL**.
4. Ambos módulos deben mostrar fondo verde indicando que están en ejecución (puertos estándar 80/443 para Apache y 3306 para MySQL).

---

### Paso 3: Importar la Base de Datos en phpMyAdmin

1. Abre tu navegador web e ingresa a:
   ```text
   http://localhost/phpmyadmin/
   ```
2. En la barra superior, haz clic en la pestaña **Importar** (o *Import*).
3. En la sección **Archivo a importar**, presiona el botón **Seleccionar archivo** (o *Examinar*).
4. Busca y selecciona el archivo SQL ubicado en el proyecto:
   ```text
   C:\xampp\htdocs\Devioz\database\devioz_creative_studio.sql
   ```
5. Asegúrate de que el conjunto de caracteres esté en **utf-8** y presiona el botón **Importar** al final de la página.
6. El script ejecutará automáticamente:
   * La creación de la base de datos `devioz_creative_studio`.
   * La creación de las 6 tablas (`usuarios`, `servicios`, `categorias`, `proyectos`, `contactos`, `configuracion`).
   * La creación de índices optimizados.
   * La carga de datos iniciales (*seed data*).

> **Alternativa por Terminal (Símbolo del sistema o PowerShell):**
> ```cmd
> C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\Devioz\database\devioz_creative_studio.sql
> ```

---

### Paso 4: Configurar Credenciales de Base de Datos (Si es necesario)

Por defecto, XAMPP se instala con el usuario `root` y contraseña vacía `""`. El proyecto ya viene configurado con estos valores por defecto.

Si tu instalación de MySQL tiene contraseña configurada o utiliza otro puerto:
1. Abre el archivo [`backend/config/database.php`](../backend/config/database.php).
2. Ajusta las constantes según tu entorno:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'devioz_creative_studio');
   define('DB_USER', 'root');
   define('DB_PASS', 'tu_contraseña_aqui');
   define('DB_CHARSET', 'utf8mb4');
   ```

---

### Paso 5: Abrir y Explorar el Frontend Público

El sitio web público es totalmente desacoplado y puede ser visualizado de dos formas:

1. **Vía Servidor Apache:**
   ```text
   http://localhost/Devioz/frontend/
   ```
2. **Abriendo directamente el archivo HTML:**
   Doble clic sobre:
   ```text
   C:\xampp\htdocs\Devioz\frontend\index.html
   ```

**Verificaciones recomendadas en el frontend:**
* Comprobar la carga fluida de las secciones Hero, Servicios, Portafolio y Proceso.
* Probar los filtros por categoría del portafolio (*Diseño Gráfico, Video, Spots, Fotografía, Branding*).
* Probar la validación en tiempo real del formulario de contacto.

---

### Paso 6: Abrir el Panel Administrativo y Probar el Login

1. Ingresa a la URL del panel administrativo en tu navegador:
   ```text
   http://localhost/Devioz/admin/login.php
   ```
2. Introduce las credenciales iniciales de prueba:
   * **Correo Electrónico:** `admin@devioz.com`
   * **Contraseña:** `Admin123!`
3. Haz clic en **Iniciar Sesión**.
4. El sistema verificará la contraseña mediante `password_verify()`, regenerará el ID de sesión de forma segura y te redirigirá a:
   ```text
   http://localhost/Devioz/admin/dashboard.php
   ```

---

### Paso 7: Verificación de Funcionalidades Administrativas

Una vez dentro del panel:
* **Dashboard:** Comprueba que se muestren las métricas numéricas y las solicitudes recientes.
* **Servicios:** Crea, edita o desactiva servicios creativos.
* **Categorías:** Gestiona las categorías del portafolio.
* **Portafolio:** Administra los proyectos y prueba marcar proyectos como destacados (★).
* **Contactos:** Consulta los mensajes recibidos y cambia su estado de seguimiento (`nuevo`, `en_proceso`, `atendido`, `archivado`).
* **Cerrar Sesión:** Haz clic en **Cerrar Sesión** en la barra superior o menú lateral. Comprueba que el sistema destruya la sesión y te redirija a `login.php?logout=1`.
* **Protección de Rutas:** Intenta volver a ingresar a `dashboard.php` sin iniciar sesión para verificar que la redirección automática te devuelva al login.

---

## 3. Preguntas Frecuentes y Solución de Problemas

### ¿Qué hacer si aparece un error de conexión a la base de datos?
1. Verifica que el módulo **MySQL** esté en verde en el panel de XAMPP.
2. Abre phpMyAdmin y confirma que la base de datos `devioz_creative_studio` exista.
3. Si cambiaste la clave de MySQL en XAMPP, actualízala en `backend/config/database.php`.

### ¿Dónde se guardan las sesiones de administración?
Para evitar los errores clásicos de permisos en Windows con `C:\xampp\tmp`, el sistema utiliza su propia carpeta de almacenamiento local:
```text
admin/storage/sessions/
```
Esta carpeta está protegida contra navegación web mediante un archivo `.htaccess` interno.
