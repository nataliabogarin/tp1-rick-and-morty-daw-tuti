<?php

$ipBD = getenv('DB_HOST') ?: '127.0.0.1';
$usuarioBD = getenv('DB_USER') ?: 'root';
$claveBD = getenv('DB_PASS');
$claveBD = $claveBD === false ? '' : $claveBD;
$nombreBD = getenv('DB_NAME') ?: 'rick_and_morty';

try {
    $conexion = new PDO("mysql:host=" . $ipBD . ";dbname=" . $nombreBD, $usuarioBD, $claveBD);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
