-- =========================================================
-- MIGRACIÓN 001: DATOS BÁSICOS DE CONFIGURACIÓN DE EMPRESA
-- Ejecutar una sola vez sobre la base sysmovilwt_v2.
-- =========================================================
USE sysmovilwt_v2;

CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id_configuracion TINYINT UNSIGNED NOT NULL DEFAULT 1,
    nombre_empresa VARCHAR(120) NOT NULL DEFAULT 'WT SERVIPHONE',
    nit VARCHAR(30) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    correo VARCHAR(120) DEFAULT NULL,
    direccion VARCHAR(180) DEFAULT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_configuracion),
    CONSTRAINT chk_configuracion_sistema_unica CHECK (id_configuracion = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO configuracion_sistema (id_configuracion, nombre_empresa)
VALUES (1, 'WT SERVIPHONE')
ON DUPLICATE KEY UPDATE id_configuracion = 1;
