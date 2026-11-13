
<?php
require_once "../auth.php";
verificarRol("admin");

require_once "../conexion.php";

header("Content-Type: application/json; charset=utf-8");

function responder($exito, $mensaje, $datos = []) {
    echo json_encode(
        array_merge([
            "exito" => $exito,
            "mensaje" => $mensaje
        ], $datos),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

try {

    // ==========================================
    // CONSULTAR PAGOS
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "GET") {

        $sql = "
            SELECT
                p.id AS payment_id,
                p.order_id,
                p.amount,
                p.method,
                p.status,
                p.payment_date,
                p.notes AS payment_notes,

                o.order_number,

                u.full_name AS customer_name,
                u.email AS customer_email,

                c.company_name

            FROM payments p

            INNER JOIN orders o
                ON p.order_id = o.id

            INNER JOIN customers c
                ON o.customer_id = c.id

            INNER JOIN users u
                ON c.user_id = u.id

            ORDER BY p.id DESC
        ";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        responder(true, "Pagos consultados correctamente.", [
            "pagos" => $pagos,
            "total" => count($pagos)
        ]);
    }

    // ==========================================
    // ACTUALIZAR ESTADO DEL PAGO
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $datos = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($datos)) {
            responder(false, "La solicitud no contiene datos válidos.");
        }

        $paymentId = filter_var(
            $datos["payment_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $nuevoEstado = $datos["status"] ?? "";

        if (!$paymentId || $paymentId < 1) {
            responder(false, "El identificador del pago no es válido.");
        }

        $estadosPermitidos = [
            "pending",
            "paid",
            "refunded"
        ];

        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            responder(false, "El estado del pago no es válido.");
        }

        // Comprobar que el pago existe.
        $consulta = $conexion->prepare("
            SELECT id, status
            FROM payments
            WHERE id = :id
            LIMIT 1
        ");

        $consulta->execute([
            ":id" => $paymentId
        ]);

        $pago = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$pago) {
            responder(false, "No se encontró el pago solicitado.");
        }

        if ($pago["status"] === $nuevoEstado) {
            responder(true, "El pago ya tiene ese estado.");
        }

        // Actualizar únicamente el estado del pago.
        $actualizar = $conexion->prepare("
            UPDATE payments
            SET status = :status
            WHERE id = :id
        ");

        $actualizar->execute([
            ":status" => $nuevoEstado,
            ":id" => $paymentId
        ]);

        responder(true, "Estado del pago actualizado correctamente.", [
            "payment_id" => $paymentId,
            "status" => $nuevoEstado
        ]);
    }

    responder(false, "Método de solicitud no permitido.");

} catch (PDOException $e) {

    error_log("Error en admin_pagos.php: " . $e->getMessage());

    http_response_code(500);

    responder(false, "Ocurrió un error al consultar o actualizar los pagos.");

} catch (Throwable $e) {

    error_log("Error en admin_pagos.php: " . $e->getMessage());

    http_response_code(500);

    responder(false, "Ocurrió un error inesperado.");
}
