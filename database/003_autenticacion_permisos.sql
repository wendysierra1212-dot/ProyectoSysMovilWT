-- Ejecutar una sola vez sobre la base sysmovilwt_v2.
USE sysmovilwt_v2;

CREATE TABLE IF NOT EXISTS permisos_rol (
    id_rol INT NOT NULL,
    modulo VARCHAR(40) NOT NULL,
    puede_ver TINYINT(1) NOT NULL DEFAULT 0,
    puede_crear TINYINT(1) NOT NULL DEFAULT 0,
    puede_editar TINYINT(1) NOT NULL DEFAULT 0,
    puede_eliminar TINYINT(1) NOT NULL DEFAULT 0,
    puede_exportar TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_rol, modulo),
    CONSTRAINT fk_permisos_rol_roles FOREIGN KEY (id_rol)
        REFERENCES roles (id_rol) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id_reset BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    vence_en DATETIME NOT NULL,
    utilizado_en DATETIME DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reset),
    UNIQUE KEY uq_password_reset_token (token_hash),
    KEY idx_password_reset_usuario_fecha (id_usuario, creado_en),
    CONSTRAINT fk_password_reset_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores iniciales. INSERT IGNORE conserva los permisos que ya se hayan ajustado.
INSERT IGNORE INTO permisos_rol
    (id_rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar)
SELECT r.id_rol, m.modulo,
    CASE
        WHEN LOWER(r.nombre_rol) LIKE '%admin%' THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%vend%' AND m.modulo IN ('dashboard','inventario','ventas','clientes','reportes') THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%tec%' AND m.modulo IN ('dashboard','clientes','servicio_tecnico') THEN 1
        WHEN LOWER(r.nombre_rol) NOT LIKE '%admin%' AND LOWER(r.nombre_rol) NOT LIKE '%vend%' AND LOWER(r.nombre_rol) NOT LIKE '%tec%' AND m.modulo = 'dashboard' THEN 1
        ELSE 0
    END AS puede_ver,
    CASE
        WHEN LOWER(r.nombre_rol) LIKE '%admin%' THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%vend%' AND m.modulo IN ('ventas','clientes') THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%tec%' AND m.modulo = 'servicio_tecnico' THEN 1
        ELSE 0
    END AS puede_crear,
    CASE
        WHEN LOWER(r.nombre_rol) LIKE '%admin%' THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%vend%' AND m.modulo = 'clientes' THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%tec%' AND m.modulo = 'servicio_tecnico' THEN 1
        ELSE 0
    END AS puede_editar,
    CASE WHEN LOWER(r.nombre_rol) LIKE '%admin%' THEN 1 ELSE 0 END AS puede_eliminar,
    CASE
        WHEN LOWER(r.nombre_rol) LIKE '%admin%' THEN 1
        WHEN LOWER(r.nombre_rol) LIKE '%vend%' AND m.modulo = 'reportes' THEN 1
        ELSE 0
    END AS puede_exportar
FROM roles r
CROSS JOIN (
    SELECT 'dashboard' AS modulo UNION ALL
    SELECT 'inventario' UNION ALL
    SELECT 'ventas' UNION ALL
    SELECT 'compras' UNION ALL
    SELECT 'clientes' UNION ALL
    SELECT 'proveedores' UNION ALL
    SELECT 'usuarios' UNION ALL
    SELECT 'servicio_tecnico' UNION ALL
    SELECT 'reportes' UNION ALL
    SELECT 'configuracion'
) AS m;
