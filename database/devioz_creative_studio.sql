-- ============================================================================
-- DEVIOZ CREATIVE STUDIO - BASE DE DATOS OFICIAL (FASE 3)
-- Charset: UTF8MB4
-- Compatible con MySQL 5.7+ / MySQL 8.0 / MariaDB / phpMyAdmin (XAMPP)
-- Reimportable múltiples veces sin errores de Foreign Key.
-- ============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `devioz_creative_studio` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `devioz_creative_studio`;

-- Desactivar temporalmente la verificación de llaves foráneas para reimportación segura
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- ELIMINACIÓN DE TABLAS EN ORDEN DEPENDIENTE RECOMENDADO
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `contactos`;
DROP TABLE IF EXISTS `proyectos`;
DROP TABLE IF EXISTS `servicios`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `configuracion`;
DROP TABLE IF EXISTS `usuarios`;

-- Reactivar verificación de llaves foráneas para la creación de esquemas
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. TABLA: usuarios
-- Guarda los accesos al panel administrativo.
-- ----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(50) DEFAULT 'admin',
  `estado` TINYINT DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. TABLA: servicios
-- Guarda los 5 servicios creativos principales mostrados en la web.
-- ----------------------------------------------------------------------------
CREATE TABLE `servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL UNIQUE,
  `descripcion` TEXT NOT NULL,
  `imagen` VARCHAR(255) NULL,
  `beneficios` TEXT NULL COMMENT 'Beneficios separados por pipe (|)',
  `estado` TINYINT DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_servicios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TABLA: categorias
-- Categorías principales del portafolio (Tabla padre para proyectos).
-- ----------------------------------------------------------------------------
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `estado` TINYINT DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_categorias_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. TABLA: proyectos
-- Proyectos del portafolio vinculados a una categoría.
-- ----------------------------------------------------------------------------
CREATE TABLE `proyectos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL UNIQUE,
  `descripcion` TEXT NOT NULL,
  `imagen` VARCHAR(255) NULL,
  `tipo` VARCHAR(80) NULL,
  `cliente` VARCHAR(120) NULL,
  `fecha` DATE NULL,
  `destacado` TINYINT DEFAULT 0 COMMENT '1: Sí, 0: No',
  `estado` TINYINT DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_proyectos_categoria` (`categoria_id`),
  KEY `idx_proyectos_estado` (`estado`),
  KEY `idx_proyectos_destacado` (`destacado`),
  CONSTRAINT `fk_proyectos_categorias` 
    FOREIGN KEY (`categoria_id`) 
    REFERENCES `categorias` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. TABLA: contactos
-- Solicitudes de cotización y mensajes recibidos desde el formulario web.
-- ----------------------------------------------------------------------------
CREATE TABLE `contactos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `empresa` VARCHAR(150) NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(40) NULL,
  `servicio_interes` VARCHAR(150) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `estado` VARCHAR(50) DEFAULT 'nuevo' COMMENT 'nuevo, en_proceso, atendido, archivado',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_contactos_estado` (`estado`),
  KEY `idx_contactos_creado_en` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. TABLA: configuracion
-- Parámetros generales del proyecto (email, redes, sede, etc.).
-- ----------------------------------------------------------------------------
CREATE TABLE `configuracion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERCIÓN DE DATOS INICIALES (SEED DATA)
-- ============================================================================

-- 1. Insertar Usuario Administrador Inicial
-- Password: Admin123! (Generado con password_hash de PHP BCRYPT)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `estado`) VALUES
('Administrador Devioz', 'admin@devioz.com', '$2y$12$Zs2WkGHOBUXPCQl/Yl.2yeN1mL1Y8cXxm4c6edueTw7G3FCMMVkyi', 'admin', 1);

-- 2. Insertar Categorías del Portafolio
INSERT INTO `categorias` (`id`, `nombre`, `slug`, `estado`) VALUES
(1, 'Diseño Gráfico', 'diseno-grafico', 1),
(2, 'Video', 'video', 1),
(3, 'Spots', 'spots', 1),
(4, 'Fotografía', 'fotografia', 1),
(5, 'Branding', 'branding', 1);

-- 3. Insertar Servicios Principales
INSERT INTO `servicios` (`id`, `titulo`, `slug`, `descripcion`, `imagen`, `beneficios`, `estado`) VALUES
(1, 'Diseño gráfico profesional', 'diseno-grafico-profesional', 'Piezas visuales para redes, campañas, presentaciones, identidad corporativa y comunicación comercial.', 'assets/img/services/diseno-grafico.svg', 'Branding e Identidad Corporativa|Piezas para Redes & Campañas|Presentaciones de Alto Impacto', 1),
(2, 'Producción de videos', 'produccion-de-videos', 'Creamos videos institucionales, promocionales y contenido dinámico para redes sociales.', 'assets/img/services/produccion-videos.svg', 'Videos Institucionales|Contenido Dinámico para RRSS|Edición y Postproducción 4K', 1),
(3, 'Spots publicitarios', 'spots-publicitarios', 'Anuncios cortos y directos para comunicar promociones, lanzamientos y mensajes clave de marca.', 'assets/img/services/spots-publicitarios.svg', 'Spots para Ads (Meta, YouTube, TikTok)|Guionismo & Concepto Creativo|Locución y Audio Profesional', 1),
(4, 'Fotografía profesional', 'fotografia-profesional', 'Fotografía corporativa, de productos, eventos y contenido comercial con acabado profesional.', 'assets/img/services/fotografia-profesional.svg', 'Fotografía Corporativa y Equipo|Fotografía de Producto & E-commerce|Cobertura de Eventos Empresariales', 1),
(5, 'Contenido visual especializado', 'contenido-visual-especializado', 'Soluciones gráficas y audiovisuales pensadas para campañas digitales, branding y comunicación de alto impacto.', 'assets/img/services/contenido-especializado.svg', 'Animaciones 2D / Motion Graphics|Kits Visuales para Lanzamientos|Estrategia Visual de Marca', 1);

-- 4. Insertar 6 Proyectos Simulados del Portafolio
INSERT INTO `proyectos` (`categoria_id`, `titulo`, `slug`, `descripcion`, `imagen`, `tipo`, `cliente`, `fecha`, `destacado`, `estado`) VALUES
(1, 'Campaña visual para redes sociales', 'campana-visual-redes-sociales', 'Diseño estratégico de piezas gráficas y carruseles publicitarios para redes digitales.', 'assets/img/portfolio/project-1.svg', 'Diseño Gráfico', 'Devioz Corp', '2026-01-15', 1, 1),
(3, 'Spot promocional de lanzamiento', 'spot-promocional-lanzamiento', 'Anuncio corto y dinámico para campaña de lanzamiento de producto tecnológico.', 'assets/img/portfolio/project-2.svg', 'Spot Publicitario', 'TechStart', '2026-02-01', 1, 1),
(4, 'Fotografía corporativa de equipo', 'fotografia-corporativa-equipo', 'Sesión de fotos ejecutiva para sitio web y perfiles corporativos institucionales.', 'assets/img/portfolio/project-3.svg', 'Fotografía', 'Global Logistics', '2026-02-10', 0, 1),
(5, 'Branding para marca emergente', 'branding-marca-emergente', 'Construcción de identidad de marca, paleta de colores y manual de aplicación gráfica.', 'assets/img/portfolio/project-4.svg', 'Branding', 'Aura Health', '2026-02-18', 1, 1),
(2, 'Video institucional empresarial', 'video-institucional-empresarial', 'Producción audiovisual de alto nivel proyectando los valores y alcance de la empresa.', 'assets/img/portfolio/project-5.svg', 'Video Producción', 'Innova Group', '2026-02-25', 1, 1),
(1, 'Piezas gráficas para campaña digital', 'piezas-graficas-campana-digital', 'Banners animados y creatividades optimizadas para pauta publicitaria digital.', 'assets/img/portfolio/project-6.svg', 'Diseño Gráfico', 'Fintech Solutions', '2026-03-01', 0, 1);

-- 5. Insertar Configuración Básica
INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('nombre_proyecto', 'Devioz Creative Studio'),
('email_contacto', 'contacto@devioz.com'),
('web_oficial', 'https://devioz.com/'),
('sede_principal', 'Lima, Perú'),
('telefono_contacto', '+51 999 999 999');
