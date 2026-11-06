<?php
/**
 * Configuración SMTP para recuperación de contraseña.
 * Define estas variables en Apache/PHP; no guardes contraseñas SMTP en Git.
 * En Gmail usa una contraseña de aplicación, no la contraseña normal.
 */
$local = [];
$archivoLocal = __DIR__ . '/correo.local.php';
if (is_file($archivoLocal)) {
    $configLocal = require $archivoLocal;
    if (is_array($configLocal)) $local = $configLocal;
}

return array_merge([
    'host' => getenv('SYSMOVILWT_SMTP_HOST') ?: '',
    'port' => (int)(getenv('SYSMOVILWT_SMTP_PORT') ?: 587),
    'encryption' => strtolower(getenv('SYSMOVILWT_SMTP_ENCRYPTION') ?: 'tls'), // tls (587) o ssl (465)
    'username' => getenv('SYSMOVILWT_SMTP_USER') ?: '',
    'password' => getenv('SYSMOVILWT_SMTP_PASSWORD') ?: '',
    'from_address' => getenv('SYSMOVILWT_MAIL_FROM') ?: (getenv('SYSMOVILWT_SMTP_USER') ?: ''),
    'from_name' => getenv('SYSMOVILWT_MAIL_NAME') ?: 'SYSMOVILWT',
    'base_url' => rtrim(getenv('SYSMOVILWT_BASE_URL') ?: 'http://localhost/SYSMOVILWT_Proyecto', '/'),
], $local, [
    // Las variables de entorno, si existen, prevalecen sobre el archivo local.
    'host' => getenv('SYSMOVILWT_SMTP_HOST') ?: ($local['host'] ?? ''),
    'port' => (int)(getenv('SYSMOVILWT_SMTP_PORT') ?: ($local['port'] ?? 587)),
    'encryption' => strtolower(getenv('SYSMOVILWT_SMTP_ENCRYPTION') ?: ($local['encryption'] ?? 'tls')),
    'username' => getenv('SYSMOVILWT_SMTP_USER') ?: ($local['username'] ?? ''),
    'password' => getenv('SYSMOVILWT_SMTP_PASSWORD') ?: ($local['password'] ?? ''),
    'from_address' => getenv('SYSMOVILWT_MAIL_FROM') ?: ($local['from_address'] ?? (getenv('SYSMOVILWT_SMTP_USER') ?: ($local['username'] ?? ''))),
    'from_name' => getenv('SYSMOVILWT_MAIL_NAME') ?: ($local['from_name'] ?? 'SYSMOVILWT'),
    'base_url' => rtrim(getenv('SYSMOVILWT_BASE_URL') ?: ($local['base_url'] ?? 'http://localhost/SYSMOVILWT_Proyecto'), '/'),
]);
