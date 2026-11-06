<?php
/**
 * Bitácora común de actividad del sistema.
 * La tabla también se incluye en database/002_actividad_sistema.sql;
 * CREATE IF NOT EXISTS facilita su puesta en marcha en instalaciones locales.
 */
function prepararTablaActividadSistema(mysqli $db): void
{
    static $preparada = false;
    if ($preparada) return;

    try {
        $db->query('SELECT 1 FROM actividad_sistema LIMIT 0');
        $preparada = true;
        return;
    } catch (Throwable $e) {
        // Tabla aún no creada; se crea en la primera operación del sistema.
    }

    $db->query("CREATE TABLE IF NOT EXISTS actividad_sistema (
        id_actividad BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        modulo VARCHAR(40) NOT NULL,
        accion VARCHAR(40) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_actividad),
        KEY idx_actividad_fecha (fecha),
        KEY idx_actividad_modulo (modulo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $preparada = true;
}

function registrarActividadSistema(mysqli $db, string $modulo, string $accion, string $descripcion): void
{
    try {
        prepararTablaActividadSistema($db);
        $modulo = substr(trim($modulo), 0, 40);
        $accion = substr(trim($accion), 0, 40);
        $descripcion = substr(trim($descripcion), 0, 255);
        if ($modulo === '' || $accion === '' || $descripcion === '') return;

        $stmt = $db->prepare('INSERT INTO actividad_sistema (modulo, accion, descripcion) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $modulo, $accion, $descripcion);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // La bitácora no debe impedir una operación principal que ya fue guardada.
    }
}
