<?php


//variables con los datos de la conexion
$ipBD = "127.0.0.1";
$usuarioBD = "pruebaconexion"; 
$claveBD = "root";            
$nombreBD = "pruebaconexion"; 

// creacion de la conexion
try {
    $conexion = new PDO("mysql:host=" . $ipBD . ";dbname=" . $nombreBD, $usuarioBD, $claveBD);
    
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    
   die("Error al conectar con la base de datos: " . $e->getMessage());
}
?>