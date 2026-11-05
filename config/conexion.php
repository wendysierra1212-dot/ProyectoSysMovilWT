<?php

$host = "localhost";
$usuario = "sysmovilwt";
$password = "SysMovilWT2026";
$base_datos = "sysmovilwt_v2";

$conexion = new mysqli($host, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

// Compatibilidad con los módulos que usan $conn y los que usan $conexion.
$conn = $conexion;

?>
