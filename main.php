<?php
require_once 'config.php';

function obtenerdatos($urlInicial) {
    $todosLosdatos = [];
    $urlSiguiente = $urlInicial;

    while ($urlSiguiente !== null) {
        $respuesta = false;

        for ($intento = 1; $intento <= 3; $intento++) {
            $respuesta = file_get_contents($urlSiguiente);

            if ($respuesta !== false) {
                break;
            }

            sleep($intento * 2);
        }

        if ($respuesta === false) {
            break;
        }

        $datos = json_decode($respuesta, true);
        $todosLosdatos = array_merge($todosLosdatos, $datos['results']);
        $urlSiguiente = $datos['info']['next'];
        usleep(250000);
    }
    return $todosLosdatos;
}

function sincronizarPersonajes($conexion, $porcentajePersonajes = 100) {
    try {
        $stmtAntes = $conexion->query("SELECT COUNT(*) FROM CHARACTERS");
        $cantidadAntes = $stmtAntes->fetchColumn();

        $locaciones = obtenerdatos('https://rickandmortyapi.com/api/location');

        $conexion->beginTransaction();
        $stmtLoc = $conexion->prepare("INSERT INTO LOCATION (id_location,name,type,dimension)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            type = VALUES(type),
            dimension = VALUES(dimension)
    ");
        foreach ($locaciones as $locacion) {
            $stmtLoc->execute([$locacion['id'], $locacion['name'], $locacion['type'], $locacion['dimension']]);
        }
        $conexion->commit();


        $episodios = obtenerdatos('https://rickandmortyapi.com/api/episode');

        $conexion->beginTransaction();
        $stmtEp = $conexion->prepare("INSERT INTO EPISODE ( id_episode, name, air_date, episode)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            air_date = VALUES(air_date),
            episode = VALUES(episode)
    ");
        foreach ($episodios as $episodio) {
            $stmtEp->execute([$episodio['id'], $episodio['name'], $episodio['air_date'], $episodio['episode']]);
        }
        $conexion->commit();


        $personajes = obtenerdatos('https://rickandmortyapi.com/api/character');

        if ($porcentajePersonajes < 100) {
            $cantidadACargar = ceil(count($personajes) * $porcentajePersonajes / 100);
            $personajes = array_slice($personajes, 0, $cantidadACargar);
        }

        $conexion->beginTransaction();

        $stmtChar = $conexion->prepare("INSERT INTO CHARACTERS (id_character,name,status,species,type,gender,image,id_origin_location,id_current_location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            status = VALUES(status),
            species = VALUES(species),
            type = VALUES(type),
            gender = VALUES(gender),
            image = VALUES(image),
            id_origin_location = VALUES(id_origin_location),
            id_current_location = VALUES(id_current_location)
    ");

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

        $stmtDespues = $conexion->query("SELECT COUNT(*) FROM CHARACTERS");
        $cantidadDespues = $stmtDespues->fetchColumn();

        return $cantidadDespues - $cantidadAntes;

    } catch (PDOException $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack(); // Si hay error, cancelamos la caja para no corromper la BD
        }
        return -1;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    sincronizarPersonajes($conexion);
}
?>
