<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';
require_once __DIR__ . '/../config/actividad.php';
iniciarSesionSysmovil();

function responderLogin(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderLogin(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$nombreUsuario = trim((string)($_POST['username'] ?? ''));
$contrasena = (string)($_POST['password'] ?? '');
if ($nombreUsuario === '' || $contrasena === '') {
    responderLogin(422, ['ok' => false, 'mensaje' => 'Escribe tu usuario y contraseña.']);
}

try {
    $stmt = $conn->prepare("SELECT u.id_usuario, u.nombre_usuario, u.contrasena, u.estado AS estado_usuario,
                                   u.id_empleado, u.id_rol, e.nombres, e.apellidos, e.estado AS estado_empleado,
                                   r.nombre_rol
                            FROM usuarios u
                            INNER JOIN empleados e ON e.id_empleado = u.id_empleado
                            INNER JOIN roles r ON r.id_rol = u.id_rol
                            WHERE u.nombre_usuario = ? LIMIT 1");
    $stmt->bind_param('s', $nombreUsuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($contrasena, (string)$usuario['contrasena'])
        || $usuario['estado_usuario'] !== 'Activo' || $usuario['estado_empleado'] !== 'Activo') {
        responderLogin(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos, o cuenta inactiva.']);
    }

    $hashActual = (string)$usuario['contrasena'];
    if (password_needs_rehash($hashActual, PASSWORD_DEFAULT)) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $idUsuario = (int)$usuario['id_usuario'];
        $stmt = $conn->prepare('UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?');
        $stmt->bind_param('si', $hash, $idUsuario);
        $stmt->execute();
        $stmt->close();
        $hashActual = $hash;
    }

    $permisos = obtenerPermisosSysmovil($conn, (int)$usuario['id_rol']);
    $rutasModulo = [
        'dashboard' => 'dashboard.php', 'inventario' => 'inventario.php', 'ventas' => 'ventas.php',
        'compras' => 'compras.php', 'clientes' => 'clientes.php', 'proveedores' => 'proveedores.php',
        'usuarios' => 'usuarios.php', 'servicio_tecnico' => 'serviciotecnico.php',
        'reportes' => 'reportes.php', 'configuracion' => 'configuracion.php',
    ];
    $destino = null;
    foreach ($rutasModulo as $modulo => $ruta) {
        if (!empty($permisos[$modulo]['ver'])) { $destino = $ruta; break; }
    }
    if ($destino === null) {
        responderLogin(403, ['ok' => false, 'mensaje' => 'Tu rol no tiene acceso a ningún módulo. Solicita al administrador que revise sus permisos.']);
    }

    session_regenerate_id(true);
    $nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
    $_SESSION['sysmovil_usuario'] = [
        'id_usuario' => (int)$usuario['id_usuario'],
        'nombre_usuario' => $usuario['nombre_usuario'],
        'id_empleado' => (int)$usuario['id_empleado'],
        'id_rol' => (int)$usuario['id_rol'],
        'nombre' => $nombreCompleto,
        'rol' => $usuario['nombre_rol'],
    ];
    $_SESSION['sysmovil_hash_contrasena'] = $hashActual;
    $_SESSION['sysmovil_verificado'] = time();

    $stmt = $conn->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?');
    $idUsuario = (int)$usuario['id_usuario'];
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $stmt->close();

    registrarActividadSistema($conn, 'Acceso', 'Inicio de sesión', 'Inicio de sesión de ' . $nombreCompleto . '.');

    responderLogin(200, [
        'ok' => true,
        'redirect' => 'pages/' . $destino,
        'usuario' => [
            'nombre' => $nombreCompleto,
            'nombre_usuario' => $usuario['nombre_usuario'],
            'rol' => $usuario['nombre_rol'],
            'id_rol' => (int)$usuario['id_rol'],
            'permisos' => $permisos,
        ],
    ]);
} catch (mysqli_sql_exception $e) {
    error_log('Login SYSMOVILWT: ' . $e->getMessage());
    responderLogin(503, ['ok' => false, 'mensaje' => 'No se pudo validar el acceso. Verifica que ejecutaste la migración de autenticación y que MySQL está disponible.']);
}
