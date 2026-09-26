<?php
require_once 'config.php';

function obtenerdatos($urlInicial) {
    $todosLosdatos = [];
    $urlSiguiente = $urlInicial;

    while ($urlSiguiente !== null) {
        echo "Descargando: " . $urlSiguiente . "...\n";
        $respuesta = file_get_contents($urlSiguiente);
        
        if ($respuesta === false) {
            echo "Error al descargar: $urlSiguiente\n";
            break;
        }

        $datos = json_decode($respuesta, true);
        $todosLosdatos = array_merge($todosLosdatos, $datos['results']);
        $urlSiguiente = $datos['info']['next'];
    }
    return $todosLosdatos;
}

try {
   
    echo "\n--- descargando locations ---\n";
    $locaciones = obtenerdatos('https://rickandmortyapi.com/api/location');
    
    $conexion->beginTransaction(); 
    $stmtLoc = $conexion->prepare("INSERT IGNORE INTO LOCATION (id_location, name, type, dimension) VALUES (?, ?, ?, ?)");
    foreach ($locaciones as $locacion) {
        $stmtLoc->execute([$locacion['id'], $locacion['name'], $locacion['type'], $locacion['dimension']]);
    }
    $conexion->commit(); 

  
    echo "\n--- descargando episodes ---\n";
    $episodios = obtenerdatos('https://rickandmortyapi.com/api/episode');
    
    $conexion->beginTransaction(); 
    $stmtEp = $conexion->prepare("INSERT IGNORE INTO EPISODE (id_episode, name, air_date, episode) VALUES (?, ?, ?, ?)");
    foreach ($episodios as $episodio) {
        $stmtEp->execute([$episodio['id'], $episodio['name'], $episodio['air_date'], $episodio['episode']]);
    }
    $conexion->commit(); 

   
    echo "\n--- descargando characters ---\n";
    $personajes = obtenerdatos('https://rickandmortyapi.com/api/character');
    
    $conexion->beginTransaction(); 
    $stmtChar = $conexion->prepare("INSERT IGNORE INTO CHARACTERS (id_character, name, status, species, type, gender, image, id_origin_location, id_current_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtRelacion = $conexion->prepare("INSERT IGNORE INTO CHARACTERS_EPISODE (id_character, id_episode) VALUES (?, ?)");

    foreach ($personajes as $personaje) {
        $id_origen = !empty($personaje['origin']['url']) ? basename($personaje['origin']['url']) : null;
        $id_locacion = !empty($personaje['location']['url']) ? basename($personaje['location']['url']) : null;

        $stmtChar->execute([
            $personaje['id'], $personaje['name'], $personaje['status'], $personaje['species'],
            $personaje['type'], $personaje['gender'], $personaje['image'], $id_origen, $id_locacion
        ]);

        foreach ($personaje['episode'] as $urlEpisodio) {
            $id_episodio = basename($urlEpisodio); 
            $stmtRelacion->execute([$personaje['id'], $id_episodio]);
        }
    }
    $conexion->commit(); // <-- GUARDAMOS LOS 3000 REGISTROS DE GOLPE

    echo "\nProceso completo con exito\n";

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack(); // Si hay error, cancelamos la caja para no corromper la BD
    }
    echo "Error en la base de datos: " . $e->getMessage();
}
?>