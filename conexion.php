
<?php

$host = getenv("DB_HOST") ?: "localhost";
$dbname = getenv("DB_NAME") ?: "footly";
$username = getenv("DB_USER") ?: "root";

$password = getenv("DB_PASSWORD");

if ($password === false) {
    $password = "";
}

$port = getenv("DB_PORT") ?: "3306";

try {

    $conexion = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Configuración para manejar errores mediante excepciones.
    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Obtener resultados como arreglos asociativos.
    $conexion->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Desactivar emulación de consultas preparadas.
    $conexion->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {

    // Registrar el error sin mostrar credenciales al usuario.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());

    die("Error: No se pudo conectar con la base de datos.");

}

?>