<?PHP

$ipBD = "127.0.0.1";
$usuarioBD = "rick_and_morty"; 
$claveBD = "root";            
$nombreBD = "rick_and_morty"; 
try {
    $conexion = new PDO("mysql:host=" . $ipBD . ";dbname=" . $nombreBD, $usuarioBD, $claveBD);
    
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    
   die("Error al conectar con la base de datos: " . $e->getMessage());
}

?>