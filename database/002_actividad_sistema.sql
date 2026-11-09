-- Crear la bitácora después de instalar el esquema base, que incluye usuarios.
USE sysmovilwt_v2;

CREATE TABLE IF NOT EXISTS actividad_sistema (
    id_actividad BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    modulo VARCHAR(40) NOT NULL,
    accion VARCHAR(40) NOT NULL,
    actor_usuario_id INT DEFAULT NULL,
    actor_nombre VARCHAR(100) NOT NULL DEFAULT 'Sistema',
    descripcion VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_actividad),
    KEY idx_actividad_fecha (fecha),
    KEY idx_actividad_modulo (modulo),
    KEY idx_actividad_usuario (actor_usuario_id),
    CONSTRAINT fk_actividad_sistema_usuario FOREIGN KEY (actor_usuario_id)
        REFERENCES usuarios (id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
