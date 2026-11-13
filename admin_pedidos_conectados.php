<?php
require_once "auth.php";
verificarRol("admin");

require_once "conexion.php";

// Obtener los clientes registrados
$sql = "SELECT
            u.id,
            u.full_name,
            u.email,
            c.phone,
            c.company_name,
            c.address,
            c.created_at
        FROM customers c
        INNER JOIN users u ON c.user_id = u.id
        WHERE u.role = 'customer'
          AND u.is_active = 1
        ORDER BY c.created_at DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$clientes = $stmt->fetchAll();
$totalClientes = count($clientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrativo | Catálogo digital</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="CSS/general.css">
    <link rel="stylesheet" href="CSS/admin.css">
    <link rel="stylesheet" href="CSS/responsive.css">
</head>

<body>

<div class="admin-page">

    <aside class="admin-sidebar">

        <a href="index.html" class="logo">
            <span>Footly</span>
        </a>

        <p class="sidebar-caption">MENÚ PRINCIPAL</p>

        <nav class="admin-nav">
            <a href="#clientes"><span>♙</span> Clientes</a>
            <a href="#pedidos"><span>▤</span> Pedidos</a>
            <a href="#inventario"><span>▣</span> Inventario</a>
            <a href="#pagos"><span>$</span> Pagos</a>
            <a href="#resumen"><span>⌂</span> Resumen</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="perfil.html" class="admin-user">
                <div class="avatar">A</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION["nombre"]) ?></strong>
                    <span>Ver perfil</span>
                </div>
            </a>

            <a href="logout.php" class="sidebar-exit">
                Cerrar sesión
            </a>
        </div>

    </aside>

    <main class="admin-main">

        <header class="admin-topbar" id="inicio">
            <div>
                <span class="section-number">PANEL DE CONTROL / Footly</span>
                <h1>Administrador<span>.</span></h1>
                <p>Gestiona y supervisa las operaciones con honestidad</p>
            </div>

            <div class="admin-date">
                <span>FECHA DE CONSULTA</span>
                <strong>01 NOVIEMBRE 2026</strong>
            </div>
        </header>

        <div class="demo-notice">
            <strong>Panel de administración</strong>
            <span> · Consulta, organiza y controla la información de tu distribuidora.</span>
        </div>

        <section class="stats-grid">

            <article class="stat-card">
                <span class="stat-label">Clientes registrados</span>
                <span class="stat-icon">♙</span>
                <strong><?= $totalClientes ?></strong>
                <span class="stat-note">Clientes totales</span>
            </article>

            <article class="stat-card">
                <span class="stat-label">Pedidos realizados</span>
                <span class="stat-icon">▤</span>
                <strong id="totalOrdersStat">0</strong>
                <span class="stat-note">Pedidos registrados</span>
            </article>

            <article class="stat-card">
                <span class="stat-label">Productos en inventario</span>
                <span class="stat-icon">▣</span>
                <strong id="totalStockStat">59</strong>
                <span class="stat-note">Unidades disponibles (demo)</span>
            </article>

            <article class="stat-card">
                <span class="stat-label">Pagos pendientes</span>
                <span class="stat-icon">$</span>
                <strong id="pendingPaymentsStat">0</strong>
                <span class="stat-note">Pagos por verificar</span>
            </article>

        </section>

        <!-- CLIENTES -->

        <section class="admin-panel" id="clientes">

            <div class="panel-heading">
                <div>
                    <span class="section-number">01 / DIRECTORIO</span>
                    <h2>Clientes</h2>
                    <p>Administra la información de tus clientes registrados.</p>
                </div>

                <div class="panel-tools">
                    <label class="admin-search">
                        <span>⌕</span>
                        <input type="search" placeholder="Buscar cliente...">
                    </label>

                    <button class="btn btn-primary" type="button" id="btnAgregarCliente">
                        + Agregar cliente
                    </button>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Teléfono</th>
                            <th>Correo electrónico</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!empty($clientes)): ?>

                        <?php foreach ($clientes as $cliente): ?>

                            <tr>
                                <td>
                                    #<?= str_pad($cliente["id"], 3, "0", STR_PAD_LEFT) ?>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($cliente["full_name"]) ?></strong>
                                </td>

                                <td>
                                    <?php if (!empty($cliente["company_name"])): ?>
                                        <span class="client-type business">Negocio</span>
                                    <?php else: ?>
                                        <span class="client-type">Particular</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente["phone"] ?? "No registrado") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente["email"]) ?>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button
                                            type="button"
                                            class="table-btn edit"
                                            title="Modificar"
                                            data-edit-client
                                            data-id="<?= (int)$cliente['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($cliente['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-correo="<?= htmlspecialchars($cliente['email'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-telefono="<?= htmlspecialchars($cliente['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-empresa="<?= htmlspecialchars($cliente['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-direccion="<?= htmlspecialchars($cliente['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        >✎</button>

                                        <button
                                            type="button"
                                            class="table-btn delete"
                                            title="Dar de baja"
                                            data-delete-client
                                            data-id="<?= (int)$cliente['id'] ?>"
                                        >×</button>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6">Todavía no hay clientes registrados.</td>
                        </tr>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-info">
                Mostrando <?= $totalClientes ?> clientes registrados
            </div>

        </section>

        <!-- PEDIDOS -->

        <section class="admin-panel" id="pedidos">

            <div class="panel-heading">
                <div>
                    <span class="section-number">02 / OPERACIONES</span>
                    <h2>Pedidos</h2>
                    <p>Consulta y administra las compras realizadas.</p>
                </div>

                <div class="panel-tools">
                    <label class="admin-search">
                        <span>⌕</span>
                        <input
                            id="orderSearch"
                            type="search"
                            placeholder="Buscar por pedido, cliente o producto..."
                        >
                    </label>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID pedido</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="6">Cargando pedidos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-info" id="ordersTableInfo" aria-live="polite">
                Cargando pedidos...
            </div>

        </section>

        <!-- INVENTARIO -->

        <section class="admin-panel" id="inventario">

            <div class="panel-heading">
                <div>
                    <span class="section-number">03 / EXISTENCIAS</span>
                    <h2>Inventario</h2>
                    <p>Registra productos y controla las existencias por modelo, talla y color.</p>
                </div>

                <div class="panel-tools">
                    <label class="admin-search">
                        <span>⌕</span>
                        <input
                            type="search"
                            id="inventorySearch"
                            placeholder="Buscar modelo, marca o código..."
                            aria-label="Buscar producto"
                        >
                    </label>

                    <button class="btn btn-primary" id="addProductBtn" type="button">
                        + Registrar movimiento
                    </button>
                </div>
            </div>

            <div class="inventory-summary">
                <article><span>Referencias registradas</span><strong id="productCount">0</strong></article>
                <article><span>Unidades disponibles</span><strong id="unitsCount">0</strong></article>
                <article><span>Existencias limitadas</span><strong id="lowStockCount">0</strong></article>
                <article><span>Agotados</span><strong id="outStockCount">0</strong></article>
            </div>

            <div class="inventory-toolbar">
                <label>Mostrar:
                    <select id="stockFilter">
                        <option value="all">Todos los productos</option>
                        <option value="available">Con existencia</option>
                        <option value="low">Existencias limitadas</option>
                        <option value="out">Agotados</option>
                    </select>
                </label>

                <span class="stock-legend">
                    <i class="legend-dot good"></i> Disponible
                    <i class="legend-dot low"></i> Bajo
                    <i class="legend-dot out"></i> Agotado
                </span>
            </div>

            <div class="table-scroll">
                <table class="data-table inventory-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>Talla</th>
                            <th>Color</th>
                            <th>Precio</th>
                            <th>Existencias</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody"></tbody>
                </table>
            </div>

            <div class="table-info" id="inventoryTableInfo" aria-live="polite">
                Cargando inventario...
            </div>

            <div class="inventory-history">
                <div class="history-heading">
                    <div>
                        <h3>Movimientos recientes</h3>
                        <p>El registro permanente se guarda en MySQL; aquí se muestra la actividad reciente de este navegador.</p>
                    </div>
                </div>
                <div id="inventoryHistoryList" class="history-list"></div>
            </div>

            <p class="demo-footnote">
                Las entradas, salidas y ajustes actualizan MySQL. El catálogo mostrará las nuevas existencias al volver a cargar la página.
            </p>

        </section>

        <!-- PAGOS -->

        <section class="admin-panel" id="pagos">

            <div class="panel-heading">
                <div>
                    <span class="section-number">04 / FINANZAS</span>
                    <h2>Pagos</h2>
                    <p>Consulta y administra los pagos vinculados a los pedidos.</p>
                </div>

                <div class="panel-tools">
                    <label class="admin-search">
                        <span>⌕</span>
                        <input
                            type="search"
                            id="paymentSearch"
                            placeholder="Buscar pago, pedido o cliente..."
                            aria-label="Buscar pagos"
                        >
                    </label>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID pago</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="paymentsTableBody">
                        <tr><td colspan="8">Cargando pagos...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="table-info" id="paymentsTableInfo" aria-live="polite">
                Consultando pagos...
            </div>

        </section>

        <!-- RESUMEN -->

        <section class="admin-panel" id="resumen">

            <div class="panel-heading">
                <div>
                    <span class="section-number">05 / ANÁLISIS</span>
                    <h2>Resumen general</h2>
                    <p>Indicadores y actividad de la distribuidora.</p>
                </div>
            </div>

            <div class="dashboard-charts">

                <article class="chart-box">
                    <div class="chart-title">
                        <div>
                            <h3>Pedidos recientes</h3>
                            <p>Distribución por estado</p>
                        </div>
                    </div>

                    <div class="order-chart">
                        <div class="order-line">
                            <span><i class="dot process-dot"></i>En proceso</span>
                            <strong>18</strong>
                        </div>
                        <div class="mini-progress">
                            <span class="process-progress"></span>
                        </div>

                        <div class="order-line">
                            <span><i class="dot shipped-dot"></i>Enviados</span>
                            <strong>32</strong>
                        </div>
                        <div class="mini-progress">
                            <span class="shipped-progress"></span>
                        </div>

                        <div class="order-line">
                            <span><i class="dot paid-dot"></i>Pagados</span>
                            <strong>78</strong>
                        </div>
                        <div class="mini-progress">
                            <span class="paid-progress"></span>
                        </div>
                    </div>

                    <div class="chart-bottom">
                        Total de pedidos <strong>128</strong>
                    </div>
                </article>

                <article class="chart-box">
                    <div class="chart-title">
                        <div>
                            <h3>Ventas de los últimos 7 días</h3>
                            <p>Comportamiento de ventas por día</p>
                        </div>
                    </div>

                    <div class="sales-graph">
                        <div class="graph-y">
                            <span>$6 mil</span>
                            <span>$4 mil</span>
                            <span>$2 mil</span>
                            <span>$0</span>
                        </div>

                        <div class="graph-bars">
                            <div class="graph-item"><div class="graph-bar b1" title="$2,000"></div><span>Lun</span></div>
                            <div class="graph-item"><div class="graph-bar b2" title="$2,800"></div><span>Mar</span></div>
                            <div class="graph-item"><div class="graph-bar b3" title="$3,200"></div><span>Mié</span></div>
                            <div class="graph-item"><div class="graph-bar b4" title="$3,800"></div><span>Jue</span></div>
                            <div class="graph-item"><div class="graph-bar b5" title="$4,500"></div><span>Vie</span></div>
                            <div class="graph-item"><div class="graph-bar b6" title="$5,100"></div><span>Sáb</span></div>
                            <div class="graph-item"><div class="graph-bar b7" title="$3,600"></div><span>Dom</span></div>
                        </div>
                    </div>

                    <div class="chart-bottom">
                        Ventas totales <strong>$25,000.00</strong>
                    </div>
                </article>

            </div>

            <div class="recent-activity">
                <h3>Actividad reciente</h3>

                <div class="activity-row">
                    <span class="activity-icon">＋</span>
                    <div>
                        <strong>Nuevo cliente registrado</strong>
                        <p>Lucas Morel fue agregado al directorio.</p>
                    </div>
                    <small>Hoy</small>
                </div>

                <div class="activity-row">
                    <span class="activity-icon">▤</span>
                    <div>
                        <strong>Nuevo pedido realizado</strong>
                        <p>Se registró el pedido PED-003.</p>
                    </div>
                    <small>Ayer</small>
                </div>

                <div class="activity-row">
                    <span class="activity-icon">$</span>
                    <div>
                        <strong>Pago confirmado</strong>
                        <p>Se confirmó el pago del pedido PED-001.</p>
                    </div>
                    <small>Hace 2 días</small>
                </div>
            </div>

        </section>

        <footer class="admin-footer">
            <p>Catálogo digital de calzado</p>
            <span>© 2026</span>
        </footer>

    </main>
</div>

<!-- MODAL DE INVENTARIO -->

<div class="inventory-modal" id="productModal" aria-hidden="true">
    <div class="inventory-modal-backdrop" data-close-modal></div>

    <section class="inventory-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-heading">
            <div>
                <span class="section-number">CONTROL DE EXISTENCIAS</span>
                <h2 id="modalTitle">Registrar movimiento</h2>
                <p id="movementProductInfo">Selecciona la variante y el tipo de movimiento.</p>
            </div>
            <button type="button" class="modal-close" id="closeProductModal" aria-label="Cerrar">×</button>
        </div>

        <form id="productForm">
            <div class="inventory-form-grid">
                <label style="grid-column:1/-1">
                    Variante
                    <select id="movementVariant" required></select>
                </label>

                <label>
                    Tipo de movimiento
                    <select id="movementType" required>
                        <option value="in">Entrada: agregar pares</option>
                        <option value="out">Salida: retirar pares</option>
                        <option value="adjustment">Ajuste: establecer existencia final</option>
                    </select>
                </label>

                <label id="movementQuantityLabel">
                    Cantidad de pares
                    <input id="movementQuantity" required type="number" min="1" step="1" placeholder="Ej. 5">
                </label>

                <label style="grid-column:1/-1">
                    Notas (opcional)
                    <input id="movementNotes" maxlength="255" placeholder="Ej. Reposición de mercancía">
                </label>
            </div>

            <p class="form-feedback" id="productFormFeedback" role="alert"></p>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelProductBtn">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="saveMovementBtn">Guardar movimiento</button>
            </div>
        </form>
    </section>
</div>


<style>
.inventory-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:22px 0}
.inventory-summary article{background:#fff;border:1px solid #e7e9e1;border-radius:12px;padding:16px}
.inventory-summary span{display:block;color:#777b73;font-size:10px;font-weight:600}
.inventory-summary strong{display:block;font-size:25px;margin-top:5px}
.inventory-toolbar{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin:18px 0;font-size:11px}
.inventory-toolbar label{display:flex;align-items:center;gap:8px;font-weight:700}
.inventory-toolbar select{padding:9px 12px;border:1px solid #dfe2d8;background:#fff;color:#171916}
.stock-legend{display:flex;align-items:center;gap:7px;color:#777b73}
.legend-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-left:8px}
.legend-dot.good{background:#3c794e}
.legend-dot.low{background:#d99b28}
.legend-dot.out{background:#d94a43}
.inventory-table{min-width:940px}
.stock-pill{display:inline-flex;padding:5px 9px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap}
.stock-pill.good{background:#e8f2e9;color:#326b40}
.stock-pill.low{background:#fff3d9;color:#94620c}
.stock-pill.out{background:#fde9e7;color:#b23d36}
.inventory-actions{display:flex;gap:6px}
.inventory-actions button{border:0;background:#f0f1eb;border-radius:7px;padding:7px 9px;font-size:11px;color:#252822}
.inventory-actions button:hover{background:#d4f36a}
.inventory-actions button:disabled{opacity:.55;cursor:not-allowed}
.inventory-history{border-top:1px solid #e7e9e1;margin-top:28px;padding-top:22px}
.history-heading h3{font-size:15px}
.history-heading p{font-size:10px;color:#777b73;margin-top:4px}
.history-list{display:grid;gap:0;margin-top:12px}
.history-item{display:flex;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #eef0e9;font-size:11px}
.history-item strong{display:block}
.history-item span,.history-item time{color:#777b73;font-size:10px}
.demo-footnote{margin-top:16px;padding:12px 14px;border-radius:8px;background:#f5f3e9;color:#776b42;font-size:10px;line-height:1.6}
.inventory-modal{display:none;position:fixed;inset:0;z-index:2000;align-items:center;justify-content:center;padding:20px}
.inventory-modal.is-open{display:flex}
.inventory-modal-backdrop{position:absolute;inset:0;background:rgba(18,20,17,.62)}
.inventory-modal-card{position:relative;background:#fff;width:min(680px,100%);max-height:92vh;overflow:auto;border-radius:16px;padding:26px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.modal-heading{display:flex;justify-content:space-between;gap:20px;margin-bottom:22px}
.modal-heading h2{font-size:23px}
.modal-heading p{font-size:11px;color:#777b73;margin-top:5px}
.modal-close{border:0;background:#f0f1eb;width:34px;height:34px;border-radius:50%;font-size:22px}
.inventory-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.inventory-form-grid label{display:grid;gap:6px;font-size:10px;font-weight:700;color:#55594f}
.inventory-form-grid input,.inventory-form-grid select{width:100%;padding:11px 12px;border:1px solid #dfe2d8;border-radius:8px;background:#fff;font-size:12px;color:#171916}
.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}
.btn-secondary{background:#eef0e9;color:#252822;border:0;padding:11px 17px;border-radius:8px;font-weight:700}
.form-feedback{font-size:11px;color:#b23d36;margin-top:10px;min-height:16px}
.empty-inventory{text-align:center;padding:25px;color:#777b73;font-size:12px}

/* GESTIÓN DE ESTADOS DE PEDIDOS */
.order-actions{
    display:flex;
    flex-direction:column;
    align-items:stretch;
    gap:7px;
    min-width:155px;
}
.order-status-select{
    width:100%;
    padding:9px 10px;
    border:1px solid #dfe2d8;
    border-radius:7px;
    background:#fff;
    color:#171916;
    font-family:'Montserrat',sans-serif;
    font-size:10px;
    font-weight:600;
    cursor:pointer;
}
.order-actions button{
    width:100%;
    white-space:nowrap;
    font-size:10px;
}
.order-actions button:disabled{
    opacity:.6;
    cursor:not-allowed;
}

@media(max-width:900px){
    .inventory-summary{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:560px){
    .inventory-summary{grid-template-columns:1fr 1fr}
    .inventory-form-grid{grid-template-columns:1fr}
    .inventory-modal-card{padding:19px}
    .modal-actions{flex-direction:column-reverse}
    .modal-actions button{width:100%}
    .history-item{flex-direction:column;gap:4px}
}
</style>

<script>
(() => {
    "use strict";

    // FUNCIONES Y VARIABLES GENERALES

    const $ = id => document.getElementById(id);

    let products = [];
    let orders = [];

    const HISTORY_KEY = "footly_inventory_history_v1";

    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[character]);
    }

    function money(value) {
        return new Intl.NumberFormat("es-MX", {
            style: "currency",
            currency: "MXN"
        }).format(Number(value) || 0);
    }

    function read(key, fallback = []) {
        try {
            const data = localStorage.getItem(key);
            return data ? JSON.parse(data) : fallback;
        } catch (error) {
            console.error("Error al leer el almacenamiento local:", error);
            return fallback;
        }
    }

    function save() {
        try {
            localStorage.setItem(
                HISTORY_KEY,
                JSON.stringify(inventoryHistory)
            );
        } catch (error) {
            console.error("No fue posible guardar el historial:", error);
        }
    }

    let inventoryHistory = read(HISTORY_KEY, []);

    function addHistory(message, detail = "") {
        inventoryHistory.unshift({
            message,
            detail,
            date: new Date().toISOString()
        });

        inventoryHistory = inventoryHistory.slice(0, 30);
        save();
    }

    // ESTADOS DEL INVENTARIO

    function stockState(product) {
        const stock = Number(product.stock) || 0;
        const minimum = Number(product.minimum) || 0;

        if (stock <= 0) return "out";
        if (stock <= minimum) return "low";

        return "good";
    }

    function stockLabel(state) {
        const labels = {
            good: "Disponible",
            low: "Existencias limitadas",
            out: "Agotado"
        };

        return labels[state] || "Sin estado";
    }

    // PEDIDOS CONECTADOS A MYSQL

    const orderMoney = value => money(value);

    const orderDate = value => {
        if (!value) return "—";

        const normalized = String(value).replace(" ", "T");
        const date = new Date(normalized);

        return Number.isNaN(date.getTime())
            ? "—"
            : date.toLocaleString("es-MX");
    };

    function statusInfo(status) {
        const states = {
            pending: {
                label: "Pendiente",
                css: "status-process"
            },
            confirmed: {
                label: "Confirmado",
                css: "status-paid"
            },
            preparing: {
                label: "En preparación",
                css: "status-process"
            },
            ready: {
                label: "Listo para entrega",
                css: "status-paid"
            },
            delivered: {
                label: "Entregado",
                css: "status-paid"
            },
            cancelled: {
                label: "Cancelado",
                css: "status-shipped"
            },
            completed: {
                label: "Completado",
                css: "status-paid"
            }
        };

        return states[status] || {
            label: status || "Sin estado",
            css: "status-process"
        };
    }

    async function cargarPedidos() {
        const tbody = $("ordersTableBody");
        const info = $("ordersTableInfo");

        tbody.innerHTML = `
            <tr>
                <td colspan="6">Consultando pedidos en MySQL...</td>
            </tr>
        `;

        info.textContent = "Cargando pedidos...";

        try {
            const response = await fetch("api/admin_pedidos.php", {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                },
                cache: "no-store"
            });

            const result = await response.json();

            if (!response.ok || !result.exito) {
                throw new Error(
                    result.mensaje ||
                    "No fue posible consultar los pedidos."
                );
            }

            orders = Array.isArray(result.pedidos)
                ? result.pedidos
                : [];

            renderOrders();

        } catch (error) {
            console.error("Error al cargar pedidos:", error);

            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-inventory">
                        No fue posible cargar los pedidos desde MySQL.
                    </td>
                </tr>
            `;

            info.textContent = error.message;
            $("totalOrdersStat").textContent = "—";
        }
    }

    function renderOrders() {
        const search = ($("orderSearch")?.value || "")
            .trim()
            .toLowerCase();

        const filtered = orders.filter(order => {
            const customer = order.customer || {};
            const items = Array.isArray(order.items)
                ? order.items
                : [];

            const searchable = [
                order.order_number,
                order.id,
                customer.name,
                customer.company,
                customer.phone,
                customer.email,
                order.status,
                order.delivery,
                order.payment,
                ...items.flatMap(item => [
                    item.model,
                    item.product_name,
                    item.code,
                    item.sku,
                    item.color,
                    item.size
                ])
            ];

            return searchable.some(value =>
                String(value ?? "").toLowerCase().includes(search)
            );
        });

        const tbody = $("ordersTableBody");

        if (!filtered.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-inventory">
                        ${orders.length
                            ? "No hay pedidos que coincidan con la búsqueda."
                            : "Todavía no hay pedidos registrados."}
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = filtered.map(order => {
                const customer = order.customer || {};
                const items = Array.isArray(order.items)
                    ? order.items
                    : [];

                const state = statusInfo(order.status);

                const productSummary = items.map(item =>
                    `${item.model || item.product_name || "Producto"} (${Number(item.quantity) || 0})`
                ).join(", ");

                const statusOptions = [
                    { value: "pending", label: "Pendiente" },
                    { value: "confirmed", label: "Confirmado" },
                    { value: "preparing", label: "En preparación" },
                    { value: "ready", label: "Listo para entrega" },
                    { value: "delivered", label: "Entregado" },
                    { value: "cancelled", label: "Cancelado" },
                    { value: "completed", label: "Completado" }
                ];

                const statusSelectHTML = `
                    <select
                        class="order-status-select"
                        data-order-status="${Number(order.order_id)}"
                        aria-label="Nuevo estado del pedido"
                    >
                        ${statusOptions.map(option => `
                            <option
                                value="${option.value}"
                                ${order.status === option.value ? "selected" : ""}
                            >
                                ${option.label}
                            </option>
                        `).join("")}
                    </select>
                `;

                return `
                    <tr>
                        <td>
                            <strong>
                                ${escapeHTML(order.order_number || order.id)}
                            </strong>
                            <br>
                            <small>
                                ${escapeHTML(productSummary || "Sin productos")}
                            </small>
                        </td>

                        <td>
                            <strong>
                                ${escapeHTML(customer.name || "Cliente sin nombre")}
                            </strong>
                            <br>
                            <small>
                                ${escapeHTML(customer.company || customer.email || "")}
                            </small>
                        </td>

                        <td>${escapeHTML(orderDate(order.date))}</td>

                        <td>
                            <span class="status ${state.css}">
                                ${escapeHTML(state.label)}
                            </span>
                        </td>

                        <td>
                            <strong>${orderMoney(order.total)}</strong>
                        </td>

                        <td>
                            <div class="order-actions">
                                ${statusSelectHTML}

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    data-update-order-status="${Number(order.order_id)}">
                                    Actualizar estado
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-detail-order="${Number(order.order_id)}">
                                    Ver detalle
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        $("ordersTableInfo").textContent =
            `Mostrando ${filtered.length} de ${orders.length} pedidos registrados`;

        $("totalOrdersStat").textContent = orders.length;
    }

    // MOSTRAR DETALLE DEL PEDIDO

    function verDetallePedido(orderId) {
        const order = orders.find(item =>
            Number(item.order_id) === Number(orderId)
        );

        if (!order) {
            alert("No se encontró la información del pedido.");
            return;
        }

        const customer = order.customer || {};
        const items = Array.isArray(order.items)
            ? order.items
            : [];

        const state = statusInfo(order.status);

        const productosHTML = items.length
            ? items.map(item => `
                <tr>
                    <td>
                        <strong>
                            ${escapeHTML(item.model || item.product_name || "Producto")}
                        </strong>
                        <br>
                        <small>${escapeHTML(item.code || item.sku || "")}</small>
                    </td>
                    <td>${escapeHTML(item.size ?? "—")}</td>
                    <td>${escapeHTML(item.color || "—")}</td>
                    <td>${Number(item.quantity) || 0}</td>
                    <td>${orderMoney(item.unit_price ?? item.unitPrice)}</td>
                    <td>
                        <strong>${orderMoney(item.subtotal)}</strong>
                    </td>
                </tr>
            `).join("")
            : `
                <tr>
                    <td colspan="6">
                        Este pedido no tiene productos registrados.
                    </td>
                </tr>
            `;

        $("orderDetailModal")?.remove();

        const modalHTML = `
            <div class="inventory-modal is-open"
                 id="orderDetailModal"
                 aria-hidden="false">

                <div class="inventory-modal-backdrop"
                     data-close-order-detail></div>

                <section class="inventory-modal-card"
                         role="dialog"
                         aria-modal="true"
                         aria-labelledby="orderDetailTitle">

                    <div class="modal-heading">
                        <div>
                            <span class="section-number">DETALLE DEL PEDIDO</span>
                            <h2 id="orderDetailTitle">
                                ${escapeHTML(order.order_number || order.id)}
                            </h2>
                            <p>Consulta la información de la solicitud.</p>
                        </div>

                        <button type="button"
                                class="modal-close"
                                data-close-order-detail
                                aria-label="Cerrar">×</button>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:22px;">

                        <div>
                            <small>CLIENTE</small>
                            <p><strong>${escapeHTML(customer.name || "—")}</strong></p>
                        </div>

                        <div>
                            <small>NEGOCIO</small>
                            <p>${escapeHTML(customer.company || "Particular")}</p>
                        </div>

                        <div>
                            <small>CORREO</small>
                            <p>${escapeHTML(customer.email || "—")}</p>
                        </div>

                        <div>
                            <small>TELÉFONO</small>
                            <p>${escapeHTML(customer.phone || "—")}</p>
                        </div>

                        <div>
                            <small>FECHA</small>
                            <p>${escapeHTML(orderDate(order.date))}</p>
                        </div>

                        <div>
                            <small>ESTADO</small>
                            <p>
                                <span class="status ${state.css}">
                                    ${escapeHTML(state.label)}
                                </span>
                            </p>
                        </div>

                        <div>
                            <small>MÉTODO DE ENTREGA</small>
                            <p>${escapeHTML(order.delivery || "No especificado")}</p>
                        </div>

                        <div>
                            <small>MÉTODO DE PAGO</small>
                            <p>${escapeHTML(order.payment || "No especificado")}</p>
                        </div>

                        <div style="grid-column:1/-1;">
                            <small>DIRECCIÓN DE ENTREGA</small>
                            <p>${escapeHTML(order.address || "No especificada")}</p>
                        </div>

                    </div>

                    <h3 style="font-size:15px;margin-bottom:12px;">
                        Productos solicitados
                    </h3>

                    <div class="table-scroll">
                        <table class="data-table" style="min-width:650px;">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Talla</th>
                                    <th>Color</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>${productosHTML}</tbody>
                        </table>
                    </div>

                    <div style="margin-top:20px;padding:15px;background:#f5f6ef;border-radius:10px;">
                        <small>TOTAL DEL PEDIDO</small>
                        <h2 style="margin-top:5px;">
                            ${orderMoney(order.total)}
                        </h2>
                    </div>

                    <div style="margin-top:16px;">
                        <small>OBSERVACIONES</small>
                        <p>${escapeHTML(order.observations || "Sin observaciones.")}</p>
                    </div>

                    <div class="modal-actions">
                        <button type="button"
                                class="btn btn-primary"
                                data-close-order-detail>
                            Cerrar detalle
                        </button>
                    </div>

                </section>
            </div>
        `;

        document.body.insertAdjacentHTML("beforeend", modalHTML);

        const modal = $("orderDetailModal");

        modal.querySelectorAll("[data-close-order-detail]").forEach(button => {
            button.addEventListener("click", () => modal.remove());
        });
    }

    // EVENTOS DE PEDIDOS

    $("orderSearch").addEventListener("input", renderOrders);

    $("ordersTableBody").addEventListener("click", event => {
        const button = event.target.closest("[data-detail-order]");

        if (button) {
            verDetallePedido(button.dataset.detailOrder);
        }
    });

    // ACTUALIZAR ESTADO DEL PEDIDO EN MYSQL

    $("ordersTableBody").addEventListener("click", async event => {
        const button = event.target.closest("[data-update-order-status]");

        if (!button) return;

        const orderId = Number(button.dataset.updateOrderStatus);

        const select = document.querySelector(
            `[data-order-status="${orderId}"]`
        );

        if (!select) {
            alert("No se encontró el selector del estado.");
            return;
        }

        const newStatus = select.value;

        const order = orders.find(item =>
            Number(item.order_id) === orderId
        );

        if (!order) {
            alert("No se encontró el pedido.");
            return;
        }

        if (order.status === newStatus) {
            alert("El pedido ya tiene ese estado.");
            return;
        }

        const labels = {
            pending: "Pendiente",
            confirmed: "Confirmado",
            preparing: "En preparación",
            ready: "Listo para entrega",
            delivered: "Entregado",
            cancelled: "Cancelado",
            completed: "Completado"
        };

        const confirmation = confirm(
            `¿Deseas cambiar el estado del pedido ${order.order_number} de "${labels[order.status] || order.status}" a "${labels[newStatus]}"?`
        );

        if (!confirmation) return;

        button.disabled = true;
        button.textContent = "Actualizando...";

        try {
            const response = await fetch(
                "api/actualizar_estado_pedido.php",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus
                    })
                }
            );

            const result = await response.json();

            if (!response.ok || !result.exito) {
                throw new Error(
                    result.mensaje ||
                    "No fue posible actualizar el pedido."
                );
            }

            alert(result.mensaje);

            await cargarPedidos();

        } catch (error) {
            console.error("Error al actualizar el pedido:", error);
            alert(error.message);
            await cargarPedidos();

        } finally {
            button.disabled = false;
            button.textContent = "Actualizar estado";
        }
    });

    // INVENTARIO: REPRESENTACIÓN DE DATOS

    function render() {
        const query = $("inventorySearch").value
            .trim()
            .toLowerCase();

        const filter = $("stockFilter").value;

        const visible = products.filter(product => {
            const matches = [
                product.code,
                product.model,
                product.brand,
                product.category,
                product.color,
                String(product.size)
            ].some(value =>
                String(value ?? "").toLowerCase().includes(query)
            );

            const state = stockState(product);

            return matches && (
                filter === "all" ||
                (filter === "available" && Number(product.stock) > 0) ||
                (filter === "low" && state === "low") ||
                (filter === "out" && state === "out")
            );
        });

        $("inventoryTableBody").innerHTML = visible.length
            ? visible.map(product => {
                const state = stockState(product);

                return `
                    <tr>
                        <td>${escapeHTML(product.code)}</td>

                        <td>
                            <strong>${escapeHTML(product.model)}</strong>
                        </td>

                        <td>${escapeHTML(product.brand)}</td>
                        <td>${escapeHTML(product.category)}</td>
                        <td>${escapeHTML(product.size)}</td>
                        <td>${escapeHTML(product.color)}</td>
                        <td>${money(product.price)}</td>

                        <td>
                            <strong>${Number(product.stock) || 0} pares</strong>
                        </td>

                        <td>
                            <span class="stock-pill ${state}">
                                ${stockLabel(state)}
                            </span>
                        </td>

                        <td>
                            <div class="inventory-actions">
                                <button
                                    type="button"
                                    data-movement-id="${Number(product.id)}">
                                    Movimiento
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("")
            : `
                <tr>
                    <td colspan="10" class="empty-inventory">
                        No se encontraron productos con esos criterios.
                    </td>
                </tr>
            `;

        const units = products.reduce(
            (sum, product) => sum + (Number(product.stock) || 0),
            0
        );

        $("productCount").textContent = products.length;
        $("unitsCount").textContent = units;

        $("lowStockCount").textContent =
            products.filter(product => stockState(product) === "low").length;

        $("outStockCount").textContent =
            products.filter(product => stockState(product) === "out").length;

        $("totalStockStat").textContent = units;

        $("inventoryTableInfo").textContent =
            `Mostrando ${visible.length} de ${products.length} registros de inventario`;

        $("inventoryHistoryList").innerHTML = inventoryHistory.length
            ? inventoryHistory.map(item => `
                <div class="history-item">
                    <div>
                        <strong>${escapeHTML(item.message)}</strong>
                        <span>${escapeHTML(item.detail || "")}</span>
                    </div>
                    <time>
                        ${new Date(item.date).toLocaleString("es-MX")}
                    </time>
                </div>
            `).join("")
            : `
                <p class="empty-inventory">
                    Todavía no hay movimientos registrados.
                </p>
            `;
    }

    // CARGAR INVENTARIO DESDE MYSQL

    async function cargarInventario() {
        $("inventoryTableInfo").textContent =
            "Consultando inventario en MySQL...";

        try {
            const response = await fetch("api/inventario.php", {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                },
                cache: "no-store"
            });

            if (!response.ok) {
                throw new Error(
                    "El servidor no pudo consultar el inventario."
                );
            }

            const datos = await response.json();

            if (!Array.isArray(datos)) {
                throw new Error(
                    datos.error ||
                    "La respuesta del inventario no es válida."
                );
            }

            products = datos.map(product => ({
                ...product,
                id: Number(product.id),
                product_id: Number(product.product_id),
                size: Number(product.size),
                price: Number(product.price),
                stock: Number(product.stock),
                minimum: Number(product.minimum)
            }));

            render();

        } catch (error) {
            console.error("Error al cargar inventario:", error);

            $("inventoryTableBody").innerHTML = `
                <tr>
                    <td colspan="10" class="empty-inventory">
                        No fue posible cargar el inventario desde MySQL.
                    </td>
                </tr>
            `;

            $("inventoryTableInfo").textContent = error.message;
        }
    }

    // MODAL DE MOVIMIENTOS DE INVENTARIO

    function openModal(product = null) {
        $("productForm").reset();
        $("productFormFeedback").textContent = "";

        const select = $("movementVariant");

        if (!products.length) {
            select.innerHTML = `
                <option value="">No hay variantes disponibles</option>
            `;

            $("productFormFeedback").textContent =
                "No hay variantes de inventario para registrar movimientos.";
        } else {
            select.innerHTML = products.map(product => `
                <option value="${product.id}">
                    ${escapeHTML(product.code)} ·
                    ${escapeHTML(product.model)} ·
                    Talla ${escapeHTML(product.size)} ·
                    ${escapeHTML(product.color)}
                    (stock: ${product.stock})
                </option>
            `).join("");
        }

        if (product) {
            select.value = String(product.id);
        }

        updateMovementQuantityLabel();
        updateMovementProductInfo();

        $("productModal").classList.add("is-open");
        $("productModal").setAttribute("aria-hidden", "false");
        $("movementType").focus();
    }

    function closeModal() {
        $("productModal").classList.remove("is-open");
        $("productModal").setAttribute("aria-hidden", "true");
    }

    function updateMovementQuantityLabel() {
        const adjustment = $("movementType").value === "adjustment";

        $("movementQuantityLabel").firstChild.textContent =
            adjustment
                ? "Existencia final deseada"
                : "Cantidad de pares";

        $("movementQuantity").min = adjustment ? "0" : "1";

        $("movementQuantity").placeholder =
            adjustment ? "Ej. 10" : "Ej. 5";
    }

    function updateMovementProductInfo() {
        const product = products.find(item =>
            String(item.id) === $("movementVariant").value
        );

        $("movementProductInfo").textContent = product
            ? `${product.model} · Talla ${product.size} · ${product.color} · Existencia actual: ${product.stock} pares`
            : "Selecciona la variante y el tipo de movimiento.";
    }

    $("addProductBtn").addEventListener("click", () => openModal());

    $("closeProductModal").addEventListener("click", closeModal);

    $("cancelProductBtn").addEventListener("click", closeModal);

    $("productModal").addEventListener("click", event => {
        if (event.target.hasAttribute("data-close-modal")) {
            closeModal();
        }
    });

    document.addEventListener("keydown", event => {
        if (
            event.key === "Escape" &&
            $("productModal").classList.contains("is-open")
        ) {
            closeModal();
        }
    });

    $("movementType").addEventListener(
        "change",
        updateMovementQuantityLabel
    );

    $("movementVariant").addEventListener(
        "change",
        updateMovementProductInfo
    );

    $("inventoryTableBody").addEventListener("click", event => {
        const button = event.target.closest("[data-movement-id]");

        if (!button) return;

        const product = products.find(item =>
            String(item.id) === button.dataset.movementId
        );

        if (product) {
            openModal(product);
        }
    });

    // REGISTRAR MOVIMIENTOS EN MYSQL

    $("productForm").addEventListener("submit", async event => {
        event.preventDefault();

        const feedback = $("productFormFeedback");
        const button = $("saveMovementBtn");

        const quantity = Number($("movementQuantity").value);
        const type = $("movementType").value;
        const variantId = Number($("movementVariant").value);

        if (!variantId) {
            feedback.textContent = "Selecciona una variante válida.";
            return;
        }

        if (
            !Number.isInteger(quantity) ||
            quantity < (type === "adjustment" ? 0 : 1)
        ) {
            feedback.textContent = "Ingresa una cantidad válida.";
            return;
        }

        button.disabled = true;
        button.textContent = "Guardando...";
        feedback.textContent = "";

        try {
            const response = await fetch(
                "api/movimiento_inventario.php",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        variant_id: variantId,
                        movement_type: type,
                        quantity: quantity,
                        notes: $("movementNotes").value.trim()
                    })
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.error ||
                    "No fue posible guardar el movimiento."
                );
            }

            const product = products.find(item =>
                Number(item.id) === variantId
            );

            const movementName = {
                in: "Entrada de inventario",
                out: "Salida de inventario",
                adjustment: "Ajuste de inventario"
            };

            addHistory(
                movementName[type],
                `${product
                    ? `${product.model} · talla ${product.size}`
                    : "Variante"} · ${result.previous_stock} → ${result.new_stock} pares`
            );

            closeModal();

            await cargarInventario();

            alert("Movimiento registrado correctamente.");

        } catch (error) {
            console.error("Error al registrar movimiento:", error);
            feedback.textContent = error.message;

        } finally {
            button.disabled = false;
            button.textContent = "Guardar movimiento";
        }
    });

    // FILTROS Y BÚSQUEDAS

    $("inventorySearch").addEventListener("input", render);

    $("stockFilter").addEventListener("change", render);

    window.addEventListener("storage", event => {
        if (event.key === HISTORY_KEY) {
            inventoryHistory = read(HISTORY_KEY, []);
            render();
        }
    });

    // INICIALIZAR PANEL

    render();
    cargarPedidos();
    cargarInventario();

})();
</script>



<div class="inventory-modal" id="clienteModal" aria-hidden="true">
    <div class="inventory-modal-backdrop" data-close-cliente></div>

    <section class="inventory-modal-card" role="dialog" aria-modal="true" aria-labelledby="clienteModalTitle">
        <div class="modal-heading">
            <div>
                <span class="section-number">DIRECTORIO DE CLIENTES</span>
                <h2 id="clienteModalTitle">Agregar cliente</h2>
                <p>Registra la información del cliente.</p>
            </div>
            <button type="button" class="modal-close" id="cerrarClienteModal" aria-label="Cerrar">×</button>
        </div>

        <form id="formCliente">
            <input type="hidden" name="id" id="clienteId">

            <div class="inventory-form-grid">
                <label>Nombre completo *
                    <input type="text" name="nombre" id="clienteNombre" maxlength="120" required>
                </label>

                <label>Correo electrónico *
                    <input type="email" name="correo" id="clienteCorreo" maxlength="150" required>
                </label>

                <label>Teléfono
                    <input type="tel" name="telefono" id="clienteTelefono" maxlength="25">
                </label>

                <label>Nombre del negocio
                    <input type="text" name="empresa" id="clienteEmpresa" maxlength="150">
                </label>

                <label style="grid-column:1/-1">Dirección
                    <input type="text" name="direccion" id="clienteDireccion" maxlength="255">
                </label>

                <label id="campoPassword" style="grid-column:1/-1">Contraseña inicial *
                    <input type="password" name="password" id="clientePassword" minlength="8" autocomplete="new-password" required>
                </label>
            </div>

            <p class="form-feedback" id="clienteFeedback" role="alert" aria-live="polite"></p>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancelarCliente">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="guardarCliente">Guardar cliente</button>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('clienteModal');
    const form = document.getElementById('formCliente');
    const feedback = document.getElementById('clienteFeedback');
    const saveButton = document.getElementById('guardarCliente');
    const passwordField = document.getElementById('campoPassword');
    const passwordInput = document.getElementById('clientePassword');

    if (!modal || !form) {
        console.error('No se encontró el formulario o modal de clientes.');
        return;
    }

    const field = id => document.getElementById(id);

    function openClientModal(mode = 'add', client = {}) {
        form.reset();
        feedback.textContent = '';

        field('clienteId').value = client.id || '';
        field('clienteNombre').value = client.nombre || '';
        field('clienteCorreo').value = client.correo || '';
        field('clienteTelefono').value = client.telefono || '';
        field('clienteEmpresa').value = client.empresa || '';
        field('clienteDireccion').value = client.direccion || '';

        const editing = mode === 'edit';

        field('clienteModalTitle').textContent =
            editing ? 'Modificar cliente' : 'Agregar cliente';

        saveButton.textContent =
            editing ? 'Guardar cambios' : 'Guardar cliente';

        passwordField.hidden = editing;
        passwordInput.required = !editing;
        passwordInput.disabled = editing;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        field('clienteNombre').focus();
    }

    function closeClientModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        form.reset();
        feedback.textContent = '';
    }

    // Abrir formulario de alta.
    field('btnAgregarCliente')?.addEventListener('click', event => {
        event.preventDefault();
        openClientModal('add');
    });

    field('cerrarClienteModal')?.addEventListener('click', closeClientModal);
    field('cancelarCliente')?.addEventListener('click', closeClientModal);

    modal.addEventListener('click', event => {
        if (event.target.matches('[data-close-cliente]')) {
            closeClientModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeClientModal();
        }
    });

    // Editar o dar de baja desde la tabla.
    document.querySelector('#clientes tbody')?.addEventListener('click', async event => {
        const editButton = event.target.closest('[data-edit-client]');
        const deleteButton = event.target.closest('[data-delete-client]');

        if (editButton) {
            openClientModal('edit', {
                id: editButton.dataset.id,
                nombre: editButton.dataset.nombre,
                correo: editButton.dataset.correo,
                telefono: editButton.dataset.telefono,
                empresa: editButton.dataset.empresa,
                direccion: editButton.dataset.direccion
            });

            return;
        }

        if (deleteButton) {
            if (!confirm('¿Deseas dar de baja a este cliente?')) return;

            const data = new FormData();
            data.append('accion', 'eliminar');
            data.append('id', deleteButton.dataset.id);

            try {
                const response = await fetch('api/clientes.php', {
                    method: 'POST',
                    body: data
                });

                const result = await response.json();

                if (!response.ok || !result.exito) {
                    throw new Error(
                        result.mensaje ||
                        'No fue posible dar de baja al cliente.'
                    );
                }

                alert(result.mensaje);
                window.location.reload();

            } catch (error) {
                alert(error.message);
            }
        }
    });

    // Crear o actualizar cliente.
    form.addEventListener('submit', async event => {
        event.preventDefault();

        feedback.textContent = '';

        const data = new FormData(form);
        const id = data.get('id');

        data.append('accion', id ? 'editar' : 'agregar');

        saveButton.disabled = true;
        saveButton.textContent = 'Guardando...';

        try {
            const response = await fetch('api/clientes.php', {
                method: 'POST',
                body: data
            });

            const result = await response.json();

            if (!response.ok || !result.exito) {
                throw new Error(
                    result.mensaje ||
                    'No fue posible guardar el cliente.'
                );
            }

            alert(result.mensaje);
            window.location.reload();

        } catch (error) {
            feedback.textContent = error.message;

        } finally {
            saveButton.disabled = false;
            saveButton.textContent =
                id ? 'Guardar cambios' : 'Guardar cliente';
        }
    });

    // Búsqueda del directorio.
    document.querySelector('#clientes input[type="search"]')?.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();

        document.querySelectorAll('#clientes tbody tr').forEach(row => {
            row.hidden = !row.textContent.toLowerCase().includes(term);
        });
    });
});
</script>

<script>
(() => {
    "use strict";

    const paymentBody = document.getElementById("paymentsTableBody");
    const paymentInfo = document.getElementById("paymentsTableInfo");
    const paymentSearch = document.getElementById("paymentSearch");
    const pendingStat = document.getElementById("pendingPaymentsStat");

    if (!paymentBody || !paymentInfo) return;

    let payments = [];

    function escapePaymentHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[character]);
    }

    function paymentMoney(value) {
        return new Intl.NumberFormat("es-MX", {
            style: "currency",
            currency: "MXN"
        }).format(Number(value) || 0);
    }

    function paymentMethodLabel(method) {
        const methods = {
            cash: "Efectivo",
            transfer: "Transferencia",
            card: "Tarjeta",
            other: "Otro"
        };

        return methods[method] || "No especificado";
    }

    function paymentStatusInfo(status) {
        const states = {
            pending: {
                label: "Pendiente",
                css: "status-pending"
            },
            paid: {
                label: "Pagado",
                css: "status-paid"
            },
            refunded: {
                label: "Reembolsado",
                css: "status-shipped"
            }
        };

        return states[status] || {
            label: status || "Sin estado",
            css: "status-pending"
        };
    }

    function paymentDate(value) {
        if (!value) return "—";

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) return "—";

        return date.toLocaleString("es-MX");
    }

    // MOSTRAR PAGOS

    function renderPayments() {
        const search = (paymentSearch?.value || "")
            .trim()
            .toLowerCase();

        const filtered = payments.filter(payment => {
            const searchable = [
                payment.payment_id,
                payment.order_number,
                payment.customer_name,
                payment.customer_email,
                payment.company_name,
                payment.method,
                payment.status,
                payment.amount
            ];

            return searchable.some(value =>
                String(value ?? "").toLowerCase().includes(search)
            );
        });

        if (!filtered.length) {
            paymentBody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-inventory">
                        ${payments.length
                            ? "No hay pagos que coincidan con la búsqueda."
                            : "Todavía no hay pagos registrados."}
                    </td>
                </tr>
            `;
        } else {
            paymentBody.innerHTML = filtered.map(payment => {
                const state = paymentStatusInfo(payment.status);

                let actionHTML = "";

                if (payment.status === "pending") {
                    actionHTML = `
                        <button
                            type="button"
                            class="btn btn-primary"
                            data-payment-action="paid"
                            data-payment-id="${Number(payment.payment_id)}">
                            Confirmar pago
                        </button>
                    `;
                } else if (payment.status === "paid") {
                    actionHTML = `
                        <button
                            type="button"
                            class="table-btn delete"
                            title="Registrar reembolso"
                            data-payment-action="refunded"
                            data-payment-id="${Number(payment.payment_id)}">
                            Reembolsar
                        </button>
                    `;
                } else {
                    actionHTML = `
                        <span class="status status-shipped">
                            Sin acciones
                        </span>
                    `;
                }

                return `
                    <tr>
                        <td>
                            <strong>
                                #PAG-${String(payment.payment_id).padStart(3, "0")}
                            </strong>
                        </td>

                        <td>
                            <strong>
                                ${escapePaymentHTML(payment.order_number || "—")}
                            </strong>
                        </td>

                        <td>
                            <strong>
                                ${escapePaymentHTML(payment.customer_name || "—")}
                            </strong>
                            <br>
                            <small>
                                ${escapePaymentHTML(
                                    payment.company_name || payment.customer_email || ""
                                )}
                            </small>
                        </td>

                        <td>
                            ${escapePaymentHTML(
                                paymentMethodLabel(payment.method)
                            )}
                        </td>

                        <td>
                            <strong>
                                ${paymentMoney(payment.amount)}
                            </strong>
                        </td>

                        <td>
                            <span class="status ${state.css}">
                                ${escapePaymentHTML(state.label)}
                            </span>
                        </td>

                        <td>
                            ${escapePaymentHTML(paymentDate(payment.payment_date))}
                        </td>

                        <td>
                            <div class="table-actions">
                                ${actionHTML}
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        const pending = payments.filter(
            payment => payment.status === "pending"
        ).length;

        if (pendingStat) {
            pendingStat.textContent = pending;
        }

        paymentInfo.textContent =
            `Mostrando ${filtered.length} de ${payments.length} pagos registrados`;
    }

    // CONSULTAR PAGOS EN MYSQL

    async function cargarPagos() {
        paymentBody.innerHTML = `
            <tr>
                <td colspan="8">Consultando pagos en MySQL...</td>
            </tr>
        `;

        paymentInfo.textContent = "Cargando pagos...";

        try {
            const response = await fetch("api/admin_pagos.php", {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                },
                cache: "no-store"
            });

            const result = await response.json();

            if (!response.ok || !result.exito) {
                throw new Error(
                    result.mensaje ||
                    "No fue posible consultar los pagos."
                );
            }

            payments = Array.isArray(result.pagos)
                ? result.pagos
                : [];

            renderPayments();

        } catch (error) {
            console.error("Error al cargar pagos:", error);

            paymentBody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-inventory">
                        No fue posible cargar los pagos desde MySQL.
                    </td>
                </tr>
            `;

            paymentInfo.textContent = error.message;
        }
    }

    // ACTUALIZAR ESTADO DEL PAGO

    paymentBody.addEventListener("click", async event => {
        const button = event.target.closest("[data-payment-action]");

        if (!button) return;

        const paymentId = Number(button.dataset.paymentId);
        const newStatus = button.dataset.paymentAction;

        const payment = payments.find(item =>
            Number(item.payment_id) === paymentId
        );

        if (!payment) {
            alert("No se encontró el pago.");
            return;
        }

        const messages = {
            paid: "¿Confirmas que este pago fue recibido?",
            refunded: "¿Confirmas que se realizó el reembolso de este pago?"
        };

        if (!confirm(messages[newStatus])) return;

        button.disabled = true;
        button.textContent = "Actualizando...";

        try {
            const response = await fetch("api/admin_pagos.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    payment_id: paymentId,
                    status: newStatus
                })
            });

            const result = await response.json();

            if (!response.ok || !result.exito) {
                throw new Error(
                    result.mensaje ||
                    "No fue posible actualizar el pago."
                );
            }

            alert(result.mensaje);

            await cargarPagos();

        } catch (error) {
            console.error("Error al actualizar pago:", error);

            alert(error.message);

            await cargarPagos();
        }
    });

    // BÚSQUEDA

    paymentSearch?.addEventListener("input", renderPayments);

    // INICIALIZAR

    cargarPagos();

})();
</script>

</body>
</html>
