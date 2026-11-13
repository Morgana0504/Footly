<?php
require_once "auth.php";
verificarRol("customer");

require_once "conexion.php";

$userId = (int) $_SESSION["user_id"];

$sql = "SELECT
            c.id AS customer_id,
            u.full_name,
            u.email,
            c.phone,
            c.company_name,
            c.address
        FROM users u
        INNER JOIN customers c ON c.user_id = u.id
        WHERE u.id = :user_id
        AND u.role = 'customer'
        AND u.is_active = 1
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->execute(["user_id" => $userId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("No se encontró el perfil del cliente. Contacta al administrador.");
}

/*
 * CONSULTAR PEDIDOS DEL CLIENTE
 */
$sqlPedidos = "SELECT
                    id,
                    order_number,
                    order_date,
                    status,
                    total,
                    notes
                FROM orders
                WHERE customer_id = :customer_id
                ORDER BY order_date DESC, id DESC";

$stmtPedidos = $conexion->prepare($sqlPedidos);
$stmtPedidos->execute([
    "customer_id" => $cliente["customer_id"]
]);

$misPedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

/*
 * CONSULTAR DETALLES Y PAGOS DE LOS PEDIDOS
 */
if (!empty($misPedidos)) {

    $idsPedidos = array_column($misPedidos, "id");

    $marcadores = implode(
        ",",
        array_fill(0, count($idsPedidos), "?")
    );

    /*
     * Obtener productos de cada pedido.
     */
    $sqlDetalles = "
        SELECT
            order_id,
            product_name,
            variant_description,
            quantity,
            unit_price,
            subtotal
        FROM order_items
        WHERE order_id IN ($marcadores)
    ";

    $stmtDetalles = $conexion->prepare($sqlDetalles);
    $stmtDetalles->execute($idsPedidos);

    $todosLosDetalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Obtener información de pagos.
     */
    $sqlPagos = "
        SELECT
            order_id,
            method,
            status
        FROM payments
        WHERE order_id IN ($marcadores)
    ";

    $stmtPagos = $conexion->prepare($sqlPagos);
    $stmtPagos->execute($idsPedidos);

    $todosLosPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Preparar la información de cada pedido.
     */
    $indicePedidos = [];

    foreach ($misPedidos as $indice => $pedido) {

        $misPedidos[$indice]["items"] = [];
        $misPedidos[$indice]["payment_method"] = "";
        $misPedidos[$indice]["payment_status"] = "";

        $indicePedidos[$pedido["id"]] = $indice;
    }

    /*
     * Asociar los productos con su pedido.
     */
    foreach ($todosLosDetalles as $detalle) {

        $orderId = $detalle["order_id"];

        if (isset($indicePedidos[$orderId])) {

            $indice = $indicePedidos[$orderId];

            $misPedidos[$indice]["items"][] = $detalle;
        }
    }

    /*
     * Asociar los pagos con su pedido.
     */
    foreach ($todosLosPagos as $pago) {

        $orderId = $pago["order_id"];

        if (isset($indicePedidos[$orderId])) {

            $indice = $indicePedidos[$orderId];

            $misPedidos[$indice]["payment_method"] = $pago["method"];
            $misPedidos[$indice]["payment_status"] = $pago["status"];
        }
    }

    /*
     * Interpretar los datos guardados en orders.notes.
     */
    foreach ($misPedidos as $indice => $pedido) {

        $informacion = [
            "Método de entrega" => "",
            "Método de pago" => "",
            "Dirección de entrega" => "",
            "Observaciones" => ""
        ];

        $lineas = preg_split(
            '/\r\n|\r|\n/',
            $pedido["notes"] ?? ""
        );

        foreach ($lineas as $linea) {

            $partes = explode(": ", $linea, 2);

            if (count($partes) === 2) {

                $clave = trim($partes[0]);
                $valor = trim($partes[1]);

                if (array_key_exists($clave, $informacion)) {
                    $informacion[$clave] = $valor;
                }
            }
        }

        $misPedidos[$indice]["informacion"] = $informacion;
    }
}

function h($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar pedido | Footly</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="CSS/general.css">
    <link rel="stylesheet" href="CSS/pedido.css">
    <link rel="stylesheet" href="CSS/responsive.css">

    <style>

        :root {
            --order-accent: #d4f36a;
            --order-ink: #171916;
            --order-muted: #777b73;
            --order-line: #e7e9e1;
        }

        .order-layout {
            align-items: start;
        }

        .order-form,
        .order-summary {
            min-width: 0;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: 2px solid rgba(125,153,43,.28);
            border-color: #879d45;
        }

        .field input[readonly] {
            background: #f4f5f1;
            color: #777;
        }

        .summary-product {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--order-line);
            font-size: 12px;
        }

        .summary-product strong {
            display: block;
            margin-bottom: 5px;
        }

        .summary-product span {
            color: var(--order-muted);
            line-height: 1.7;
        }

        .summary-product b {
            white-space: nowrap;
        }

        .cart-item-remove {
            display: block;
            border: 0;
            background: transparent;
            color: #c33;
            cursor: pointer;
            font-size: 11px;
            margin-top: 6px;
            padding: 5px 0;
        }

        .cart-item-remove:hover {
            color: #a22;
            text-decoration: underline;
        }

        .cart-quantity {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            font-size: 11px;
        }

        .cart-quantity span {
            color: var(--order-muted);
        }

        .cart-quantity strong {
            color: var(--order-ink);
            margin: 0;
        }

        .summary-placeholder {
            padding: 18px 0;
            text-align: center;
        }

        .summary-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #f1f3eb;
            font-size: 24px;
        }

        .summary-placeholder p {
            font-size: 11px;
            line-height: 1.6;
            color: var(--order-muted);
        }

        .summary-note {
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            background: #f5f3e9;
            color: #776b42;
            font-size: 10px;
            line-height: 1.6;
        }

        .form-feedback {
            display: none;
            margin: 12px 0;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.5;
        }

        .form-feedback.show {
            display: block;
        }

        .form-feedback.success {
            background: #eaf4e9;
            color: #2f6a3b;
        }

        .form-feedback.error {
            background: #fbe9e8;
            color: #a63832;
        }

        .form-feedback.info {
            background: #eef2e8;
            color: #52652b;
        }

        .order-submit:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .required-note {
            font-size: 10px;
            color: var(--order-muted);
            margin: 10px 0 18px;
        }

        @media(max-width:800px) {

            .order-layout {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }

        /* SELECCIÓN DE PRODUCTOS */

        .cart-product-select {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .cart-product-select input[type="checkbox"] {
            width: 17px;
            height: 17px;
            min-width: 17px;
            margin-top: 3px;
            accent-color: #3c794e;
            cursor: pointer;
        }

        .cart-product-info {
            flex: 1;
            min-width: 0;
        }

        .cart-product-info strong {
            display: block;
            margin-bottom: 5px;
        }

        .cart-selection-count {
            margin: 12px 0;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f1f3eb;
            color: #52652b;
            font-size: 11px;
        }

        .cart-selection-empty {
            color: #a63832;
            font-size: 11px;
            margin-top: 10px;
        }

        /* VENTANA EMERGENTE DE ELIMINACIÓN */

        .remove-modal {
            width: min(92%, 430px);
            max-width: 430px;
            padding: 0;
            border: none;
            border-radius: 18px;
            background: #fff;
            color: var(--order-ink);
            box-shadow: 0 20px 70px rgba(0, 0, 0, .25);
        }

        .remove-modal[open] {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
        }

        .remove-modal::backdrop {
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(3px);
        }

        .remove-modal-content {
            padding: 28px;
        }

        .remove-modal-content h2 {
            margin: 12px 0;
            font-size: 25px;
        }

        .remove-modal-content h2 span {
            color: #879d45;
        }

        .remove-modal-content p {
            font-size: 12px;
            line-height: 1.7;
            color: var(--order-muted);
        }

        .remove-modal-content .field {
            margin-top: 20px;
        }

        .remove-modal-content input {
            width: 100%;
            box-sizing: border-box;
        }

        .remove-modal-note {
            margin: 12px 0;
            padding: 10px;
            background: #f5f3e9;
            border-radius: 8px;
        }

        .remove-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .remove-modal-actions button {
            flex: 1;
            justify-content: center;
            font-size: 11px;
        }

        .btn-danger {
            background: #c33;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #a22;
        }

        @media(max-width:480px) {

            .remove-modal-actions {
                flex-direction: column;
            }
        }

        /* MIS PEDIDOS */

        .my-orders {
            margin-top: 45px;
        }

        .my-orders-heading {
            margin-bottom: 25px;
        }

        .my-orders-heading h2 {
            font-size: 30px;
            margin: 10px 0;
        }

        .my-orders-heading h2 span {
            color: #879d45;
        }

        .my-orders-heading p {
            color: #777b73;
            font-size: 12px;
        }

        .my-orders-list {
            display: grid;
            gap: 14px;
        }

        .my-order-card {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            align-items: center;
            gap: 20px;
            padding: 22px;
            border: 1px solid #e7e9e1;
            border-radius: 12px;
            background: #fff;
        }

        .my-order-label {
            display: block;
            color: #777b73;
            font-size: 10px;
            margin-bottom: 8px;
        }

        .my-order-info h3 {
            font-size: 15px;
            margin-bottom: 7px;
        }

        .my-order-info p {
            color: #777b73;
            font-size: 11px;
        }

        .my-order-total strong {
            font-size: 16px;
        }

        .order-status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            background: #eef0e9;
            color: #55594f;
        }

        .order-status.pending,
        .order-status.preparing {
            background: #fff3d9;
            color: #94620c;
        }

        .order-status.confirmed,
        .order-status.ready,
        .order-status.delivered,
        .order-status.completed {
            background: #e8f2e9;
            color: #326b40;
        }

        .order-status.cancelled {
            background: #fde9e7;
            color: #b23d36;
        }

        .my-orders-empty {
            padding: 30px;
            text-align: center;
            border: 1px solid #e7e9e1;
            border-radius: 12px;
        }

        .my-orders-empty p {
            margin-top: 8px;
            color: #777b73;
            font-size: 12px;
        }

        /* DETALLE DESPLEGABLE DEL PEDIDO */

        .order-details {
            grid-column: 1 / -1;
            margin-top: 5px;
            border-top: 1px solid #e7e9e1;
            padding-top: 14px;
        }

        .order-details summary {
            cursor: pointer;
            font-weight: 700;
            color: #3c794e;
            font-size: 12px;
            list-style: none;
        }

        .order-details summary::-webkit-details-marker {
            display: none;
        }

        .order-details summary::after {
            content: "+";
            float: right;
            font-size: 20px;
            line-height: 15px;
        }

        .order-details[open] summary::after {
            content: "−";
        }

        .order-details-content {
            padding-top: 18px;
        }

        .order-details-content h4 {
            font-size: 13px;
            margin: 18px 0 12px;
            color: #171916;
        }

        .order-products {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-product {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 14px;
            background: #f7f7f2;
            border-radius: 12px;
        }

        .order-product-info {
            min-width: 0;
        }

        .order-product-info strong {
            font-size: 13px;
        }

        .order-product-info p,
        .order-product-price p {
            margin: 5px 0;
            font-size: 11px;
            color: #666;
            overflow-wrap: anywhere;
        }

        .order-product-price {
            text-align: right;
            white-space: nowrap;
        }

        .order-product-price strong {
            color: #3c794e;
            font-size: 13px;
        }

        .order-extra-info {
            padding: 14px;
            background: #f7f7f2;
            border-radius: 12px;
        }

        .order-extra-info p {
            font-size: 12px;
            margin: 8px 0;
            overflow-wrap: anywhere;
        }

        .order-extra-info strong {
            color: #171916;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #fff3d9;
            color: #94620c;
            font-size: 10px;
            font-weight: 700;
        }

        .payment-status.paid {
            background: #e8f2e9;
            color: #326b40;
        }

        .payment-status.failed,
        .payment-status.cancelled {
            background: #fde9e7;
            color: #b23d36;
        }

        @media(max-width:650px) {

            .my-order-card {
                grid-template-columns: 1fr 1fr;
            }

            .my-order-status {
                grid-column: 1 / -1;
            }

            .order-product {
                flex-direction: column;
            }

            .order-product-price {
                text-align: left;
            }
        }

    </style>

</head>

<body>

<header class="navbar">

    <a href="index.php" class="logo">Footly<span>.</span></a>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="catalogo.php">Catálogo</a>
        <a href="pedido.php" class="active">Pedidos</a>
    </nav>

    <div class="account-actions">
        <span>Hola, <?= h($cliente["full_name"]) ?></span>
        <a href="logout.php" class="btn btn-dark">Cerrar sesión ↗</a>
    </div>

</header>

<main>

    <section class="page-heading">

        <span class="eyebrow">GESTIÓN COMERCIAL</span>

        <h1>Registro de <span>pedido.</span></h1>

        <p>
            Revisa los productos seleccionados y completa los datos de entrega.
        </p>

    </section>

    <section class="section order-layout">

        <form class="order-form" id="orderForm">

            <!-- 01. DATOS DEL CLIENTE -->

            <div class="form-section">

                <span class="step-number">01</span>

                <div class="form-section-content">

                    <h2>Datos del cliente</h2>

                    <p class="muted">Información asociada a tu cuenta.</p>

                    <div class="form-grid">

                        <div class="field">
                            <label>Nombre completo</label>
                            <input
                                type="text"
                                value="<?= h($cliente["full_name"]) ?>"
                                readonly>
                        </div>

                        <div class="field">
                            <label>Empresa o distribuidor</label>
                            <input
                                type="text"
                                value="<?= h($cliente["company_name"]) ?>"
                                readonly>
                        </div>

                        <div class="field">
                            <label>Teléfono</label>
                            <input
                                type="text"
                                value="<?= h($cliente["phone"]) ?>"
                                readonly>
                        </div>

                        <div class="field">
                            <label>Correo electrónico</label>
                            <input
                                type="email"
                                value="<?= h($cliente["email"]) ?>"
                                readonly>
                        </div>

                    </div>

                </div>

            </div>

            <!-- 02. PRODUCTOS -->

            <div class="form-section">

                <span class="step-number">02</span>

                <div class="form-section-content">

                    <h2>Productos seleccionados</h2>

                    <p class="muted">
                        Los productos se recuperan del carrito de tu catálogo.
                    </p>

                    <div id="cartDetails">
                        <p class="muted">Cargando productos...</p>
                    </div>

                    <p class="small-text muted">
                        Puedes regresar al catálogo para agregar más modelos.
                    </p>

                    <a href="catalogo.php" class="btn btn-dark">
                        Regresar al catálogo
                    </a>

                </div>

            </div>

            <!-- 03. ENTREGA Y PAGO -->

            <div class="form-section">

                <span class="step-number">03</span>

                <div class="form-section-content">

                    <h2>Entrega y pago</h2>

                    <p class="muted">
                        Indica cómo deseas gestionar tu pedido.
                    </p>

                    <div class="form-grid">

                        <div class="field">

                            <label for="entrega">Método de entrega *</label>

                            <select id="entrega" required>

                                <option value="">Seleccionar</option>

                                <option value="Recoger en sucursal">
                                    Recoger en sucursal
                                </option>

                                <option value="Entrega a domicilio">
                                    Entrega a domicilio
                                </option>

                                <option value="Entrega a distribuidor">
                                    Entrega a distribuidor
                                </option>

                            </select>

                        </div>

                        <div class="field">

                            <label for="pago">Método de pago *</label>

                            <select id="pago" required>

                                <option value="">Seleccionar</option>

                                <option value="Transferencia bancaria">
                                    Transferencia bancaria
                                </option>

                                <option value="Pago en efectivo">
                                    Pago en efectivo
                                </option>

                                <option value="Pago con tarjeta (por confirmar)">
                                    Pago con tarjeta (por confirmar)
                                </option>

                                <option value="Crédito autorizado">
                                    Crédito autorizado
                                </option>

                            </select>

                        </div>

                        <div class="field field-full">

                            <label for="direccion">Dirección de entrega</label>

                            <input
                                type="text"
                                id="direccion"
                                maxlength="250"
                                value="<?= h($cliente["address"]) ?>"
                                placeholder="Dirección, ciudad y código postal">

                        </div>

                        <div class="field field-full">

                            <label for="observaciones">Observaciones</label>

                            <textarea
                                id="observaciones"
                                rows="4"
                                maxlength="500"
                                placeholder="Indicaciones adicionales del pedido..."></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <p class="required-note">
                * Campos obligatorios. El pedido quedará pendiente de confirmación.
            </p>

            <div class="form-feedback" id="formFeedback" role="status"></div>

            <button
                type="submit"
                class="btn btn-dark btn-full order-submit"
                id="submitOrder"
                disabled>

                Registrar solicitud ↗

            </button>

            <p class="small-text muted">
                Las existencias no se descuentan hasta que el administrador confirme el pedido.
            </p>

        </form>

        <!-- RESUMEN -->

        <aside class="order-summary">

            <span class="eyebrow">RESUMEN</span>

            <h2>Tu pedido<span>.</span></h2>

            <div id="summaryContent" class="summary-placeholder">

                <div class="summary-icon">＋</div>

                <h3>Tu selección aparecerá aquí</h3>

                <p>Agrega productos desde el catálogo.</p>

            </div>

            <div class="summary-line">
                <span>Total de pares</span>
                <strong id="summaryQuantity">0</strong>
            </div>

            <div class="summary-line">
                <span>Subtotal</span>
                <strong id="summarySubtotal">$0.00</strong>
            </div>

            <div class="summary-line">
                <span>Envío</span>
                <strong>Por confirmar</strong>
            </div>

            <div class="summary-total">
                <span>Total estimado*</span>
                <strong id="summaryTotal">$0.00</strong>
            </div>

            <p class="summary-note">
                * El total no incluye envío. El costo final se confirmará según el destino de entrega.
            </p>

            <p class="small-text muted">
                No ingreses datos bancarios en este formulario.
            </p>

        </aside>

    </section>


    <!-- SECCIÓN DE SEGUIMIENTO DE PEDIDOS -->

    <section class="section my-orders">

        <div class="my-orders-heading">

            <span class="eyebrow">SEGUIMIENTO</span>

            <h2>Mis pedidos<span>.</span></h2>

            <p>
                Consulta el estado y los detalles de tus solicitudes.
            </p>

        </div>

        <?php if (empty($misPedidos)): ?>

            <div class="my-orders-empty">

                <h3>Aún no tienes pedidos registrados.</h3>

                <p>
                    Cuando registres una solicitud, aparecerá aquí.
                </p>

            </div>

        <?php else: ?>

            <div class="my-orders-list">

                <?php foreach ($misPedidos as $pedido): ?>

                    <?php

                    $estados = [
                        "pending" => "Pendiente",
                        "confirmed" => "Confirmado",
                        "preparing" => "En preparación",
                        "ready" => "Listo para entrega",
                        "delivered" => "Entregado",
                        "cancelled" => "Cancelado",
                        "completed" => "Completado"
                    ];

                    $estado = $pedido["status"];

                    $nombreEstado = $estados[$estado] ?? $estado;

                    $estadosPago = [
                        "pending" => "Pendiente",
                        "paid" => "Pagado",
                        "failed" => "Fallido",
                        "refunded" => "Reembolsado",
                        "cancelled" => "Cancelado"
                    ];

                    $estadoPago = $estadosPago[
                        $pedido["payment_status"] ?? ""
                    ] ?? "No disponible";

                    $metodosPago = [
                        "transfer" => "Transferencia bancaria",
                        "cash" => "Pago en efectivo",
                        "card" => "Pago con tarjeta",
                        "other" => "Crédito autorizado"
                    ];

                    $metodoPago = $metodosPago[
                        $pedido["payment_method"] ?? ""
                    ] ?? "No disponible";

                    ?>

                    <article class="my-order-card">

                        <!-- INFORMACIÓN PRINCIPAL -->

                        <div class="my-order-info">

                            <span class="my-order-label">
                                Número de pedido
                            </span>

                            <h3>
                                <?= h($pedido["order_number"]) ?>
                            </h3>

                            <p>
                                Fecha:
                                <?= h(date(
                                    "d/m/Y",
                                    strtotime($pedido["order_date"])
                                )) ?>
                            </p>

                        </div>

                        <!-- TOTAL -->

                        <div class="my-order-total">

                            <span class="my-order-label">Total</span>

                            <strong>
                                $<?= h(number_format(
                                    (float)$pedido["total"],
                                    2
                                )) ?>
                            </strong>

                        </div>

                        <!-- ESTADO DEL PEDIDO -->

                        <div class="my-order-status">

                            <span class="my-order-label">
                                Estado del pedido
                            </span>

                            <span class="order-status <?= h($estado) ?>">
                                <?= h($nombreEstado) ?>
                            </span>

                        </div>


                        <!-- DETALLE DESPLEGABLE -->

                        <details class="order-details">

                            <summary>
                                Consultar detalle del pedido
                            </summary>

                            <div class="order-details-content">

                                <!-- PRODUCTOS -->

                                <h4>Productos solicitados</h4>

                                <?php if (!empty($pedido["items"])): ?>

                                    <div class="order-products">

                                        <?php foreach ($pedido["items"] as $producto): ?>

                                            <div class="order-product">

                                                <div class="order-product-info">

                                                    <strong>
                                                        <?= h($producto["product_name"]) ?>
                                                    </strong>

                                                    <p>
                                                        <?= h($producto["variant_description"]) ?>
                                                    </p>

                                                    <p>
                                                        Cantidad:
                                                        <?= (int)$producto["quantity"] ?>
                                                        par(es)
                                                    </p>

                                                </div>

                                                <div class="order-product-price">

                                                    <p>
                                                        Precio unitario:
                                                        $<?= h(number_format(
                                                            (float)$producto["unit_price"],
                                                            2
                                                        )) ?>
                                                    </p>

                                                    <strong>
                                                        $<?= h(number_format(
                                                            (float)$producto["subtotal"],
                                                            2
                                                        )) ?>
                                                    </strong>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php else: ?>

                                    <p>
                                        No hay productos registrados para este pedido.
                                    </p>

                                <?php endif; ?>


                                <!-- ENTREGA Y PAGO -->

                                <h4>Información de entrega y pago</h4>

                                <div class="order-extra-info">

                                    <p>
                                        <strong>Entrega:</strong>

                                        <?= h(
                                            $pedido["informacion"]["Método de entrega"]
                                            ?? "No especificada"
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Método de pago:</strong>

                                        <?= h(
                                            $pedido["informacion"]["Método de pago"]
                                            ?? "No especificado"
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Dirección:</strong>

                                        <?= h(
                                            $pedido["informacion"]["Dirección de entrega"]
                                            ?? "No especificada"
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Observaciones:</strong>

                                        <?= h(
                                            $pedido["informacion"]["Observaciones"]
                                            ?? "Sin observaciones"
                                        ) ?>
                                    </p>

                                </div>


                                <!-- ESTADO DEL PAGO -->

                                <h4>Estado del pago</h4>

                                <div class="order-extra-info">

                                    <p>
                                        <strong>Método registrado:</strong>

                                        <?= h($metodoPago) ?>
                                    </p>

                                    <p>
                                        <strong>Estado:</strong>

                                        <span class="payment-status <?= h(
                                            $pedido["payment_status"] ?? ""
                                        ) ?>">
                                            <?= h($estadoPago) ?>
                                        </span>
                                    </p>

                                </div>

                            </div>

                        </details>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>


<!-- VENTANA EMERGENTE PARA ELIMINAR PRODUCTOS -->

<dialog id="removeModal" class="remove-modal">

    <div class="remove-modal-content">

        <span class="eyebrow">MODIFICAR CARRITO</span>

        <h2>Eliminar productos<span>.</span></h2>

        <p id="removeProductInfo">
            Selecciona cuántos pares deseas eliminar.
        </p>

        <div class="field">

            <label for="removeQuantity">
                Cantidad que deseas eliminar
            </label>

            <input
                type="number"
                id="removeQuantity"
                min="1"
                value="1"
                step="1"
                required>

        </div>

        <p class="remove-modal-note" id="removeModalNote"></p>

        <div class="remove-modal-actions">

            <button
                type="button"
                class="btn btn-dark"
                id="cancelRemove">

                Cancelar

            </button>

            <button
                type="button"
                class="btn btn-danger"
                id="confirmRemove">

                Confirmar eliminación

            </button>

        </div>

    </div>

</dialog>


<footer class="footer">

    <a href="index.php" class="logo">Footly<span>.</span></a>

    <p>Gestión de pedidos</p>

    <span>© 2026</span>

</footer>


<script>

(() => {

    "use strict";

    const API_PEDIDOS = "api/pedido.php";
    const CLAVE_CARRITO = "footly_cart_v1";

    const $ = id => document.getElementById(id);

    const form = $("orderForm");
    const cartDetails = $("cartDetails");
    const summaryContent = $("summaryContent");
    const submitButton = $("submitOrder");
    const feedback = $("formFeedback");

    const removeModal = $("removeModal");
    const removeQuantity = $("removeQuantity");
    const removeProductInfo = $("removeProductInfo");
    const removeModalNote = $("removeModalNote");
    const cancelRemove = $("cancelRemove");
    const confirmRemove = $("confirmRemove");

    let carrito = [];
    let productosSeleccionados = new Set();

    let varianteEliminar = null;

    function leerCarrito() {

        try {

            const contenido = localStorage.getItem(CLAVE_CARRITO);

            const datos = contenido ? JSON.parse(contenido) : [];

            return Array.isArray(datos) ? datos : [];

        } catch (error) {

            console.error("Error al leer el carrito:", error);

            return [];
        }
    }

    function guardarCarrito() {

        try {

            localStorage.setItem(
                CLAVE_CARRITO,
                JSON.stringify(carrito)
            );

            return true;

        } catch (error) {

            console.error("Error al guardar el carrito:", error);

            mostrarMensaje(
                "No fue posible actualizar el carrito.",
                "error"
            );

            return false;
        }
    }

    function dinero(valor) {

        return Number(valor || 0).toLocaleString("es-MX", {
            style: "currency",
            currency: "MXN"
        });
    }

    function escapar(valor) {

        return String(valor ?? "").replace(/[&<>"']/g, caracter => ({

            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"

        })[caracter]);
    }

    function mostrarMensaje(mensaje, tipo = "info") {

        feedback.textContent = mensaje;

        feedback.className = `form-feedback show ${tipo}`;
    }

    function obtenerSeleccionados() {

        return carrito.filter(producto =>
            productosSeleccionados.has(
                String(producto.variant_id)
            )
        );
    }

    // ACTUALIZAR CARRITO Y RESUMEN

    function actualizarCarrito() {

        carrito = leerCarrito();

        const variantesDisponibles = new Set(
            carrito.map(producto =>
                String(producto.variant_id)
            )
        );

        productosSeleccionados.forEach(id => {

            if (!variantesDisponibles.has(id)) {
                productosSeleccionados.delete(id);
            }

        });

        if (carrito.length === 0) {

            productosSeleccionados.clear();

            cartDetails.innerHTML = `

                <div class="summary-placeholder">

                    <div class="summary-icon">＋</div>

                    <h3>No tienes productos en el carrito</h3>

                    <p>Regresa al catálogo para agregar productos.</p>

                </div>

            `;

            summaryContent.innerHTML = `

                <div class="summary-icon">＋</div>

                <h3>Tu selección aparecerá aquí</h3>

                <p>Agrega productos desde el catálogo.</p>

            `;

            $("summaryQuantity").textContent = "0";

            $("summarySubtotal").textContent = dinero(0);

            $("summaryTotal").textContent = dinero(0);

            submitButton.disabled = true;

            return;
        }

        cartDetails.innerHTML = carrito.map(producto => {

            const id = String(producto.variant_id);

            const cantidad = Number(producto.quantity) || 0;

            const precio = Number(producto.price) || 0;

            const subtotal = cantidad * precio;

            const seleccionado = productosSeleccionados.has(id);

            return `

                <div class="summary-product">

                    <div class="cart-product-select">

                        <input
                            type="checkbox"
                            class="cart-checkbox"
                            data-select="${escapar(id)}"
                            ${seleccionado ? "checked" : ""}
                            aria-label="Seleccionar ${escapar(producto.model)}">

                        <div class="cart-product-info">

                            <strong>${escapar(producto.model)}</strong>

                            <span>

                                Código: ${escapar(producto.code)}<br>

                                Color: ${escapar(producto.color)}<br>

                                Talla: ${escapar(producto.size)}<br>

                                Precio: ${dinero(precio)}

                            </span>

                            <div class="cart-quantity">

                                <span>Cantidad:</span>

                                <strong>${cantidad} par(es)</strong>

                            </div>

                            <button
                                type="button"
                                class="cart-item-remove"
                                data-remove="${escapar(id)}">

                                Eliminar producto

                            </button>

                        </div>

                    </div>

                    <b>${dinero(subtotal)}</b>

                </div>

            `;

        }).join("");

        const seleccionados = obtenerSeleccionados();

        let total = 0;

        let cantidadTotal = 0;

        seleccionados.forEach(producto => {

            const cantidad = Number(producto.quantity) || 0;

            const precio = Number(producto.price) || 0;

            total += cantidad * precio;

            cantidadTotal += cantidad;

        });

        summaryContent.innerHTML = `

            <p>
                ${seleccionados.length} de ${carrito.length}
                variantes seleccionadas.
            </p>

            <p>
                Solo los productos seleccionados se incluirán
                en el pedido que registres.
            </p>

        `;

        $("summaryQuantity").textContent = cantidadTotal;

        $("summarySubtotal").textContent = dinero(total);

        $("summaryTotal").textContent = dinero(total);

        submitButton.disabled = seleccionados.length === 0;
    }


    // SELECCIONAR O DESELECCIONAR PRODUCTOS

    cartDetails.addEventListener("change", event => {

        const checkbox = event.target.closest("[data-select]");

        if (!checkbox) return;

        const id = String(checkbox.dataset.select);

        if (checkbox.checked) {

            productosSeleccionados.add(id);

        } else {

            productosSeleccionados.delete(id);

        }

        actualizarCarrito();

    });


    // ABRIR VENTANA DE ELIMINACIÓN

    cartDetails.addEventListener("click", event => {

        const botonEliminar = event.target.closest("[data-remove]");

        if (!botonEliminar) return;

        const id = String(botonEliminar.dataset.remove);

        const producto = carrito.find(item =>
            String(item.variant_id) === id
        );

        if (!producto) return;

        varianteEliminar = id;

        const cantidad = Number(producto.quantity) || 1;

        removeProductInfo.textContent =
            `${producto.model} | Talla ${producto.size} | Color ${producto.color}. Actualmente tienes ${cantidad} pares.`;

        removeQuantity.value = 1;

        removeQuantity.min = 1;

        removeQuantity.max = cantidad;

        removeModalNote.textContent =
            cantidad > 1
                ? `Puedes eliminar entre 1 y ${cantidad} pares.`
                : "Si confirmas, se eliminará este producto del carrito.";

        removeModal.showModal();

        removeQuantity.focus();

    });


    // CANCELAR ELIMINACIÓN

    cancelRemove.addEventListener("click", () => {

        removeModal.close();

        varianteEliminar = null;

    });


    // CONFIRMAR ELIMINACIÓN

    confirmRemove.addEventListener("click", () => {

        if (varianteEliminar === null) {

            removeModal.close();

            return;
        }

        const indice = carrito.findIndex(producto =>
            String(producto.variant_id) === varianteEliminar
        );

        if (indice === -1) {

            removeModal.close();

            varianteEliminar = null;

            actualizarCarrito();

            return;
        }

        const producto = carrito[indice];

        const cantidadActual = Number(producto.quantity) || 1;

        const cantidadEliminar = Number(removeQuantity.value);

        if (
            !Number.isInteger(cantidadEliminar) ||
            cantidadEliminar < 1 ||
            cantidadEliminar > cantidadActual
        ) {

            removeModalNote.textContent =
                `Ingresa una cantidad válida entre 1 y ${cantidadActual}.`;

            return;
        }

        const id = String(producto.variant_id);

        producto.quantity = cantidadActual - cantidadEliminar;

        if (producto.quantity === 0) {

            carrito.splice(indice, 1);

            productosSeleccionados.delete(id);

        }

        if (guardarCarrito()) {

            actualizarCarrito();

            removeModal.close();

            varianteEliminar = null;

            mostrarMensaje(

                cantidadEliminar === cantidadActual
                    ? "El producto se eliminó completamente del carrito."
                    : `Se eliminaron ${cantidadEliminar} pares. Los demás permanecen en tu carrito.`,

                "success"

            );
        }

    });


    // CERRAR VENTANA CON ESCAPE

    removeModal.addEventListener("close", () => {

        varianteEliminar = null;

    });


    // REGISTRAR PEDIDO

    form.addEventListener("submit", async event => {

        event.preventDefault();

        feedback.className = "form-feedback";

        carrito = leerCarrito();

        const seleccionados = obtenerSeleccionados();

        if (seleccionados.length === 0) {

            mostrarMensaje(
                "Selecciona al menos un producto para registrar tu pedido.",
                "error"
            );

            actualizarCarrito();

            return;
        }

        if (!form.reportValidity()) return;

        const variantesEnviadas = seleccionados.map(producto =>
            String(producto.variant_id)
        );

        const datos = {

            items: seleccionados.map(producto => ({

                variant_id: Number(producto.variant_id),

                quantity: Number(producto.quantity)

            })),

            entrega: $("entrega").value,

            pago: $("pago").value,

            direccion: $("direccion").value.trim(),

            observaciones: $("observaciones").value.trim()

        };

        const datosInvalidos = datos.items.some(item =>

            !Number.isInteger(item.variant_id) ||

            item.variant_id <= 0 ||

            !Number.isInteger(item.quantity) ||

            item.quantity <= 0

        );

        if (datosInvalidos) {

            mostrarMensaje(
                "Hay productos con datos inválidos. Regresa al catálogo y vuelve a agregarlos.",
                "error"
            );

            return;
        }

        submitButton.disabled = true;

        submitButton.textContent = "Registrando pedido...";

        try {

            const respuesta = await fetch(API_PEDIDOS, {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify(datos)

            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.exito) {

                throw new Error(
                    resultado.mensaje ||
                    "No fue posible registrar el pedido."
                );

            }

            // CONSERVAR LOS PRODUCTOS NO ENVIADOS

            const carritoActualizado = leerCarrito().filter(producto =>

                !variantesEnviadas.includes(
                    String(producto.variant_id)
                )

            );

            localStorage.setItem(
                CLAVE_CARRITO,
                JSON.stringify(carritoActualizado)
            );

            productosSeleccionados.clear();

            form.reset();

            actualizarCarrito();

            mostrarMensaje(

                `¡Solicitud registrada! Número de pedido: ${resultado.order_number}. Los productos no seleccionados permanecen en tu carrito.`,

                "success"

            );

            window.scrollTo({

                top: 0,

                behavior: "smooth"

            });

        } catch (error) {

            mostrarMensaje(error.message, "error");

            actualizarCarrito();

        } finally {

            submitButton.textContent = "Registrar solicitud ↗";

            submitButton.disabled =
                obtenerSeleccionados().length === 0;

        }

    });


    // DETECTAR CAMBIOS DEL CARRITO DESDE OTRAS PESTAÑAS

    window.addEventListener("storage", event => {

        if (event.key === CLAVE_CARRITO) {

            actualizarCarrito();

        }

    });


    // CARGAR CARRITO AL ABRIR LA PÁGINA

    actualizarCarrito();

})();

</script>

</body>
</html>
