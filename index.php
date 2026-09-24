<?php
require_once 'config.php';

// ====================================================================
// 1. RESPUESTA PARA EL JAVASCRIPT (Solo se ejecuta al tocar un botón)
// ====================================================================
if (isset($_GET['ajax_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$_GET['ajax_id'];$sqlDetalle = "
        SELECT c.id_character, c.name, c.status, c.species, c.type, c.gender, c.image, o.name AS origin_name
        FROM CHARACTERS c
        LEFT JOIN LOCATION o ON c.id_origin_location = o.id_location
        WHERE c.id_character = ?
    ";
    $stmtDetalle = $conexion->prepare($sqlDetalle);
    $stmtDetalle->execute([$id]);
    $personaje =$stmtDetalle->fetch(PDO::FETCH_ASSOC);

    if ($personaje) {
        $stmtEp =$conexion->prepare("
            SELECT e.name, e.episode FROM EPISODE e
            JOIN CHARACTERS_EPISODE ce ON e.id_episode = ce.id_episode
            WHERE ce.id_character = ? ORDER BY e.id_episode ASC
        ");
        $stmtEp->execute([$id]);
        $episodios =$stmtEp->fetchAll(PDO::FETCH_ASSOC);

        $personaje['first_episode'] = !empty($episodios) ? $episodios[0]['name'] . " (" . $episodios[0]['episode'] . ")" : "Desconocido";
        $personaje['last_episode']  = !empty($episodios) ? end($episodios)['name'] . " (" . end($episodios)['episode'] . ")" : "Desconocido";
    }
    
    echo json_encode($personaje);
    exit; // Terminamos aquí para que solo devuelva los datos puros al JS
}

// ====================================================================
// 2. CÓDIGO NORMAL DE PHP (Carga inicial de la página)
// ====================================================================
$stmtTemporadas =$conexion->query("SELECT DISTINCT SUBSTRING(episode, 1, 3) AS season_code FROM EPISODE ORDER BY season_code ASC");
$temporadas =$stmtTemporadas->fetchAll(PDO::FETCH_COLUMN);

$temporadaActual = isset($_GET['season']) ?$_GET['season'] : (isset($temporadas[0]) ?$temporadas[0] : 'S01');

$sqlChars = "
    SELECT DISTINCT c.id_character, c.name 
    FROM CHARACTERS c
    JOIN CHARACTERS_EPISODE ce ON c.id_character = ce.id_character
    JOIN EPISODE e ON ce.id_episode = e.id_episode
    WHERE e.episode LIKE ?
    ORDER BY c.name ASC
";
$stmtChars = $conexion->prepare($sqlChars);
$stmtChars->execute([$temporadaActual . '%']);
$personajes =$stmtChars->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rick and Morty - Híbrido</title>
    <style>
        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #121418; color: #e2e8f0; }
        .contenedor { display: flex; gap: 20px; max-width: 1000px; margin: 0 auto; }
        
        .panel-izquierdo { width: 300px; background-color: #1a1e24; border: 1px solid #2d333b; padding: 15px; border-radius: 8px; display: flex; flex-direction: column; }
        .form-temporada select { width: 100%; padding: 8px; background: #0f1216; color: white; border: 1px solid #3b4252; margin-bottom: 15px; cursor: pointer;}
        
        .lista-personajes { list-style: none; margin: 0; padding: 0; max-height: 500px; overflow-y: auto; border: 1px solid #282e38; background: #14171d; }
        
        /* Convertimos los enlaces en botones HTML puros */
        .btn-personaje { width: 100%; text-align: left; padding: 10px; background: none; color: #cbd5e1; border: none; border-bottom: 1px solid #1f242d; cursor: pointer; font-size: 1rem; }
        .btn-personaje:hover { background-color: #242a35; }
        .btn-personaje.activo { background-color: #2563eb; color: white; font-weight: bold; }

        .panel-derecho { flex: 1; display: flex; gap: 20px; background-color: #1a1e24; border: 1px solid #2d333b; padding: 20px; border-radius: 8px; min-height: 400px;}
        .foto img { width: 200px; border-radius: 8px; border: 2px solid #374151; background: #0f1216; }
        .info { flex: 1; }
        .info h2 { margin-top: 0; border-bottom: 1px solid #2d333b; padding-bottom: 10px; }
        
        .dato { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #21262d; font-size: 0.9rem; }
        .etiqueta { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; }
        .valor { text-align: right; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; }
        .badge.Alive { background: #166534; color: #86efac; }
        .badge.Dead { background: #991b1b; color: #fca5a5; }
        .badge.unknown { background: #374151; color: #d1d5db; }
    </style>
</head>
<body>

    

    <div class="contenedor">
        
        <!-- Panel Izquierdo -->
        <div class="panel-izquierdo">
            <label class="etiqueta" style="margin-bottom: 5px;">Seleccionar Temporada:</label>
            <form method="GET" action="index.php" class="form-temporada">
                <!-- JAVASCRIPT: onchange="this.form.submit()" envía el formulario automáticamente al elegir otra opción -->
                <select name="season" onchange="this.form.submit()">
                    <?php foreach ($temporadas as$codigo): ?>
                        <?php $num = (int)str_replace('S', '',$codigo); ?>
                        <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo ===$temporadaActual ? 'selected' : '' ?>>
                            Temporada <?= $num ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
    <label class="etiqueta" style="margin-bottom: 5px;">Personaje:</label>
            <ul class="lista-personajes">
              
                <?php foreach ($personajes as$p): ?>
                    <li>
                        <!-- JAVASCRIPT: onclick llama a la función para pedir los datos sin recargar la página -->
                        <button type="button" class="btn-personaje" data-id="<?= $p['id_character'] ?>" onclick="verDetallePersonaje(this)">
                            <?= htmlspecialchars($p['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($personajes)): ?>
                    <li style="padding:10px; color:gray;">Sin resultados</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Panel Derecho: Creado vacío, se llena con JS -->
        <div class="panel-derecho" id="panelDetalles" style="display: none;">
            <div class="foto">
                <img id="charImg" src="" alt="Foto">
            </div>
            <div class="info">
                <h2 id="charName">-</h2>
                <div class="dato"><span class="etiqueta">Status</span><span id="charStatus" class="badge"></span></div>
                <div class="dato"><span class="etiqueta">Species</span><span id="charSpecies" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Gender</span><span id="charGender" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Origin</span><span id="charOrigin" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Type</span><span id="charType" class="valor"></span></div>
               <!-- <div class="dato"><span class="etiqueta">First Episode</span><span id="charFirstEp" class="valor"></span></div> -->
               <!-- <div class="dato"><span class="etiqueta">Last Seen</span><span id="charLastEp" class="valor"></span></div>
            </div>
        </div>

    </div>

    <-- EL ÚNICO JAVASCRIPT (Solo para los botones) -->
    <script>
        async function verDetallePersonaje(boton) {
            // 1. Remarcamos visualmente el botón seleccionado
            document.querySelectorAll('.btn-personaje').forEach(b => b.classList.remove('activo'));
            boton.classList.add('activo');

            // 2. Extraemos el ID guardado en el botón (data-id)
            const id = boton.getAttribute('data-id');

            // 3. Vamos al backend de PHP a pedir solo los datos de ese personaje
            const respuesta = await fetch(`index.php?ajax_id=${id}`);
            const datos = await respuesta.json();

            if (datos) {
                // 4. Mostramos el panel y reemplazamos los textos e imagen
                document.getElementById('panelDetalles').style.display = 'flex';
                document.getElementById('charImg').src = datos.image;
                document.getElementById('charName').textContent = datos.name;
                
                const statusSpan = document.getElementById('charStatus');
                statusSpan.textContent = datos.status;
                statusSpan.className = `badge ${datos.status}`; // Le da el color Verde/Rojo/Gris
                
                document.getElementById('charSpecies').textContent = datos.species || '-';
                document.getElementById('charGender').textContent = datos.gender || '-';
                document.getElementById('charOrigin').textContent = datos.origin_name || 'Desconocido';
                document.getElementById('charType').textContent = datos.type || 'Ninguno';
                document.getElementById('charFirstEp').textContent = datos.first_episode;
                document.getElementById('charLastEp').textContent = datos.last_episode;
            }
        }

        // Hacemos que se haga clic automáticamente en el primer personaje al entrar
        window.onload = () => {
            const primerBoton = document.querySelector('.btn-personaje');
            if (primerBoton) primerBoton.click();
        };
    </script>

</body>
</html>