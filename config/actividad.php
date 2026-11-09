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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $preparada = true;
}

function registrarActividadSistema(
    mysqli $db,
    string $modulo,
    string $accion,
    string $descripcion,
    ?int $actorUsuarioId = null,
    ?string $actorNombre = null
): void
{
    try {
        prepararTablaActividadSistema($db);
        $modulo = substr(trim($modulo), 0, 40);
        $accion = substr(trim($accion), 0, 40);
        $descripcion = substr(trim($descripcion), 0, 255);
        if ($modulo === '' || $accion === '' || $descripcion === '') return;

        $usuarioSesion = $_SESSION['sysmovil_usuario'] ?? null;
        if ($actorUsuarioId === null && is_array($usuarioSesion) && isset($usuarioSesion['id_usuario'])) {
            $actorUsuarioId = (int)$usuarioSesion['id_usuario'];
        }
        if ($actorNombre === null && is_array($usuarioSesion)) {
            $actorNombre = (string)($usuarioSesion['nombre_usuario'] ?? $usuarioSesion['nombre'] ?? 'Sistema');
        }
        $actorNombre = substr(trim((string)($actorNombre ?: 'Sistema')), 0, 100);

        $stmt = $db->prepare('INSERT INTO actividad_sistema (modulo, accion, actor_usuario_id, actor_nombre, descripcion) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('ssiss', $modulo, $accion, $actorUsuarioId, $actorNombre, $descripcion);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('SYSMOVILWT bitácora: ' . $e->getMessage());
        // La bitácora no debe impedir una operación principal que ya fue guardada.
    }
}
