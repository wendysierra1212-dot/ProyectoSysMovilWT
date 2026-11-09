-- Agrega a la bitácora quién realizó cada acción.
-- Ejecutar sobre sysmovilwt_v2 después de las migraciones anteriores.
USE sysmovilwt_v2;

SET @existe_actor_usuario_id := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividad_sistema'
      AND COLUMN_NAME = 'actor_usuario_id'
);
SET @sql_actor_usuario_id := IF(
    @existe_actor_usuario_id = 0,
    'ALTER TABLE actividad_sistema ADD COLUMN actor_usuario_id INT NULL AFTER accion',
    'SELECT ''La columna actor_usuario_id ya existe'' AS resultado'
);
PREPARE stmt_actor_usuario_id FROM @sql_actor_usuario_id;
EXECUTE stmt_actor_usuario_id;
DEALLOCATE PREPARE stmt_actor_usuario_id;

SET @existe_actor_nombre := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividad_sistema'
      AND COLUMN_NAME = 'actor_nombre'
);
SET @sql_actor_nombre := IF(
    @existe_actor_nombre = 0,
    'ALTER TABLE actividad_sistema ADD COLUMN actor_nombre VARCHAR(100) NOT NULL DEFAULT ''Sistema'' AFTER actor_usuario_id',
    'SELECT ''La columna actor_nombre ya existe'' AS resultado'
);
PREPARE stmt_actor_nombre FROM @sql_actor_nombre;
EXECUTE stmt_actor_nombre;
DEALLOCATE PREPARE stmt_actor_nombre;

SET @existe_indice_actor := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'actividad_sistema'
      AND INDEX_NAME = 'idx_actividad_usuario'
);
SET @sql_indice_actor := IF(
    @existe_indice_actor = 0,
    'ALTER TABLE actividad_sistema ADD KEY idx_actividad_usuario (actor_usuario_id)',
    'SELECT ''El índice idx_actividad_usuario ya existe'' AS resultado'
);
PREPARE stmt_indice_actor FROM @sql_indice_actor;
EXECUTE stmt_indice_actor;
DEALLOCATE PREPARE stmt_indice_actor;

SET @existe_fk_actor := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'actividad_sistema'
      AND CONSTRAINT_NAME = 'fk_actividad_sistema_usuario'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql_fk_actor := IF(
    @existe_fk_actor = 0,
    'ALTER TABLE actividad_sistema ADD CONSTRAINT fk_actividad_sistema_usuario FOREIGN KEY (actor_usuario_id) REFERENCES usuarios (id_usuario) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''La relación de usuario en actividad_sistema ya existe'' AS resultado'
);
PREPARE stmt_fk_actor FROM @sql_fk_actor;
EXECUTE stmt_fk_actor;
DEALLOCATE PREPARE stmt_fk_actor;

SELECT actor_usuario_id, actor_nombre, modulo, accion, fecha
FROM actividad_sistema ORDER BY id_actividad DESC LIMIT 10;
