<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';
require_once __DIR__ . '/../config/mailer.php';

$mensaje = '';
$identificador = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = trim((string)($_POST['identificador'] ?? ''));
    if (!validarCsrfSysmovil($_POST['csrf_token'] ?? null)) {
        $mensaje = 'La página venció. Recárgala e inténtalo de nuevo.';
    } elseif ($identificador === '') {
        $mensaje = 'Escribe tu nombre de usuario o correo.';
    } else {
        // El mensaje es igual para cuentas existentes e inexistentes para no revelar usuarios.
        $mensaje = 'Si existe una cuenta activa con ese usuario/correo y tiene un email registrado, enviaremos un enlace de recuperación. Revisa también la carpeta de spam.';
        try {
            $stmt = $conn->prepare("SELECT u.id_usuario, u.nombre_usuario, e.nombres, e.correo
                                    FROM usuarios u INNER JOIN empleados e ON e.id_empleado = u.id_empleado
                                    WHERE (u.nombre_usuario = ? OR e.correo = ?)
                                      AND u.estado = 'Activo' AND e.estado = 'Activo'
                                    LIMIT 1");
            $stmt->bind_param('ss', $identificador, $identificador);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario && filter_var((string)$usuario['correo'], FILTER_VALIDATE_EMAIL)) {
                $stmt = $conn->prepare('SELECT COUNT(*) FROM password_reset_tokens WHERE id_usuario = ? AND creado_en >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
                $stmt->bind_param('i', $usuario['id_usuario']);
                $stmt->execute();
                $intentos = (int)$stmt->get_result()->fetch_row()[0];
                $stmt->close();

                if ($intentos < 3) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $stmt = $conn->prepare('UPDATE password_reset_tokens SET utilizado_en = NOW() WHERE id_usuario = ? AND utilizado_en IS NULL');
                    $stmt->bind_param('i', $usuario['id_usuario']);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare('INSERT INTO password_reset_tokens (id_usuario, token_hash, vence_en) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
                    $stmt->bind_param('is', $usuario['id_usuario'], $tokenHash);
                    $stmt->execute();
                    $idReset = (int)$conn->insert_id;
                    $stmt->close();

                    $configCorreo = require __DIR__ . '/../config/correo.php';
                    $enlace = $configCorreo['base_url'] . '/pages/restablecer-contrasena.php?token=' . rawurlencode($token);
                    $nombre = htmlspecialchars((string)$usuario['nombres'], ENT_QUOTES, 'UTF-8');
                    $urlHtml = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
                    $html = '<!doctype html><html lang="es"><meta charset="utf-8"><body style="font-family:Arial,sans-serif;color:#172033">'
                        . '<h2>Restablecer contraseña de SYSMOVILWT</h2><p>Hola ' . $nombre . '.</p>'
                        . '<p>Recibimos una solicitud para cambiar la contraseña de tu cuenta. El enlace vence en 30 minutos y solo puede usarse una vez.</p>'
                        . '<p><a href="' . $urlHtml . '" style="display:inline-block;background:#c62828;color:white;padding:12px 18px;border-radius:7px;text-decoration:none">Crear contraseña nueva</a></p>'
                        . '<p>Si no solicitaste el cambio, ignora este mensaje. No compartas este enlace.</p></body></html>';
                    try {
                        enviarCorreoSysmovil((string)$usuario['correo'], 'Restablecer contraseña de SYSMOVILWT', $html);
                    } catch (Throwable $e) {
                        $stmt = $conn->prepare('UPDATE password_reset_tokens SET utilizado_en = NOW() WHERE id_reset = ?');
                        $stmt->bind_param('i', $idReset);
                        $stmt->execute();
                        $stmt->close();
                        error_log('SYSMOVILWT recuperación: ' . $e->getMessage());
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('SYSMOVILWT solicitud de recuperación: ' . $e->getMessage());
            $mensaje = 'No fue posible procesar la solicitud. Verifica que la migración 003 de autenticación esté instalada.';
        }
    }
}
$csrf = tokenCsrfSysmovil();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Recuperar contraseña | SYSMOVILWT</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"><link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/login.css">
<style>.recovery-card{max-width:480px}.recovery-card .form-control{width:100%}.recovery-message{margin:14px 0;padding:12px;border-radius:9px;background:#eff6ff;color:#1e3a8a;font-size:13px}.recovery-card .login-btn{margin-top:12px}</style></head>
<body class="login-page"><div class="login-container"><section class="login-right"><div class="login-card recovery-card"><div class="login-logo"><div class="logo-circle">WT</div></div><h2>¿Olvidaste tu contraseña?</h2><p class="login-subtitle">Te enviaremos un enlace al correo asociado a tu usuario.</p>
<?php if ($mensaje !== ''): ?><div class="recovery-message" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><div class="form-group"><label for="identificador"><i class="fa-solid fa-user"></i> Usuario o correo</label><input class="form-control" type="text" id="identificador" name="identificador" value="<?= htmlspecialchars($identificador, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="username"></div><button class="btn btn-primary login-btn" type="submit"><i class="fa-solid fa-envelope"></i> Enviar enlace</button></form>
<p class="login-setup-link"><a href="../login.html">Volver al inicio de sesión</a></p><div class="login-footer">© 2026 WT SERVIPHONE</div></div></section></div></body></html>
