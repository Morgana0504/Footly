<?php
require_once "conexion.php";

$mensaje = "";
$adminExiste = (int)$conexion->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn() > 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$adminExiste) {

    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nombre === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        $mensaje = "Verifica los datos. La contraseña debe tener al menos 10 caracteres.";
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password_hash, role)
                VALUES (?, ?, ?, 'admin')";

        $stmt = $conexion->prepare($sql);

        try {
            $stmt->execute([$nombre, $correo, $hash]);
            $mensaje = "Administrador creado correctamente.";
            $adminExiste = true;
        } catch (PDOException $e) {
            $mensaje = "No se pudo crear la cuenta. Comprueba que el correo no esté registrado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear administrador | Footly</title>
</head>
<body>

<h2>Crear administrador de Footly</h2>

<p><?= htmlspecialchars($mensaje) ?></p>

<?php if (!$adminExiste): ?>

<form method="POST">
    <label>Nombre completo</label>
    <input type="text" name="nombre" required>

    <br><br>

    <label>Correo electrónico</label>
    <input type="email" name="correo" required>

    <br><br>

    <label>Contraseña</label>
    <input type="password" name="password" minlength="10" required>

    <br><br>

    <button type="submit">Crear administrador</button>
</form>

<?php else: ?>

<p>Ya existe una cuenta administrativa. No es posible crear otra desde este archivo.</p>

<?php endif; ?>

</body>
</html>
