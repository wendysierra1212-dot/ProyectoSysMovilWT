CREATE TABLE IF NOT EXISTS actividad_sistema (
    id_actividad BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    modulo VARCHAR(40) NOT NULL,
    accion VARCHAR(40) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_actividad),
    KEY idx_actividad_fecha (fecha),
    KEY idx_actividad_modulo (modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
