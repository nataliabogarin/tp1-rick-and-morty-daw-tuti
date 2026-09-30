<?php
require_once 'config.php';

$mensajeSincronizacion = '';
$stmtCantidad = $conexion->query("SELECT COUNT(*) FROM CHARACTERS");
$cantidadPersonajes = $stmtCantidad->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sincronizar_personajes'])) {
    require_once 'main.php';
    $porcentajePersonajes = $cantidadPersonajes == 0 ? 70 : 100;

    if (sincronizarPersonajes($conexion, $porcentajePersonajes)) {
        $mensajeSincronizacion = 'Personajes sincronizados correctamente.';
        $stmtCantidad = $conexion->query("SELECT COUNT(*) FROM CHARACTERS");
        $cantidadPersonajes = $stmtCantidad->fetchColumn();
    } else {
        $mensajeSincronizacion = 'No se pudo completar la sincronización.';
    }
}

// ====================================================================
// 1. RESPUESTA PARA EL JAVASCRIPT (Solo se ejecuta al tocar un botón)
// ====================================================================
if (isset($_GET['ajax_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)$_GET['ajax_id'];
    $season = isset($_GET['season']) ? $_GET['season'] : 'S01';

    $sqlDetalle = "
        SELECT c.id_character, c.name, c.status, c.species, c.type, c.gender, c.image, o.name AS origin_name, l.name AS location_name
        FROM CHARACTERS c
        LEFT JOIN LOCATION o ON c.id_origin_location = o.id_location
        LEFT JOIN LOCATION l ON c.id_current_location = l.id_location
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

        $episodiosTemporada = array_filter($episodios, function($episodio) use ($season) {
        return str_starts_with($episodio['episode'], $season);
}        );

        $personaje['first_episode'] = !empty($episodios) ? $episodios[0]['name'] . " (" . $episodios[0]['episode'] . ")" : "Desconocido";
        $personaje['last_episode']  = !empty($episodiosTemporada) ? end($episodiosTemporada)['name'] . " (" . end($episodiosTemporada)['episode'] . ")" : "Desconocido";
    }

    echo json_encode($personaje);
    exit; // Terminamos aquí para que solo devuelva los datos puros al JS
}

// ====================================================================
// 2. CÓDIGO NORMAL DE PHP (Carga inicial de la página)
// ====================================================================
$temporadas = [];
$personajes = [];
$temporadaActual = 'S01';

if ($cantidadPersonajes > 0) {
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
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rick and Morty - Híbrido</title>
    <link rel="stylesheet" href="assets/css/rick-morty-animations.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= $cantidadPersonajes == 0 ? 'body-inicio' : 'body-personajes' ?>">

    <?php if ($cantidadPersonajes == 0): ?>
        <div class="intro">
            <h1>Rick and Morty</h1>
            <p>Todavía no hay personajes cargados.</p>
            <p>Para empezar, importá los datos desde la API.</p>
            <form method="POST" action="index.php" id="formImportar">
                <input type="hidden" name="sincronizar_personajes" value="1">
                <button type="submit" class="btn-sincronizar btn-importar" id="btnImportar">
                    Importar personajes
                </button>
            </form>
            <div class="carga-inicial oculto" id="cargaInicial">
                <p>Importando personajes...</p>
                <p id="mensajeCarga">Viajando por universos desconocidos...</p>
                <div class="portal-carga"></div>
            </div>
            <?php if ($mensajeSincronizacion !== ''): ?>
                <p class="mensaje-sincronizacion" id="mensajeSincronizacion"><?= htmlspecialchars($mensajeSincronizacion) ?></p>
            <?php endif; ?>
        </div>
    <?php else: ?>

    <header class="encabezado-app">
        <img src="assets/img/logo_Rick_and_Morty.svg.webp" alt="Rick and Morty" class="logo-app">
    </header>

    <div class="animacion-info">
        <strong>Explorador interdimensional activo</strong>
        <span>Seleccioná un personaje para ver su información.</span>
    </div>

    <div class="acciones-app">
        <form method="POST" action="index.php?season=<?= htmlspecialchars($temporadaActual) ?>" class="form-sincronizar" id="formSincronizar">
            <input type="hidden" name="sincronizar_personajes" value="1">
            <button type="submit" class="btn-sincronizar">
                Sincronizar personajes
            </button>
        </form>
        <?php if ($mensajeSincronizacion !== ''): ?>
            <p class="mensaje-sincronizacion" id="mensajeSincronizacion"><?= htmlspecialchars($mensajeSincronizacion) ?></p>
        <?php endif; ?>
    </div>

    <div class="contenedor">

        <!-- Panel Izquierdo -->
        <div class="panel-izquierdo">
            <h2 class="titulo-panel">Filtros</h2>
            <label class="etiqueta label-formulario">Seleccionar Temporada:</label>
            <form method="GET" action="index.php" class="form-temporada">
                <!-- JAVASCRIPT: onchange="this.form.submit()" envía el formulario automáticamente al elegir otra opción -->
                <select id="seasonSelect" name="season" onchange="this.form.submit()">
                    <?php foreach ($temporadas as$codigo): ?>
                        <?php $num = (int)str_replace('S', '',$codigo); ?>
                        <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo ===$temporadaActual ? 'selected' : '' ?>>
                            Temporada <?= $num ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
    <label class="etiqueta label-formulario">Personaje:</label>
            <input
            type="text"
            id="buscadorPersonaje"
            placeholder="Buscar personaje..."
>
            <div class="lista-personajes-contenedor">
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
                    <li class="sin-resultados-item">Sin resultados</li>
                <?php endif; ?>
            </ul>
            </div>
             <p id="sinResultados" class="oculto">
                  Sin resultados
             </p>
        </div>

        <!-- Panel Derecho: Creado vacío, se llena con JS -->
        <div class="panel-derecho oculto" id="panelDetalles">
            <div class="foto">
                <img id="charImg" src="" alt="Foto">
            </div>
            <div class="info">
                <h2 id="charName">-</h2>
                <div class="dato"><span class="etiqueta">Status</span><span id="charStatus" class="badge"></span></div>
                <div class="dato"><span class="etiqueta">Species</span><span id="charSpecies" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Gender</span><span id="charGender" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Origin</span><span id="charOrigin" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Last Known Location</span><span id="charLocation" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Type</span><span id="charType" class="valor"></span></div>
               <div class="dato"><span class="etiqueta">First Episode</span><span id="charFirstEp" class="valor"></span></div>
                <div class="dato"><span class="etiqueta">Last Episode In Season</span><span id="charLastEp" class="valor"></span></div>
            </div>
        </div>

    </div>

    <div class="personajes-animados">
        <div class="rick-container">
            <div class="head-container">
                <div class="head">
                    <div class="brow-container">
                        <div class="brow"></div>
                    </div>
                    <div class="eyes-container">
                        <div class="left eye">
                            <div class="pupil"></div>
                        </div>
                        <div class="right eye">
                            <div class="pupil"></div>
                        </div>
                    </div>
                    <div class="eyebags-container">
                        <div class="left eyebag"></div>
                        <div class="right eyebag"></div>
                    </div>
                    <div class="nose"></div>
                    <div class="mouth-container">
                        <div class="mouth"></div>
                        <div class="spittle"></div>
                        <div class="spittle-arcs"></div>
                    </div>
                </div>
                <div class="ear-container">
                    <div class="left ear"></div>
                    <div class="right ear"></div>
                </div>
                <div class="hair-container">
                    <div class="hair"></div>
                </div>
                <div class="neck"></div>
            </div>
            <div class="body-container">
                <div class="body">
                    <div class="shirt">
                        <div class="flaps-container">
                            <div class="left flap"></div>
                            <div class="right flap"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="morty-container">
            <div class="head-container">
                <div class="head">
                    <div class="brows-container">
                        <div class="left brow"></div>
                        <div class="right brow"></div>
                    </div>
                    <div class="eyes-container">
                        <div class="left eye">
                            <div class="pupil"></div>
                        </div>
                        <div class="right eye">
                            <div class="pupil"></div>
                        </div>
                    </div>
                    <div class="nose"></div>
                    <div class="mouth-container">
                        <div class="mouth"></div>
                    </div>
                </div>
                <div class="ear-container">
                    <div class="left ear"></div>
                    <div class="right ear"></div>
                </div>
                <div class="hair-container">
                    <div class="hair"></div>
                </div>
            </div>
            <div class="body-container">
                <div class="body"></div>
            </div>
        </div>
    </div>

    <div class="overlay-sincronizacion oculto" id="overlaySincronizacion">
        <div class="overlay-contenido">
            <p>Sincronizando personajes...</p>
            <p id="mensajeSincronizacionCarga">Actualizando dimensiones conocidas...</p>
            <div class="portal-carga portal-sincronizacion"></div>
        </div>
    </div>

    <!-- EL ÚNICO JAVASCRIPT (Solo para los botones) -->
    <script>
        const buscador = document.getElementById('buscadorPersonaje');
    const sinResultados = document.getElementById('sinResultados');
    const listaPersonajes = document.querySelector('.lista-personajes');
    const formSincronizar = document.getElementById('formSincronizar');
    const overlaySincronizacion = document.getElementById('overlaySincronizacion');
    const mensajeSincronizacionCarga = document.getElementById('mensajeSincronizacionCarga');
    const frasesSincronizacion = [
        'Actualizando dimensiones conocidas...',
        'Revisando cambios en la Ciudadela...',
        'Buscando nuevos registros interdimensionales...',
        'Ordenando episodios y locaciones...',
        'Conectando con la API del multiverso...'
    ];
    let scrollPersonajes;

    formSincronizar.addEventListener('submit', function () {
        let numeroFrase = 0;

        overlaySincronizacion.classList.remove('oculto');

        setInterval(function () {
            numeroFrase++;
            mensajeSincronizacionCarga.textContent = frasesSincronizacion[numeroFrase % frasesSincronizacion.length];
        }, 1800);
    });

    listaPersonajes.addEventListener('mouseenter', function () {
        scrollPersonajes = setInterval(function () {
            listaPersonajes.scrollTop += 1;
        }, 35);
    });

    listaPersonajes.addEventListener('mouseleave', function () {
        clearInterval(scrollPersonajes);
    });

    buscador.addEventListener('input', function () {

        // Normalizamos lo escrito en el buscador
        const textoBuscado = buscador.value
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim();

        const personajes = document.querySelectorAll('.lista-personajes li');
        let coincidencias = 0;

        personajes.forEach(personaje => {

            // Normalizamos también el nombre del personaje
            const nombre = personaje.textContent
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");

            if (nombre.includes(textoBuscado)) {
                personaje.style.display = '';
                coincidencias++;
            } else {
                personaje.style.display = 'none';
            }
        });

        // Si no encontramos ningún personaje, mostramos el mensaje
        if (coincidencias === 0) {
            sinResultados.style.display = 'block';
        } else {
            sinResultados.style.display = 'none';
        }
    });
        async function verDetallePersonaje(boton) {
            // 1. Remarcamos visualmente el botón seleccionado
            document.querySelectorAll('.btn-personaje').forEach(b => b.classList.remove('activo'));
            boton.classList.add('activo');

            // 2. Extraemos el ID guardado en el botón (data-id)
            const id = boton.getAttribute('data-id');

            // 3. Obtenemos la temporada seleccionada
            const season = document.getElementById('seasonSelect').value;

            // 4. Vamos al backend de PHP a pedir los datos del personaje y la temporada
            const respuesta = await fetch(`index.php?ajax_id=${id}&season=${season}`);
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
                document.getElementById('charLocation').textContent = datos.location_name || 'Desconocido';
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
    <?php endif; ?>

<?php if ($cantidadPersonajes == 0): ?>
    <script>
        const formImportar = document.getElementById('formImportar');
        const btnImportar = document.getElementById('btnImportar');
        const cargaInicial = document.getElementById('cargaInicial');
        const mensajeCarga = document.getElementById('mensajeCarga');
        const frasesCarga = [
            'Viajando por universos desconocidos...',
            'Abriendo portal interdimensional...',
            'Buscando personajes en la Ciudadela...',
            'Cargando episodios desde otra dimensión...',
            'Sincronizando realidades alternativas...',
            'Consultando al Consejo de Ricks...',
            'Atravesando la curva finita central...',
            'Revisando archivos de Mortys perdidos...'
        ];

        formImportar.addEventListener('submit', function () {
            let numeroFrase = 0;

            btnImportar.disabled = true;
            btnImportar.textContent = 'Importando...';
            cargaInicial.classList.remove('oculto');

            setInterval(function () {
                numeroFrase++;
                mensajeCarga.textContent = frasesCarga[numeroFrase % frasesCarga.length];
            }, 1800);
        });
    </script>
<?php endif; ?>
<?php if ($mensajeSincronizacion !== ''): ?>
    <script>
        setTimeout(function () {
            const mensaje = document.getElementById('mensajeSincronizacion');

            if (mensaje) {
                mensaje.classList.add('oculto');
            }
        }, 10000);
    </script>
<?php endif; ?>
</body>
</html>
