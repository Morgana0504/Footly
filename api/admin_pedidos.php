
<?php
require_once "../auth.php";
verificarRol("admin");

require_once "../conexion.php";

header("Content-Type: application/json; charset=utf-8");

function responder($exito, $mensaje, $datos = [], $codigo = 200) {
    http_response_code($codigo);

    echo json_encode(
        array_merge([
            "exito" => $exito,
            "mensaje" => $mensaje
        ], $datos),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Extrae datos que actualmente se almacenan
 * dentro de orders.notes.
 */
function obtenerDatoNota($notas, $etiqueta) {
    if (!$notas) {
        return "";
    }

    $patron = '/^' . preg_quote($etiqueta, '/') . ':\s*(.*)$/mi';

    if (preg_match($patron, $notas, $coincidencia)) {
        return trim($coincidencia[1]);
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    responder(false, "Método no permitido.", [], 405);
}

try {

    // 1. Consultar pedidos, clientes y último pago registrado.
    $sqlPedidos = "
        SELECT
            o.id,
            o.order_number,
            o.customer_id,
            o.order_date,
            o.status,
            o.total,
            o.notes,

            u.full_name AS customer_name,
            u.email AS customer_email,
            c.phone AS customer_phone,
            c.company_name AS customer_company,

            pay.id AS payment_id,
            pay.amount AS payment_amount,
            pay.method AS payment_method,
            pay.status AS payment_status,
            pay.payment_date AS payment_date,
            pay.notes AS payment_notes

        FROM orders o

        INNER JOIN customers c
            ON c.id = o.customer_id

        INNER JOIN users u
            ON u.id = c.user_id

        LEFT JOIN payments pay
            ON pay.id = (
                SELECT p2.id
                FROM payments p2
                WHERE p2.order_id = o.id
                ORDER BY p2.id DESC
                LIMIT 1
            )

        ORDER BY o.order_date DESC, o.id DESC
    ";

    $stmtPedidos = $conexion->prepare($sqlPedidos);
    $stmtPedidos->execute();

    $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

    // 2. Preparar la respuesta.
    $resultado = [];
    $idsPedidos = [];

    foreach ($pedidos as $pedido) {

        $id = (int) $pedido["id"];
        $idsPedidos[] = $id;

        $notas = $pedido["notes"] ?? "";

        $resultado[$id] = [
            "id" => $pedido["order_number"],
            "order_id" => $id,
            "order_number" => $pedido["order_number"],
            "date" => $pedido["order_date"],
            "status" => $pedido["status"],
            "total" => (float) $pedido["total"],

            "customer" => [
                "name" => $pedido["customer_name"],
                "company" => $pedido["customer_company"] ?? "",
                "phone" => $pedido["customer_phone"] ?? "",
                "email" => $pedido["customer_email"]
            ],

            // Se conserva el texto que ya utiliza el frontend.
            "delivery" => obtenerDatoNota(
                $notas,
                "Método de entrega"
            ),

            "payment" => obtenerDatoNota(
                $notas,
                "Método de pago"
            ),

            "address" => obtenerDatoNota(
                $notas,
                "Dirección de entrega"
            ),

            "observations" => obtenerDatoNota(
                $notas,
                "Observaciones"
            ),

            // NUEVOS DATOS DEL PAGO.
            "payment_id" => $pedido["payment_id"] !== null
                ? (int) $pedido["payment_id"]
                : null,

            "payment_method" => $pedido["payment_method"] ?? null,

            "payment_status" => $pedido["payment_status"] ?? null,

            "payment_amount" => $pedido["payment_amount"] !== null
                ? (float) $pedido["payment_amount"]
                : null,

            "payment_date" => $pedido["payment_date"] ?? null,

            "payment_notes" => $pedido["payment_notes"] ?? "",

            "notes" => $notas,
            "items" => []
        ];
    }

    // 3. Consultar productos de todos los pedidos.
    if (count($idsPedidos) > 0) {

        $marcadores = implode(
            ",",
            array_fill(0, count($idsPedidos), "?")
        );

        $sqlDetalles = "
            SELECT
                oi.order_id,
                oi.variant_id,
                oi.product_name,
                oi.variant_description,
                oi.quantity,
                oi.unit_price,
                oi.subtotal,

                pv.sku,
                pv.size,
                pv.color,

                p.code AS product_code,
                p.model AS product_model

            FROM order_items oi

            LEFT JOIN product_variants pv
                ON pv.id = oi.variant_id

            LEFT JOIN products p
                ON p.id = pv.product_id

            WHERE oi.order_id IN ($marcadores)

            ORDER BY oi.id ASC
        ";

        $stmtDetalles = $conexion->prepare($sqlDetalles);
        $stmtDetalles->execute($idsPedidos);

        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles as $detalle) {

            $orderId = (int) $detalle["order_id"];

            if (!isset($resultado[$orderId])) {
                continue;
            }

            $resultado[$orderId]["items"][] = [
                "variant_id" => (int) $detalle["variant_id"],
                "model" => $detalle["product_name"],
                "product_name" => $detalle["product_name"],
                "code" => $detalle["product_code"] ?? "",
                "sku" => $detalle["sku"] ?? "",
                "color" => $detalle["color"] ?? "",
                "size" => $detalle["size"] ?? "",
                "description" => $detalle["variant_description"] ?? "",
                "variant_description" => $detalle["variant_description"] ?? "",
                "quantity" => (int) $detalle["quantity"],
                "unitPrice" => (float) $detalle["unit_price"],
                "unit_price" => (float) $detalle["unit_price"],
                "subtotal" => (float) $detalle["subtotal"]
            ];
        }
    }

    responder(
        true,
        "Pedidos consultados correctamente.",
        [
            "pedidos" => array_values($resultado),
            "total" => count($resultado)
        ]
    );

} catch (PDOException $e) {

    error_log(
        "Error al consultar pedidos administrativos: " .
        $e->getMessage()
    );

    responder(
        false,
        "No fue posible consultar los pedidos.",
        [],
        500
    );
}