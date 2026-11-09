<?php
declare(strict_types=1);

/**
 * La configuración sensible se carga desde database.local.php (ignorado por Git)
 * o desde variables de entorno. Consulta database.local.example.php.
 */
$configLocal = [];
$archivoLocal = __DIR__ . '/database.local.php';
if (is_file($archivoLocal)) {
    $configCargada = require $archivoLocal;
    if (is_array($configCargada)) {
        $configLocal = $configCargada;
    }
}

$host = getenv('SYSMOVILWT_DB_HOST') ?: ($configLocal['host'] ?? 'localhost');
$usuario = getenv('SYSMOVILWT_DB_USER') ?: ($configLocal['usuario'] ?? '');
$passwordEntorno = getenv('SYSMOVILWT_DB_PASSWORD');
$password = $passwordEntorno !== false ? $passwordEntorno : (string)($configLocal['password'] ?? '');
$base_datos = getenv('SYSMOVILWT_DB_NAME') ?: ($configLocal['base_datos'] ?? '');

if ($usuario === '' || $base_datos === '') {
    http_response_code(500);
    exit('Falta configurar la conexión MySQL. Copia config/database.local.example.php como config/database.local.php y completa los datos locales.');
}

$conexion = new mysqli($host, $usuario, $password, $base_datos);
if ($conexion->connect_error) {
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Revisa la configuración local de MySQL.');
}

$conexion->set_charset('utf8mb4');

// Compatibilidad con los módulos que usan $conn y los que usan $conexion.
$conn = $conexion;
