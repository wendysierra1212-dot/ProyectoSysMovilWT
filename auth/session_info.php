<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';

$usuario = exigirInicioSesion($conn);
$usuario['permisos'] = obtenerPermisosSysmovil($conn, (int)$usuario['id_rol']);

echo json_encode(
    ['ok' => true, 'usuario' => $usuario],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
