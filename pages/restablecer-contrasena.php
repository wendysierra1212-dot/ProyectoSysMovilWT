<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$token = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$error = '';
$resultado = '';
$tokenValido = false;

if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $conn->prepare('SELECT id_reset FROM password_reset_tokens WHERE token_hash = ? AND vence_en > NOW() AND utilizado_en IS NULL LIMIT 1');
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $tokenValido = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Throwable $e) {
        $error = 'No está disponible la recuperación. Verifica la migración 003 de autenticación.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $nueva = (string)($_POST['contrasena'] ?? '');
    $confirmar = (string)($_POST['confirmar'] ?? '');
    if (!validarCsrfSysmovil($_POST['csrf_token'] ?? null)) {
        $error = 'La página venció. Recarga el enlace e inténtalo de nuevo.';
    } elseif (!$tokenValido) {
        $error = 'El enlace no es válido, ya se usó o venció. Solicita uno nuevo.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!hash_equals($nueva, $confirmar)) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $conn->begin_transaction();
            $tokenHash = hash('sha256', $token);
            $stmt = $conn->prepare('SELECT id_reset, id_usuario FROM password_reset_tokens WHERE token_hash = ? AND vence_en > NOW() AND utilizado_en IS NULL FOR UPDATE');
            $stmt->bind_param('s', $tokenHash);
            $stmt->execute();
            $registro = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$registro) throw new RuntimeException('El enlace ya no está disponible. Solicita uno nuevo.');

            $hashContrasena = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE usuarios SET contrasena = ? WHERE id_usuario = ? AND estado = \'Activo\'');
            $stmt->bind_param('si', $hashContrasena, $registro['id_usuario']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) throw new RuntimeException('La cuenta no está activa. Contacta al administrador.');
            $stmt->close();

            $stmt = $conn->prepare('UPDATE password_reset_tokens SET utilizado_en = NOW() WHERE id_usuario = ? AND utilizado_en IS NULL');
            $stmt->bind_param('i', $registro['id_usuario']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            $tokenValido = false;
            $resultado = 'Contraseña actualizada. Ya puedes iniciar sesión con tu nueva contraseña.';
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignorado) {}
            if ($e instanceof mysqli_sql_exception) {
                error_log('SYSMOVILWT al restablecer contraseña: ' . $e->getMessage());
                $error = 'No fue posible actualizar la contraseña. Recarga el enlace e inténtalo de nuevo.';
            } else {
                $error = $e->getMessage();
            }
        }
    }
}
$csrf = tokenCsrfSysmovil();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nueva contraseña | SYSMOVILWT</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"><link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/login.css">
<style>.recovery-card{max-width:480px}.recovery-card .form-control{width:100%}.recovery-message{margin:14px 0;padding:12px;border-radius:9px;background:#fee2e2;color:#991b1b;font-size:13px}.recovery-message.success{background:#dcfce7;color:#166534}.recovery-card .login-btn{margin-top:12px}</style></head>
<body class="login-page"><div class="login-container"><section class="login-right"><div class="login-card recovery-card"><div class="login-logo"><div class="logo-circle">WT</div></div><h2>Nueva contraseña</h2>
<?php if ($resultado !== ''): ?><div class="recovery-message success" role="status"><?= htmlspecialchars($resultado, ENT_QUOTES, 'UTF-8') ?></div><a class="btn btn-primary login-btn" href="../login.html">Ir al inicio de sesión</a>
<?php elseif ($error !== ''): ?><div class="recovery-message" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><a class="btn btn-primary login-btn" href="solicitar-restablecimiento.php">Solicitar un enlace nuevo</a>
<?php elseif ($tokenValido): ?><p class="login-subtitle">Elige una contraseña de al menos 6 caracteres.</p><form method="post"><input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><div class="form-group"><label for="contrasena">Nueva contraseña</label><input class="form-control" type="password" id="contrasena" name="contrasena" required minlength="6" autocomplete="new-password"></div><div class="form-group"><label for="confirmar">Confirmar contraseña</label><input class="form-control" type="password" id="confirmar" name="confirmar" required minlength="6" autocomplete="new-password"></div><button class="btn btn-primary login-btn" type="submit"><i class="fa-solid fa-key"></i> Guardar contraseña</button></form>
<?php else: ?><div class="recovery-message" role="alert">El enlace no es válido, ya se usó o venció. Solicita uno nuevo.</div><a class="btn btn-primary login-btn" href="solicitar-restablecimiento.php">Solicitar un enlace nuevo</a><?php endif; ?>
<div class="login-footer">© 2026 WT SERVIPHONE</div></div></section></div></body></html>
