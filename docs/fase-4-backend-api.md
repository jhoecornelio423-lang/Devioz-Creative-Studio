# Documentación Fase 4 - Backend API PHP

## 1. Resumen de la Arquitectura Backend

El Backend de **Devioz Creative Studio** está construido en PHP puro bajo una **arquitectura de API REST desacoplada**. Todos los endpoints responden únicamente en formato JSON (`application/json`) y utilizan **PDO con Prepared Statements** para garantizar máxima seguridad contra inyecciones SQL.

- **Directorio raíz del backend:** [`backend/`](../backend/)
- **Base de datos:** MySQL (`devioz_creative_studio`)
- **Cabeceras CORS:** Habilitadas para consultas cliente locales y preflight OPTIONS.

---

## 2. Endpoints Disponibles

### 2.1 Obtener Servicios Creativos
- **Endpoint:** `GET /backend/api/services.php`
- **Descripción:** Devuelve la lista de servicios activos (`estado = 1`) ordenados por ID. El campo `beneficios` se devuelve estructurado como array.
- **Respuesta Exitosa (HTTP 200):**
```json
{
  "success": true,
  "message": "Servicios obtenidos correctamente",
  "data": [
    {
      "id": 1,
      "titulo": "Diseño gráfico profesional",
      "slug": "diseno-grafico-profesional",
      "descripcion": "Piezas visuales para redes, campañas, presentaciones...",
      "imagen": "assets/img/services/diseno-grafico.svg",
      "beneficios": [
        "Branding e Identidad Corporativa",
        "Piezas para Redes & Campañas",
        "Presentaciones de Alto Impacto"
      ],
      "estado": 1
    }
  ]
}
```

---

### 2.2 Obtener Categorías del Portafolio
- **Endpoint:** `GET /backend/api/categories.php`
- **Descripción:** Devuelve las categorías activas para el filtrado del portafolio.
- **Respuesta Exitosa (HTTP 200):**
```json
{
  "success": true,
  "message": "Categorías obtenidas correctamente",
  "data": [
    {
      "id": 1,
      "nombre": "Diseño Gráfico",
      "slug": "diseno-grafico",
      "estado": 1
    }
  ]
}
```

---

### 2.3 Obtener Proyectos del Portafolio
- **Endpoint:** `GET /backend/api/portfolio.php`
- **Parámetros Opcionales (Query Params):**
  - `category` (string): Slug de categoría a filtrar (Ej. `video`, `diseno-grafico`, `spots`, `fotografia`, `branding`, `todos`).
- **Ejemplo con filtro:** `GET /backend/api/portfolio.php?category=video`
- **Respuesta Exitosa (HTTP 200):**
```json
{
  "success": true,
  "message": "Proyectos del portafolio obtenidos correctamente",
  "data": [
    {
      "id": 5,
      "categoria_id": 2,
      "categoria_nombre": "Video",
      "categoria_slug": "video",
      "titulo": "Video institucional empresarial",
      "slug": "video-institucional-empresarial",
      "descripcion": "Producción audiovisual de alto nivel...",
      "imagen": "assets/img/portfolio/project-5.svg",
      "tipo": "Video Producción",
      "cliente": "Innova Group",
      "fecha": "2026-02-25",
      "destacado": 1,
      "estado": 1
    }
  ]
}
```

---

### 2.4 Obtener Configuración Global
- **Endpoint:** `GET /backend/api/config.php`
- **Descripción:** Devuelve la configuración pública como un objeto clave / valor.
- **Respuesta Exitosa (HTTP 200):**
```json
{
  "success": true,
  "message": "Configuración obtenida correctamente",
  "data": {
    "nombre_proyecto": "Devioz Creative Studio",
    "email_contacto": "contacto@devioz.com",
    "web_oficial": "https://devioz.com/",
    "sede_principal": "Lima, Perú",
    "telefono_contacto": "+51 999 999 999"
  }
}
```

---

### 2.5 Registrar Solicitud de Contacto
- **Endpoint:** `POST /backend/api/contact.php`
- **Content-Type:** `application/json`
- **Cuerpo de la Petición (Payload):**
```json
{
  "nombre": "Carlos Mendoza",
  "empresa": "Devioz Test Corp",
  "email": "carlos@ejemplo.com",
  "telefono": "+51 987 654 321",
  "servicio_interes": "produccion-videos",
  "mensaje": "Requerimos un spot promocional de 30 segundos."
}
```
- **Validaciones Requeridas:** `nombre`, `email` (formato válido), `servicio_interes`, `mensaje`.
- **Respuesta Exitosa (HTTP 201):**
```json
{
  "success": true,
  "message": "Solicitud registrada correctamente",
  "data": {
    "id": 1
  }
}
```
- **Respuesta de Error de Validación (HTTP 422):**
```json
{
  "success": false,
  "message": "Error de validación en la solicitud.",
  "errors": {
    "email": "El formato de correo electrónico no es válido."
  }
}
```

---

## 3. Instrucciones de Prueba

### Opción 1: En Navegador / cURL con Servidor Local de PHP
1. Abrir la terminal en la raíz del proyecto y levantar el servidor interno de PHP:
   ```bash
   php -S localhost:8000
   ```
2. Probar los endpoints en el navegador o mediante cURL:
   - `http://localhost:8000/backend/api/services.php`
   - `http://localhost:8000/backend/api/categories.php`
   - `http://localhost:8000/backend/api/portfolio.php?category=video`
   - `http://localhost:8000/backend/api/config.php`

3. Probar envío POST con cURL:
   ```bash
   curl -X POST http://localhost:8000/backend/api/contact.php \
     -H "Content-Type: application/json" \
     -d "{\"nombre\":\"Test\", \"email\":\"test@ejemplo.com\", \"servicio_interes\":\"diseno-grafico\", \"mensaje\":\"Prueba cURL\"}"
   ```

---

## 4. Pendientes para la Fase 5 (Panel Administrativo)

En la **Fase 5** se desarrollarán los siguientes componentes backend:
- Endpoint de Autenticación de Administrador (`backend/api/login.php`).
- Manejo de Sesiones / Tokens JWT o PHP Session.
- Endpoints CRUD para administración de Servicios, Categorías y Proyectos.
- Endpoint para listar y actualizar el estado de los mensajes de contacto.
