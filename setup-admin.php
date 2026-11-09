<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/autenticacion.php';
require_once __DIR__ . '/config/actividad.php';
iniciarSesionSysmovil();
$error = '';
$configurado = (int)$conn->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] > 0;
$csrf = tokenCsrfSysmovil();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$configurado) {
    if (!validarCsrfSysmovil($_POST['csrf_token'] ?? null)) {
        $error = 'La página venció. Recárgala e inténtalo de nuevo.';
    } else {
        $cedula = trim((string)($_POST['cedula'] ?? ''));
        $nombres = trim((string)($_POST['nombres'] ?? ''));
        $apellidos = trim((string)($_POST['apellidos'] ?? ''));
        $correo = trim((string)($_POST['correo'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $nombreUsuario = trim((string)($_POST['nombre_usuario'] ?? ''));
        $contrasena = (string)($_POST['contrasena'] ?? '');
        $confirmar = (string)($_POST['confirmar'] ?? '');

        if ($cedula === '' || $nombres === '' || $apellidos === '' || $nombreUsuario === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Completa los datos y escribe un correo válido para el administrador.';
        } elseif (strlen($contrasena) < 6) {
            $error = 'La contraseña inicial debe tener al menos 6 caracteres.';
        } elseif (!hash_equals($contrasena, $confirmar)) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                $conn->begin_transaction();
                if ((int)$conn->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0] > 0) {
                    throw new RuntimeException('Ya se creó una cuenta. El asistente inicial está cerrado.');
                }

                $resultadoRol = $conn->query("SELECT id_rol, nombre_rol FROM roles WHERE LOWER(nombre_rol) LIKE '%admin%' ORDER BY id_rol LIMIT 1")->fetch_assoc();
                if (!$resultadoRol) {
                    $stmt = $conn->prepare("INSERT INTO roles (nombre_rol, descripcion) VALUES ('Administrador', 'Acceso completo al sistema')");
                    $stmt->execute();
                    $idRol = (int)$conn->insert_id;
                    $nombreRol = 'Administrador';
                    $stmt->close();
                } else {
                    $idRol = (int)$resultadoRol['id_rol'];
                    $nombreRol = (string)$resultadoRol['nombre_rol'];
                }

                $stmt = $conn->prepare("INSERT INTO empleados (cedula, nombres, apellidos, cargo, telefono, correo, estado) VALUES (?, ?, ?, 'Administrador', NULLIF(?, ''), ?, 'Activo')");
                $stmt->bind_param('sssss', $cedula, $nombres, $apellidos, $telefono, $correo);
                $stmt->execute();
                $idEmpleado = (int)$conn->insert_id;
                $stmt->close();

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, contrasena, estado, id_empleado, id_rol) VALUES (?, ?, 'Activo', ?, ?)");
                $stmt->bind_param('ssii', $nombreUsuario, $hash, $idEmpleado, $idRol);
                $stmt->execute();
                $idUsuario = (int)$conn->insert_id;
                $stmt->close();

                permisosInicialesRol($conn, $idRol, $nombreRol);
                $conn->commit();
                registrarActividadSistema($conn, 'Usuarios', 'Configuración inicial', 'Se creó la cuenta de administrador inicial.', $idUsuario, $nombreUsuario);
                header('Location: login.html?inicializado=1');
                exit;
            } catch (Throwable $e) {
                try { $conn->rollback(); } catch (Throwable $ignorado) {}
                $error = $e instanceof mysqli_sql_exception && $e->getCode() === 1062
                    ? 'La cédula, el correo o el nombre de usuario ya están registrados.'
                    : ($e instanceof mysqli_sql_exception && $e->getCode() === 1146
                        ? 'Falta instalar la migración de autenticación. Ejecuta database/003_autenticacion_permisos.sql en MySQL Workbench y vuelve a cargar esta página.'
                        : ($e instanceof RuntimeException && !($e instanceof mysqli_sql_exception)
                            ? $e->getMessage()
                            : 'No fue posible crear la cuenta inicial. Revisa los datos e inténtalo de nuevo.'));
                if ($e instanceof mysqli_sql_exception) {
                    error_log('SYSMOVILWT configuración del administrador: ' . $e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador inicial | SYSMOVILWT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css"><link rel="stylesheet" href="assets/css/login.css">
    <style>.setup-card{max-width:560px}.setup-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.setup-card .form-group{margin-bottom:12px}.setup-card .form-control{width:100%}.setup-error{padding:12px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:12px 0}.setup-note{font-size:12px;color:#64748b;margin:10px 0 18px}@media(max-width:600px){.setup-grid{grid-template-columns:1fr}}</style>
</head><body class="login-page"><div class="login-container"><section class="login-right"><div class="login-card setup-card">
    <div class="login-logo"><div class="logo-circle">WT</div></div>
    <h2>Administrador inicial</h2>
    <p class="login-subtitle">Crea la primera cuenta de acceso al sistema.</p>
    <?php if ($configurado): ?>
        <div class="setup-error" style="background:#dcfce7;color:#166534">La configuración inicial ya se completó. No es posible crear otra cuenta desde este asistente.</div>
        <a class="btn btn-primary login-btn" href="login.html">Ir al inicio de sesión</a>
    <?php else: ?>
        <?php if ($error !== ''): ?><div class="setup-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <p class="setup-note">Este formulario se desactiva automáticamente cuando se crea el primer usuario. El correo será necesario para recuperar la contraseña.</p>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="setup-grid">
                <div class="form-group"><label for="cedula">Cédula</label><input class="form-control" id="cedula" name="cedula" required maxlength="20"></div>
                <div class="form-group"><label for="correo">Correo</label><input class="form-control" type="email" id="correo" name="correo" required maxlength="100"></div>
                <div class="form-group"><label for="nombres">Nombres</label><input class="form-control" id="nombres" name="nombres" required maxlength="100"></div>
                <div class="form-group"><label for="apellidos">Apellidos</label><input class="form-control" id="apellidos" name="apellidos" required maxlength="100"></div>
                <div class="form-group"><label for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" maxlength="20"></div>
                <div class="form-group"><label for="nombre_usuario">Usuario de acceso</label><input class="form-control" id="nombre_usuario" name="nombre_usuario" required maxlength="50" autocomplete="username"></div>
                <div class="form-group"><label for="contrasena">Contraseña (mínimo 6 caracteres)</label><input class="form-control" type="password" id="contrasena" name="contrasena" required minlength="6" autocomplete="new-password"></div>
                <div class="form-group"><label for="confirmar">Confirmar contraseña</label><input class="form-control" type="password" id="confirmar" name="confirmar" required minlength="6" autocomplete="new-password"></div>
            </div>
            <button class="btn btn-primary login-btn" type="submit"><i class="fa-solid fa-user-shield"></i> Crear administrador</button>
        </form>
    <?php endif; ?>
    <div class="login-footer">© 2026 WT SERVIPHONE</div>
</div></section></div></body></html>
