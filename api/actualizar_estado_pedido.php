
<?php
header('Content-Type: application/json; charset=utf-8');

require_once "../auth.php";
verificarRol("admin");

require_once "../conexion.php";

try {
    // Solo se permiten solicitudes POST.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "exito" => false,
            "mensaje" => "Método no permitido."
        ]);
        exit;
    }

    // Recibir los datos enviados desde JavaScript.
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        http_response_code(400);
        echo json_encode([
            "exito" => false,
            "mensaje" => "Los datos enviados no son válidos."
        ]);
        exit;
    }

    $orderId = isset($datos["order_id"]) ? (int) $datos["order_id"] : 0;
    $nuevoEstado = trim($datos["status"] ?? "");

    // Estados permitidos en la base de datos.
    $estadosPermitidos = [
        "pending",
        "confirmed",
        "preparing",
        "ready",
        "delivered",
        "cancelled",
        "completed"
    ];

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode([
            "exito" => false,
            "mensaje" => "El identificador del pedido no es válido."
        ]);
        exit;
    }

    if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
        http_response_code(400);
        echo json_encode([
            "exito" => false,
            "mensaje" => "El estado seleccionado no es válido."
        ]);
        exit;
    }

    // Comprobar que el pedido existe.
    $consulta = $conexion->prepare(
        "SELECT id, status FROM orders WHERE id = :id LIMIT 1"
    );

    $consulta->execute([
        ":id" => $orderId
    ]);

    $pedido = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode([
            "exito" => false,
            "mensaje" => "No se encontró el pedido."
        ]);
        exit;
    }

    // Actualizar únicamente el estado del pedido.
    $actualizar = $conexion->prepare(
        "UPDATE orders SET status = :status WHERE id = :id"
    );

    $actualizar->execute([
        ":status" => $nuevoEstado,
        ":id" => $orderId
    ]);

    echo json_encode([
        "exito" => true,
        "mensaje" => "El estado del pedido se actualizó correctamente.",
        "order_id" => $orderId,
        "estado_anterior" => $pedido["status"],
        "nuevo_estado" => $nuevoEstado
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Ocurrió un error al actualizar el estado del pedido."
    ]);
}
