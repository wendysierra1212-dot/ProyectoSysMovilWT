<?php
declare(strict_types=1);

/* =========================================================
   ACCIÓN: CARGAR CONEXIÓN Y PROTEGER FORMULARIOS
   ========================================================= */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/autenticacion.php';
require_once __DIR__ . '/../../config/actividad.php';
exigirPermiso($conn, 'configuracion', 'ver');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_configuracion'])) {
    $_SESSION['csrf_configuracion'] = bin2hex(random_bytes(32));
}

/* =========================================================
   ACCIÓN: PREPARAR ESTADO DE LA VISTA
   ========================================================= */
$mensajeConfiguracion = '';
$tipoMensajeConfiguracion = 'success';
$tablaConfiguracionDisponible = true;
$empresa = [
    'nombre_empresa' => 'WT SERVIPHONE',
    'nit' => '',
    'telefono' => '',
    'correo' => '',
    'direccion' => '',
];
$rolesConfiguracion = [];
$rolEditar = null;

/* =========================================================
   ACCIÓN: GUARDAR DATOS GENERALES DE LA EMPRESA
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals((string)$_SESSION['csrf_configuracion'], $token)) {
        $mensajeConfiguracion = 'La solicitud venció. Recarga la página e inténtalo de nuevo.';
        $tipoMensajeConfiguracion = 'error';
    } else {
        $accion = (string)($_POST['accion'] ?? '');
        exigirPermiso($conn, 'configuracion', 'editar');

        try {
            if ($accion === 'guardar_empresa') {
                $empresa['nombre_empresa'] = trim((string)($_POST['nombre_empresa'] ?? ''));
                $empresa['nit'] = trim((string)($_POST['nit'] ?? ''));
                $empresa['telefono'] = trim((string)($_POST['telefono'] ?? ''));
                $empresa['correo'] = trim((string)($_POST['correo'] ?? ''));
                $empresa['direccion'] = trim((string)($_POST['direccion'] ?? ''));

                if ($empresa['nombre_empresa'] === '') {
                    throw new InvalidArgumentException('El nombre de la empresa es obligatorio.');
                }
                if ($empresa['correo'] !== '' && !filter_var($empresa['correo'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Escribe un correo válido o deja el campo vacío.');
                }

                $stmt = $conn->prepare(
                    'INSERT INTO configuracion_sistema (id_configuracion, nombre_empresa, nit, telefono, correo, direccion)
                     VALUES (1, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'))
                     ON DUPLICATE KEY UPDATE nombre_empresa = VALUES(nombre_empresa), nit = VALUES(nit), telefono = VALUES(telefono), correo = VALUES(correo), direccion = VALUES(direccion)'
                );
                $stmt->bind_param(
                    'sssss',
                    $empresa['nombre_empresa'],
                    $empresa['nit'],
                    $empresa['telefono'],
                    $empresa['correo'],
                    $empresa['direccion']
                );
                $stmt->execute();
                $stmt->close();
                registrarActividadSistema($conn, 'Configuración', 'Actualización', 'Datos generales de la empresa actualizados.');
                $mensajeConfiguracion = 'Los datos de la empresa se guardaron correctamente.';
            } elseif ($accion === 'guardar_rol') {
                $idRol = (int)($_POST['id_rol'] ?? 0);
                $rolNuevo = $idRol <= 0;
                $nombreRol = trim((string)($_POST['nombre_rol'] ?? ''));
                $descripcionRol = trim((string)($_POST['descripcion_rol'] ?? ''));

                if ($nombreRol === '') {
                    throw new InvalidArgumentException('El nombre del rol es obligatorio.');
                }

                if ($idRol > 0) {
                    $stmt = $conn->prepare('UPDATE roles SET nombre_rol = ?, descripcion = NULLIF(?, \'\') WHERE id_rol = ?');
                    $stmt->bind_param('ssi', $nombreRol, $descripcionRol, $idRol);
                } else {
                    $stmt = $conn->prepare('INSERT INTO roles (nombre_rol, descripcion) VALUES (?, NULLIF(?, \'\'))');
                    $stmt->bind_param('ss', $nombreRol, $descripcionRol);
                }

                $stmt->execute();
                if ($rolNuevo) $idRol = (int)$conn->insert_id;
                $stmt->close();
                permisosInicialesRol($conn, $idRol, $nombreRol);
                registrarActividadSistema($conn, 'Configuración', $rolNuevo ? 'Registro de rol' : 'Actualización de rol', 'Rol ' . $nombreRol . ($rolNuevo ? ' creado.' : ' actualizado.'));
                $mensajeConfiguracion = $rolNuevo ? 'El rol se creó correctamente.' : 'El rol se actualizó correctamente.';
            } elseif ($accion === 'guardar_permisos') {
                $idRolPermisos = (int)($_POST['id_rol'] ?? 0);
                if ($idRolPermisos <= 0) throw new InvalidArgumentException('Selecciona un rol válido.');
                $stmt = $conn->prepare('SELECT id_rol, nombre_rol FROM roles WHERE id_rol = ?');
                $stmt->bind_param('i', $idRolPermisos);
                $stmt->execute();
                $rolPermisos = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$rolPermisos) throw new InvalidArgumentException('El rol seleccionado no existe.');

                $valores = $_POST['permisos'] ?? [];
                $stmt = $conn->prepare('INSERT INTO permisos_rol (id_rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE puede_ver=VALUES(puede_ver), puede_crear=VALUES(puede_crear), puede_editar=VALUES(puede_editar), puede_eliminar=VALUES(puede_eliminar), puede_exportar=VALUES(puede_exportar)');
                $conn->begin_transaction();
                foreach (modulosSysmovil() as $modulo) {
                    $datos = is_array($valores[$modulo] ?? null) ? $valores[$modulo] : [];
                    $ver = isset($datos['ver']) ? 1 : 0;
                    $crear = $ver && isset($datos['crear']) ? 1 : 0;
                    $editar = $ver && isset($datos['editar']) ? 1 : 0;
                    $eliminar = $ver && isset($datos['eliminar']) ? 1 : 0;
                    $exportar = $ver && isset($datos['exportar']) ? 1 : 0;
                    $stmt->bind_param('isiiiii', $idRolPermisos, $modulo, $ver, $crear, $editar, $eliminar, $exportar);
                    $stmt->execute();
                }
                $stmt->close();
                $conn->commit();
                registrarActividadSistema($conn, 'Configuración', 'Permisos actualizados', 'Se actualizaron los permisos del rol ' . $rolPermisos['nombre_rol'] . '.');
                header('Location: configuracion.php?rol_permisos=' . $idRolPermisos . '&guardado=permisos#tab-roles');
                exit;
            } else {
                throw new InvalidArgumentException('La acción solicitada no está disponible.');
            }
        } catch (InvalidArgumentException $e) {
            $mensajeConfiguracion = $e->getMessage();
            $tipoMensajeConfiguracion = 'error';
        } catch (mysqli_sql_exception $e) {
            error_log('SYSMOVILWT configuración: ' . $e->getMessage());
            if ($conn->errno) { try { $conn->rollback(); } catch (Throwable $ignorado) {} }
            $mensajeConfiguracion = $e->getCode() === 1062
                ? 'Ya existe un rol con ese nombre.'
                : 'No fue posible guardar los cambios. Verifica la migración y los datos enviados.';
            $tipoMensajeConfiguracion = 'error';
            if ($e->getCode() === 1146) {
                $tablaConfiguracionDisponible = false;
            }
        }
    }
}

/* =========================================================
   ACCIÓN: LEER CONFIGURACIÓN DE EMPRESA
   ========================================================= */
try {
    $resultadoEmpresa = $conn->query(
        'SELECT nombre_empresa, nit, telefono, correo, direccion
         FROM configuracion_sistema WHERE id_configuracion = 1'
    )->fetch_assoc();
    if ($resultadoEmpresa) {
        $empresa = array_merge($empresa, $resultadoEmpresa);
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1146) {
        $tablaConfiguracionDisponible = false;
    } else {
        error_log('SYSMOVILWT al cargar configuración de empresa: ' . $e->getMessage());
        $tablaConfiguracionDisponible = false;
    }
}

/* =========================================================
   ACCIÓN: LISTAR ROLES Y PREPARAR EDICIÓN
   ========================================================= */
$rolesConfiguracion = $conn->query(
    'SELECT id_rol, nombre_rol, descripcion FROM roles ORDER BY id_rol'
)->fetch_all(MYSQLI_ASSOC);

$idRolPermisosSeleccionado = (int)($_GET['rol_permisos'] ?? ($rolesConfiguracion[0]['id_rol'] ?? 0));
$permisosRolSeleccionado = [];
if ($idRolPermisosSeleccionado > 0) {
    $permisosRolSeleccionado = obtenerPermisosSysmovil($conn, $idRolPermisosSeleccionado);
}
$modulosPermisosConfiguracion = [
    'dashboard' => ['Dashboard', 'fa-house'],
    'inventario' => ['Inventario', 'fa-boxes-stacked'],
    'ventas' => ['Ventas', 'fa-cart-shopping'],
    'compras' => ['Compras', 'fa-bag-shopping'],
    'clientes' => ['Clientes', 'fa-users'],
    'proveedores' => ['Proveedores', 'fa-truck'],
    'usuarios' => ['Usuarios', 'fa-user-shield'],
    'servicio_tecnico' => ['Servicio Técnico', 'fa-screwdriver-wrench'],
    'reportes' => ['Reportes', 'fa-chart-column'],
    'configuracion' => ['Configuración', 'fa-gear'],
];

if (isset($_GET['editar_rol'])) {
    $idRolEditar = (int)$_GET['editar_rol'];
    if ($idRolEditar > 0) {
        $stmt = $conn->prepare('SELECT id_rol, nombre_rol, descripcion FROM roles WHERE id_rol = ?');
        $stmt->bind_param('i', $idRolEditar);
        $stmt->execute();
        $rolEditar = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}

/* =========================================================
   ACCIÓN: ESCAPAR VALORES ANTES DE MOSTRARLOS
   ========================================================= */
function escaparConfiguracion(mixed $valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
