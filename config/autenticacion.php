<?php
declare(strict_types=1);

function iniciarSesionSysmovil(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function modulosSysmovil(): array
{
    return [
        'dashboard', 'inventario', 'ventas', 'compras', 'clientes',
        'proveedores', 'usuarios', 'servicio_tecnico', 'reportes', 'configuracion',
    ];
}

function accionesSysmovil(): array
{
    return ['ver' => 'puede_ver', 'crear' => 'puede_crear', 'editar' => 'puede_editar', 'eliminar' => 'puede_eliminar', 'exportar' => 'puede_exportar'];
}

function permisosInicialesRol(mysqli $db, int $idRol, string $nombreRol): void
{
    $rol = strtolower($nombreRol);
    $esAdmin = str_contains($rol, 'admin');
    $esVendedor = str_contains($rol, 'vend');
    $esTecnico = str_contains($rol, 'tec');
    $stmt = $db->prepare('INSERT IGNORE INTO permisos_rol (id_rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar) VALUES (?, ?, ?, ?, ?, ?, ?)');

    foreach (modulosSysmovil() as $modulo) {
        $ver = $crear = $editar = $eliminar = $exportar = 0;
        if ($esAdmin) {
            $ver = $crear = $editar = $eliminar = $exportar = 1;
        } elseif ($modulo === 'dashboard') {
            $ver = 1;
        } elseif ($esVendedor) {
            if ($modulo === 'ventas') { $ver = 1; $crear = 1; }
            if ($modulo === 'clientes') { $ver = 1; $crear = 1; $editar = 1; }
            if (in_array($modulo, ['inventario', 'reportes'], true)) $ver = 1;
            if ($modulo === 'reportes') $exportar = 1;
        } elseif ($esTecnico) {
            if (in_array($modulo, ['clientes', 'servicio_tecnico'], true)) $ver = 1;
            if ($modulo === 'servicio_tecnico') { $crear = 1; $editar = 1; }
        }

        $stmt->bind_param('isiiiii', $idRol, $modulo, $ver, $crear, $editar, $eliminar, $exportar);
        $stmt->execute();
    }
    $stmt->close();
}

function obtenerPermisosSysmovil(mysqli $db, int $idRol): array
{
    $permisos = [];
    $resultado = $db->prepare('SELECT modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar FROM permisos_rol WHERE id_rol = ?');
    $resultado->bind_param('i', $idRol);
    $resultado->execute();
    $filas = $resultado->get_result()->fetch_all(MYSQLI_ASSOC);
    $resultado->close();
    foreach ($filas as $fila) {
        $permisos[$fila['modulo']] = [
            'ver' => (bool)$fila['puede_ver'],
            'crear' => (bool)$fila['puede_crear'],
            'editar' => (bool)$fila['puede_editar'],
            'eliminar' => (bool)$fila['puede_eliminar'],
            'exportar' => (bool)$fila['puede_exportar'],
        ];
    }
    return $permisos;
}

function usuarioSysmovil(): ?array
{
    iniciarSesionSysmovil();
    return isset($_SESSION['sysmovil_usuario']) && is_array($_SESSION['sysmovil_usuario'])
        ? $_SESSION['sysmovil_usuario']
        : null;
}

function exigirInicioSesion(mysqli $db): array
{
    $usuario = usuarioSysmovil();
    if (!$usuario) {
        header('Location: ../login.html');
        exit;
    }

    // Consultamos el estado y el rol en cada solicitud protegida para que
    // los cambios hechos por un administrador se apliquen sin esperar ni
    // obligar al usuario a iniciar sesión otra vez.
    $stmt = $db->prepare("SELECT u.nombre_usuario, u.estado, u.contrasena, u.id_rol,
                                 r.nombre_rol, e.nombres, e.apellidos,
                                 e.estado AS estado_empleado
                          FROM usuarios u
                          INNER JOIN empleados e ON e.id_empleado = u.id_empleado
                          INNER JOIN roles r ON r.id_rol = u.id_rol
                          WHERE u.id_usuario = ?");
    $stmt->bind_param('i', $usuario['id_usuario']);
    $stmt->execute();
    $estado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$estado || $estado['estado'] !== 'Activo' || $estado['estado_empleado'] !== 'Activo'
        || !hash_equals((string)($_SESSION['sysmovil_hash_contrasena'] ?? ''), (string)$estado['contrasena'])) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
        header('Location: ../login.html?motivo=inactivo');
        exit;
    }

    $_SESSION['sysmovil_usuario']['nombre_usuario'] = (string)$estado['nombre_usuario'];
    $_SESSION['sysmovil_usuario']['id_rol'] = (int)$estado['id_rol'];
    $_SESSION['sysmovil_usuario']['rol'] = (string)$estado['nombre_rol'];
    $_SESSION['sysmovil_usuario']['nombre'] = trim($estado['nombres'] . ' ' . $estado['apellidos']);
    $_SESSION['sysmovil_verificado'] = time();

    return $_SESSION['sysmovil_usuario'];
}

function usuarioTienePermiso(mysqli $db, string $modulo, string $accion = 'ver'): bool
{
    $columnas = accionesSysmovil();
    if (!in_array($modulo, modulosSysmovil(), true) || !isset($columnas[$accion])) return false;
    $usuario = usuarioSysmovil();
    if (!$usuario) return false;

    $columna = $columnas[$accion];
    $stmt = $db->prepare("SELECT {$columna} FROM permisos_rol WHERE id_rol = ? AND modulo = ? LIMIT 1");
    $stmt->bind_param('is', $usuario['id_rol'], $modulo);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_row();
    $stmt->close();
    return (bool)($fila[0] ?? false);
}

function exigirPermiso(mysqli $db, string $modulo, string $accion = 'ver'): void
{
    exigirInicioSesion($db);
    try {
        $permitido = usuarioTienePermiso($db, $modulo, $accion);
    } catch (mysqli_sql_exception $e) {
        http_response_code(503);
        exit('La configuración de permisos no está instalada. Ejecuta database/003_autenticacion_permisos.sql en MySQL.');
    }
    if (!$permitido) {
        http_response_code(403);
        exit('No tienes permiso para realizar esta acción.');
    }
}

function tokenCsrfSysmovil(): string
{
    iniciarSesionSysmovil();
    if (empty($_SESSION['sysmovil_csrf'])) $_SESSION['sysmovil_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['sysmovil_csrf'];
}

function validarCsrfSysmovil(?string $token): bool
{
    iniciarSesionSysmovil();
    return is_string($token) && isset($_SESSION['sysmovil_csrf']) && hash_equals((string)$_SESSION['sysmovil_csrf'], $token);
}

function exigirCsrfSysmovil(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Esta acción solo acepta solicitudes POST.');
    }

    if (!validarCsrfSysmovil($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('La sesión del formulario venció o el token no es válido. Recarga la página e inténtalo de nuevo.');
    }
}
