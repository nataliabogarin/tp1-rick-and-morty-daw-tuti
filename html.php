<?php 
include_once 'main.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos de Rick and Morty</title>
    <style>
        
        .personaje-foto {
            width: 100px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 15px;
        }
        li {
            margin-bottom: 20px;
            list-style: none;
        }
    </style>
</head>
<body>
   
    <ul>
        <?php
        // Recorremos los datos
        foreach ($datos_php['results'] as $personaje) {
            
            
            $nombre  = $personaje['name'];
            $especie = $personaje['species'];
            $estado  = $personaje['status'];
            $genero  = $personaje['gender'];
            $imagen  = $personaje['image']; 
       
            echo "<li>";
            echo "<img src='$imagen' class='personaje-foto' alt='Foto de $nombre'>";
            echo "<strong>Nombre:</strong> $nombre | ";
            echo "<strong>Especie:</strong> $especie | ";
            echo "<strong>Estado:</strong> $estado | ";
            echo "<strong>Género:</strong> $genero";
            echo "</li>";
        }
        ?>
    </ul>
</body>
</html>
