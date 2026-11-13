<?php
require_once "../auth.php";
verificarRol("admin");
require_once "../conexion.php";

header("Content-Type: application/json; charset=utf-8");

function responder($exito, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode([
        "exito" => $exito,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

$accion = $_POST["accion"] ?? "";
$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);

try {
    // AGREGAR CLIENTE
    if ($accion === "agregar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $empresa = trim($_POST["empresa"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($nombre === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            responder(false, "Ingresa un nombre y un correo electrónico válido.", 422);
        }

        if (strlen($password) < 8) {
            responder(false, "La contraseña debe tener al menos 8 caracteres.", 422);
        }

        $conexion->beginTransaction();

        $sqlUsuario = "INSERT INTO users
            (full_name, email, password_hash, role, is_active)
            VALUES (?, ?, ?, 'customer', 1)";

        $stmt = $conexion->prepare($sqlUsuario);
        $stmt->execute([
            $nombre,
            $correo,
            password_hash($password, PASSWORD_DEFAULT)
        ]);

        $userId = $conexion->lastInsertId();

        $sqlCliente = "INSERT INTO customers
            (user_id, phone, company_name, address)
            VALUES (?, ?, ?, ?)";

        $stmt = $conexion->prepare($sqlCliente);
        $stmt->execute([
            $userId,
            $telefono ?: null,
            $empresa ?: null,
            $direccion ?: null
        ]);

        $conexion->commit();
        responder(true, "Cliente registrado correctamente.");
    }

    // MODIFICAR CLIENTE
    if ($accion === "editar") {
        if (!$id) {
            responder(false, "Identificador de cliente no válido.", 422);
        }

        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $empresa = trim($_POST["empresa"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        if ($nombre === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            responder(false, "Ingresa un nombre y un correo electrónico válido.", 422);
        }

        $conexion->beginTransaction();

        $stmt = $conexion->prepare(
            "UPDATE users
             SET full_name = ?, email = ?
             WHERE id = ? AND role = 'customer'"
        );
        $stmt->execute([$nombre, $correo, $id]);

        if ($stmt->rowCount() === 0) {
            // Comprobar que el usuario exista aunque los datos no hayan cambiado.
            $check = $conexion->prepare(
                "SELECT id FROM users WHERE id = ? AND role = 'customer'"
            );
            $check->execute([$id]);

            if (!$check->fetch()) {
                $conexion->rollBack();
                responder(false, "No se encontró el cliente.", 404);
            }
        }

        $stmt = $conexion->prepare(
            "UPDATE customers
             SET phone = ?, company_name = ?, address = ?
             WHERE user_id = ?"
        );
        $stmt->execute([
            $telefono ?: null,
            $empresa ?: null,
            $direccion ?: null,
            $id
        ]);

        $conexion->commit();
        responder(true, "Información del cliente actualizada.");
    }

    // DAR DE BAJA (SIN BORRAR SUS REGISTROS)
    if ($accion === "eliminar") {
        if (!$id) {
            responder(false, "Identificador de cliente no válido.", 422);
        }

        $stmt = $conexion->prepare(
            "UPDATE users
             SET is_active = 0
             WHERE id = ? AND role = 'customer'"
        );
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            responder(false, "No se encontró el cliente o ya está inactivo.", 404);
        }

        responder(true, "Cliente dado de baja correctamente.");
    }

    responder(false, "Acción no reconocida.", 400);

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($e->getCode() === "23000") {
        responder(false, "Ese correo electrónico ya está registrado.", 409);
    }

    responder(false, "Ocurrió un error al procesar la solicitud.", 500);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    responder(false, "No fue posible completar la operación.", 500);
}