<?php
$server = "localhost";
$usuario = "root";
$contrasenia = "";
$base_de_datos = "refugio_mascotas";

$conexion = @new mysqli($server, $usuario, $contrasenia, $base_de_datos);

// Verificamos la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (!$conexion->set_charset("utf8")) {
    die("Error cargando el conjunto de caracteres utf8: " . $conexion->error);
}

// Opcional: Configurar la zona horaria
$conexion->query("SET time_zone = '+00:00'");
