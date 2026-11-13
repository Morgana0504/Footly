<?php

// Evitar que se envíe contenido antes de iniciar la sesión.
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    } else {
        error_log("No se pudo iniciar la sesión: ya se enviaron encabezados.");
    }
}

function verificarRol($rolRequerido) {

    // Verificar que exista una sesión iniciada.
    if (
        !isset($_SESSION["user_id"]) ||
        !isset($_SESSION["rol"])
    ) {
        header("Location: login.php");
        exit;
    }

    // Verificar que el usuario tenga el rol requerido.
    if ($_SESSION["rol"] !== $rolRequerido) {

        if ($_SESSION["rol"] === "admin") {
            header("Location: admin_pedidos_conectados.php");
        } else {
            header("Location: catalogo.php");
        }

        exit;
    }
}
