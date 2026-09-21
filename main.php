<?php

include_once 'config.php';

// 2. URL de la API externa
$url_api = "https://rickandmortyapi.com/api/character";

//se guardan los datos de la api en $json datos.
$json_datos = file_get_contents($url_api);

// se guarda el json en la tabla informacion api de la BD
$sql = "INSERT INTO informacion_api (datos_json) VALUES (:json)";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(':json', $json_datos);
$stmt->execute();

// 5. Sacamos el último registro guardado para comprobar que funcionó
$sql = "SELECT datos_json FROM informacion_api ORDER BY id DESC LIMIT 1";
$resultado = $conexion->query($sql)->fetch(PDO::FETCH_ASSOC);

$json_guardado = $resultado['datos_json']; 

//se convierte el json en un array
$datos_php = json_decode($json_guardado, true);

?>

