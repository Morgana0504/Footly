<?php
require_once "../auth.php";
verificarRol("customer");

require_once "../conexion.php";

header("Content-Type: application/json; charset=utf-8");

function responder($exito, $mensaje, $codigo = 200, $datos = []) {
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

$entrada = json_decode(file_get_contents("php://input"), true);

if (!is_array($entrada)) {
    responder(false, "Los datos recibidos no son válidos.", 400);
}

$userId = (int) $_SESSION["user_id"];

$items = $entrada["items"] ?? [];
$entrega = trim($entrada["entrega"] ?? "");
$pago = trim($entrada["pago"] ?? "");
$direccion = trim($entrada["direccion"] ?? "");
$observaciones = trim($entrada["observaciones"] ?? "");

$entregasPermitidas = [
    "Recoger en sucursal",
    "Entrega a domicilio",
    "Entrega a distribuidor"
];

$pagosPermitidos = [
    "Transferencia bancaria",
    "Pago en efectivo",
    "Pago con tarjeta (por confirmar)",
    "Crédito autorizado"
];

if (!is_array($items) || count($items) === 0) {
    responder(false, "El pedido debe contener al menos un producto.", 400);
}

if (count($items) > 100) {
    responder(false, "El pedido contiene demasiados productos.", 400);
}

if (!in_array($entrega, $entregasPermitidas, true)) {
    responder(false, "Selecciona un método de entrega válido.", 400);
}

if (!in_array($pago, $pagosPermitidos, true)) {
    responder(false, "Selecciona un método de pago válido.", 400);
}

if (mb_strlen($direccion) > 250 || mb_strlen($observaciones) > 500) {
    responder(false, "La dirección o las observaciones exceden la longitud permitida.", 400);
}

/*
 * Convertir la opción visible del formulario
 * al valor permitido por el ENUM de payments.method.
 */
$metodosPago = [
    "Transferencia bancaria" => "transfer",
    "Pago en efectivo" => "cash",
    "Pago con tarjeta (por confirmar)" => "card",
    "Crédito autorizado" => "other"
];

$metodoPagoBD = $metodosPago[$pago];

try {

    $conexion->beginTransaction();

    // Obtener el cliente a partir de la sesión.
    $stmtCliente = $conexion->prepare("
        SELECT c.id
        FROM customers c
        INNER JOIN users u ON u.id = c.user_id
        WHERE c.user_id = :user_id
          AND u.role = 'customer'
          AND u.is_active = 1
        LIMIT 1
    ");

    $stmtCliente->execute([
        "user_id" => $userId
    ]);

    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception("No se encontró un perfil de cliente activo.");
    }

    $customerId = (int) $cliente["id"];

    $total = 0;
    $detalles = [];

    // Validar cada variante directamente en MySQL.
    foreach ($items as $item) {

        $variantId = filter_var(
            $item["variant_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $item["quantity"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$variantId || !$quantity || $quantity < 1) {
            throw new Exception("Uno de los productos contiene datos inválidos.");
        }

        $stmtVariante = $conexion->prepare("
            SELECT
                pv.id,
                pv.sku,
                pv.size,
                pv.color,
                pv.price,
                pv.stock,
                p.model,
                p.is_active AS product_active,
                pv.is_active AS variant_active
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            WHERE pv.id = :variant_id
            FOR UPDATE
        ");

        $stmtVariante->execute([
            "variant_id" => $variantId
        ]);

        $variante = $stmtVariante->fetch(PDO::FETCH_ASSOC);

        if (
            !$variante ||
            (int) $variante["product_active"] !== 1 ||
            (int) $variante["variant_active"] !== 1
        ) {
            throw new Exception("Uno de los productos ya no está disponible.");
        }

        $stock = (int) $variante["stock"];

        if ($quantity > $stock) {
            throw new Exception(
                "Existencias insuficientes para " .
                $variante["model"] .
                ", talla " . $variante["size"] .
                ", color " . $variante["color"] .
                ". Disponibles: " . $stock . " pares."
            );
        }

        $precio = (float) $variante["price"];
        $subtotal = $precio * $quantity;

        $total += $subtotal;

        $detalles[] = [
            "variant_id" => $variantId,
            "product_name" => $variante["model"],
            "variant_description" =>
                "SKU: " . $variante["sku"] .
                " | Talla: " . $variante["size"] .
                " | Color: " . $variante["color"],
            "quantity" => $quantity,
            "unit_price" => $precio,
            "subtotal" => $subtotal
        ];
    }

    // Conservar los datos de entrega y pago en las notas.
    $notas = "Método de entrega: " . $entrega .
        "\nMétodo de pago: " . $pago .
        "\nDirección de entrega: " . $direccion .
        "\nObservaciones: " . $observaciones;

    // Generar número de pedido.
    $orderNumber = "PED-" .
        date("Ymd") . "-" .
        strtoupper(bin2hex(random_bytes(3)));

    // Registrar el pedido.
    $stmtPedido = $conexion->prepare("
        INSERT INTO orders (
            order_number,
            customer_id,
            order_date,
            status,
            total,
            notes
        )
        VALUES (
            :order_number,
            :customer_id,
            NOW(),
            'pending',
            :total,
            :notes
        )
    ");

    $stmtPedido->execute([
        "order_number" => $orderNumber,
        "customer_id" => $customerId,
        "total" => $total,
        "notes" => $notas
    ]);

    $orderId = (int) $conexion->lastInsertId();

    // Registrar los productos del pedido.
    $stmtDetalle = $conexion->prepare("
        INSERT INTO order_items (
            order_id,
            variant_id,
            product_name,
            variant_description,
            quantity,
            unit_price,
            subtotal
        )
        VALUES (
            :order_id,
            :variant_id,
            :product_name,
            :variant_description,
            :quantity,
            :unit_price,
            :subtotal
        )
    ");

    foreach ($detalles as $detalle) {

        $stmtDetalle->execute([
            "order_id" => $orderId,
            "variant_id" => $detalle["variant_id"],
            "product_name" => $detalle["product_name"],
            "variant_description" => $detalle["variant_description"],
            "quantity" => $detalle["quantity"],
            "unit_price" => $detalle["unit_price"],
            "subtotal" => $detalle["subtotal"]
        ]);
    }

    /*
     * NUEVO: registrar el pago asociado al pedido.
     *
     * El estado comienza como pending.
     * No se marca como paid solamente por seleccionar
     * un método de pago.
     */
    $stmtPago = $conexion->prepare("
        INSERT INTO payments (
            order_id,
            amount,
            method,
            status,
            notes
        )
        VALUES (
            :order_id,
            :amount,
            :method,
            'pending',
            :notes
        )
    ");

    $stmtPago->execute([
        "order_id" => $orderId,
        "amount" => $total,
        "method" => $metodoPagoBD,
        "notes" => "Método seleccionado por el cliente: " . $pago
    ]);

    $paymentId = (int) $conexion->lastInsertId();

    // Confirmar pedido, detalles y pago en una sola operación.
    $conexion->commit();

    responder(
        true,
        "El pedido se registró correctamente.",
        201,
        [
            "order_id" => $orderId,
            "order_number" => $orderNumber,
            "total" => $total,
            "status" => "pending",
            "payment_id" => $paymentId,
            "payment_method" => $metodoPagoBD,
            "payment_status" => "pending"
        ]
    );

} catch (PDOException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al registrar pedido: " . $e->getMessage());

    responder(
        false,
        "Ocurrió un error en la base de datos al registrar el pedido.",
        500
    );

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al registrar pedido: " . $e->getMessage());

    responder(
        false,
        $e->getMessage(),
        400
    );
}