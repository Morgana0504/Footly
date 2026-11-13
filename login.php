<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {

    if (headers_sent($archivo, $linea)) {
        die("Los encabezados ya fueron enviados desde: " . $archivo . " en la línea " . $linea);
    }

    session_start();
}

require_once "conexion.php";

$vista = $_GET["vista"] ?? "login";
$mensaje = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    // INICIAR SESIÓN
    if ($accion === "login") {

        $correo = trim($_POST["usuario"] ?? "");
        $password = $_POST["password"] ?? "";

        $sql = "SELECT id, full_name, email, password_hash, role, is_active
                FROM users
                WHERE email = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if (
            !$usuario ||
            !password_verify($password, $usuario["password_hash"]) ||
            !$usuario["is_active"]
        ) {
            $mensaje = "Correo o contraseña incorrectos.";
            $vista = "login";
        } else {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["full_name"];
            $_SESSION["correo"] = $usuario["email"];
            $_SESSION["rol"] = $usuario["role"];

            if ($usuario["role"] === "admin") {
                header("Location: admin_pedidos_conectados.php");
            } else {
                header("Location: catalogo.php");
            }

            exit;
        }
    }

    // REGISTRO DE CLIENTES
    elseif ($accion === "registro") {

        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirmar = $_POST["confirmar_password"] ?? "";

        if (
            $nombre === "" ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 10 ||
            $password !== $confirmar
        ) {
            $mensaje = "Verifica tus datos y asegúrate de que las contraseñas coincidan.";
            $vista = "registro";

        } else {

            try {
                $conexion->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users
                        (full_name, email, password_hash, role)
                        VALUES (?, ?, ?, 'customer')";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([$nombre, $correo, $hash]);

                $userId = $conexion->lastInsertId();

                $sql = "INSERT INTO customers (user_id, phone)
                        VALUES (?, ?)";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([$userId, $telefono]);

                $conexion->commit();

                header("Location: login.php?registro=exitoso");
                exit;

            } catch (PDOException $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                error_log($e->getMessage());

                $mensaje = "No se pudo completar el registro. Comprueba si el correo ya está registrado.";
                $vista = "registro";
            }
        }
    }
}

if (isset($_GET["registro"]) && $_GET["registro"] === "exitoso") {
    $mensaje = "¡Tu cuenta fue creada! Ya puedes iniciar sesión.";
    $vista = "login";
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Footly</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/general.css">
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" href="CSS/responsive.css">
</head>
<body class="login-page">

<header class="navbar">
    <a href="index.php" class="logo">Footly<span>.</span></a>
    <a href="index.php" class="text-link">← Volver al inicio</a>
</header>

<main class="login-layout">
    <section class="login-visual">
        <img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=1200&q=85" alt="Calzado deportivo">
        <div class="login-visual-text">
            <span class="eyebrow">TU CATÁLOGO, MÁS CERCA</span>
            <h1>El siguiente<br>paso empieza<br><span>aquí.</span></h1>
            <p>Consulta productos y organiza tus pedidos.</p>
        </div>
    </section>

    <section class="login-form-area">
        <div class="login-form-box">

<span class="eyebrow">
    <?= $vista === "registro" ? "ÚNETE A FOOTLY" : "BIENVENIDO DE NUEVO" ?>
</span>

<h2>
    <?= $vista === "registro" ? "Crear cuenta" : "Iniciar sesión" ?><span>.</span>
</h2>

<p class="muted">
    <?= $vista === "registro"
        ? "Registra tus datos para comenzar a realizar pedidos."
        : "Ingresa tus datos para continuar." ?>
</p>

<?php if ($mensaje !== ""): ?>
    <p class="form-message">
        <?= htmlspecialchars($mensaje) ?>
    </p>
<?php endif; ?>

<?php if ($vista === "registro"): ?>

    <form action="login.php?vista=registro" method="POST">

        <input type="hidden" name="accion" value="registro">

        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre"
               placeholder="Ingresa tu nombre" required maxlength="120">

        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo"
               placeholder="ejemplo@correo.com" required maxlength="150">

        <label for="telefono">Teléfono</label>
        <input type="tel" id="telefono" name="telefono"
               placeholder="Ingresa tu teléfono" maxlength="25">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               placeholder="Mínimo 10 caracteres" minlength="10" required>

        <label for="confirmar_password">Confirmar contraseña</label>
        <input type="password" id="confirmar_password"
               name="confirmar_password"
               placeholder="Repite tu contraseña" minlength="10" required>

        <button type="submit" class="btn btn-dark btn-full">
            Crear mi cuenta ↗
        </button>

    </form>

    <div class="login-divider"><span>¿YA TIENES CUENTA?</span></div>

    <p class="muted small-text">
        <a href="login.php?vista=login" class="small-link">
            Iniciar sesión
        </a>
    </p>

<?php else: ?>

    <form action="login.php" method="POST">

        <input type="hidden" name="accion" value="login">

        <label for="usuario">Correo electrónico</label>
        <input type="email" id="usuario" name="usuario"
               placeholder="Ingresa tu correo" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               placeholder="Ingresa tu contraseña" required>

        <div class="form-options">
            <span></span>
            <a href="#" class="small-link">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn btn-dark btn-full">
            Entrar a mi cuenta ↗
        </button>

    </form>

    <div class="login-divider"><span>¿ERES NUEVO EN FOOTLY?</span></div>

    <p class="muted small-text">
        ¿Aún no tienes cuenta?
        <a href="login.php?vista=registro" class="small-link">
            Crear cuenta
        </a>
    </p>

<?php endif; ?>

<p class="security-note">
    🔒 Acceso reservado para usuarios autorizados.
</p>

        </div>
    </section>
</main>

<footer class="footer">
    <a href="index.php" class="logo">Footly<span>.</span></a>
    <p>Catálogo digital de calzado</p>
    <span>© 2026</span>
</footer>

</body>
</html>