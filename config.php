<?PHP

$ipBD = "127.0.0.1";
$usuarioBD = "root"; 
$claveBD = "admin";            
$nombreBD = "rick_and_morty"; 
try {
    $conexion = new PDO("mysql:host=" . $ipBD . ";dbname=" . $nombreBD, $usuarioBD, $claveBD);
    
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "conexion exitosa";
} catch (PDOException $e) {
    
   die("Error al conectar con la base de datos: " . $e->getMessage());
}

?>