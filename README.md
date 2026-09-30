# Rick and Morty App

Aplicación web desarrollada en PHP para consumir datos de The Rick and Morty API, almacenarlos en una base de datos MySQL/MariaDB y consultarlos desde una interfaz web.

El proyecto permite importar personajes, episodios y locaciones desde la API, guardar las relaciones entre personajes y episodios, y volver a ejecutar la sincronización sin duplicar registros.

## Requisitos

Para ejecutar el proyecto se necesita:

- PHP 8 o superior
- MySQL o MariaDB
- Servidor web local, por ejemplo Apache, XAMPP, Laragon o similar
- Extensión PDO de PHP habilitada
- Acceso a internet para consumir la API

La API utilizada es:

```text
https://rickandmortyapi.com/
```

## Importar la base de datos

El script de creación de tablas se encuentra en:

```text
database/rick_and_morty.sql
```

Para importarlo:

1. Crear una base de datos en MySQL o MariaDB.
2. Seleccionar esa base desde phpMyAdmin o desde la terminal.
3. Importar el archivo `database/rick_and_morty.sql`.

Desde phpMyAdmin:

1. Entrar a phpMyAdmin.
2. Crear o seleccionar la base de datos.
3. Ir a la pestaña **Importar**.
4. Elegir el archivo `rick_and_morty.sql`.
5. Ejecutar la importación.

El archivo SQL crea las tablas necesarias para guardar:

- Locaciones
- Personajes
- Episodios
- Relación entre personajes y episodios

## Configurar config.php

La conexión a la base de datos se configura en el archivo:

```text
config.php
```

Por defecto, el archivo toma valores desde variables de entorno y usa valores locales si no existen:

```php
$ipBD = getenv('DB_HOST') ?: '127.0.0.1';
$usuarioBD = getenv('DB_USER') ?: 'root';
$claveBD = getenv('DB_PASS');
$claveBD = $claveBD === false ? '' : $claveBD;
$nombreBD = getenv('DB_NAME') ?: 'rick_and_morty';
```

Para un entorno local simple, se puede ajustar directamente con los datos de la base:

```php
$ipBD = "127.0.0.1";
$usuarioBD = "root";
$claveBD = "";
$nombreBD = "rick_and_morty";
```

En hosting, hay que reemplazar esos valores por los datos reales de la base de datos creada en el panel:

```php
$ipBD = "host_de_mysql";
$usuarioBD = "usuario_mysql";
$claveBD = "clave_mysql";
$nombreBD = "nombre_base_de_datos";
```

## Ejecutar el proyecto localmente

Una forma simple de ejecutarlo es copiar el proyecto dentro de la carpeta pública del servidor local.

Por ejemplo, en XAMPP:

```text
htdocs/tp1-rick-and-morty-daw-tuti
```

Luego abrir en el navegador:

```text
http://localhost/tp1-rick-and-morty-daw-tuti/
```

También se puede ejecutar con Docker si se usa el `docker-compose.yml` del proyecto:

```bash
docker compose up -d
```

Y abrir:

```text
http://localhost:8080
```

## Importar y sincronizar personajes

La aplicación tiene dos momentos principales de carga de datos.

### Importar personajes

Cuando la base de datos está vacía, se muestra una pantalla inicial con el botón:

```text
Importar personajes
```

Ese botón ejecuta el importador PHP y carga datos desde la API.

En la implementación actual, la primera importación carga una parte de los personajes para poder demostrar luego el funcionamiento de la sincronización.

### Sincronizar personajes

Cuando ya existen personajes cargados, aparece la interfaz principal y se muestra el botón:

```text
Sincronizar personajes
```

Ese botón vuelve a consultar la API y actualiza la base de datos.

La sincronización está preparada para:

- Agregar personajes nuevos
- Actualizar información existente
- Evitar duplicados
- Mantener las relaciones entre personajes y episodios

Si no hay personajes nuevos, la aplicación muestra un mensaje indicando que no hay nuevos registros para sincronizar.

## Archivos principales

```text
index.php
```

Contiene la interfaz web principal, el listado de personajes, el filtro por temporada, el buscador y la visualización del detalle.

```text
main.php
```

Contiene el importador/sincronizador que consume la API y guarda los datos en la base.

```text
config.php
```

Contiene la configuración de conexión a MySQL/MariaDB.

```text
database/rick_and_morty.sql
```

Contiene el script SQL para crear la estructura de la base de datos.

```text
assets/
```

Contiene imágenes y estilos CSS utilizados por la interfaz.

## Notas

Para que la importación funcione, el servidor debe permitir conexiones externas hacia:

```text
https://rickandmortyapi.com/
```

Si el servidor tiene restricciones con `file_get_contents()` para URLs externas, puede ser necesario habilitar `allow_url_fopen` o adaptar el importador para usar cURL.
