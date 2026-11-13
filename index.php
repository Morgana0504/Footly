
<?php
require_once "auth.php";

// La página principal es pública.
// No se utiliza verificarRol() aquí.

$sesionIniciada = isset($_SESSION["user_id"]);
$rol = $_SESSION["rol"] ?? null;

// Definir destinos según el tipo de usuario
if ($sesionIniciada && $rol === "admin") {
    $enlaceCatalogo = "admin_pedidos_conectados.php";
    $enlaceCuenta = "admin_pedidos_conectados.php";
    $textoAcceso = "Panel administrativo ↗";
} elseif ($sesionIniciada && $rol === "customer") {
    $enlaceCatalogo = "catalogo.php";
    $enlaceCuenta = "pedido.php";
    $textoAcceso = "Mi cuenta ↗";
} else {
    $enlaceCatalogo = "login.php";
    $enlaceCuenta = "login.php";
    $textoAcceso = "Iniciar sesión ↗";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIKE | Catálogo Digital</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="CSS/general.css">
    <link rel="stylesheet" href="CSS/index.css">
    <link rel="stylesheet" href="CSS/responsive.css">
</head>

<body>

<header class="navbar">

    <a href="index.php" class="logo">Footly<span>.</span></a>

    <nav>
        <a href="index.php" class="active">Inicio</a>
        <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>">Catálogo</a>
        <a href="#destacados">Destacados</a>
    </nav>

    <a href="<?= htmlspecialchars($enlaceCuenta, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-dark">
        <?= htmlspecialchars($textoAcceso, ENT_QUOTES, 'UTF-8') ?>
    </a>

</header>

<main>

    <section class="hero">

        <div class="hero-content">

            <span class="eyebrow">CATÁLOGO DIGITAL · CALZADO</span>

            <h1>
                MUÉVETE.<br>
                DESTACA.<br>
                <span>SUPERA.</span>
            </h1>

            <p>
                Descubre los modelos disponibles, consulta tallas,
                colores y precios en un solo lugar.
            </p>

            <div class="hero-actions">

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-lime">
                    Explorar catálogo ↗
                </a>

                <?php if (!$sesionIniciada): ?>

                    <a href="login.php" class="btn btn-outline">
                        Acceso a clientes
                    </a>

                <?php elseif ($rol === "customer"): ?>

                    <a href="pedido.php" class="btn btn-outline">
                        Mis pedidos
                    </a>

                <?php else: ?>

                    <a href="admin_pedidos_conectados.php" class="btn btn-outline">
                        Ir al panel
                    </a>

                <?php endif; ?>

            </div>

            <div class="hero-note">
                <span class="status-dot"></span>
                Consulta los modelos de nuestra colección
            </div>

        </div>

        <div class="hero-image">

            <img
                src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1100&q=85"
                alt="Calzado deportivo rojo">

            <div class="image-label">
                <span>ESTILO QUE SE MUEVE</span>
                <strong>Encuentra tu próximo modelo.</strong>
            </div>

        </div>

        <div class="hero-number">01 / COLECCIÓN</div>

    </section>


    <section class="benefits section">

        <div class="benefit">
            <span class="benefit-icon">01</span>
            <div>
                <h3>Catálogo digital</h3>
                <p>Consulta modelos desde cualquier dispositivo.</p>
            </div>
        </div>

        <div class="benefit">
            <span class="benefit-icon">02</span>
            <div>
                <h3>Información clara</h3>
                <p>Revisa tallas, colores y precios.</p>
            </div>
        </div>

        <div class="benefit">
            <span class="benefit-icon">03</span>
            <div>
                <h3>Pedidos organizados</h3>
                <p>Facilita la captura de tus solicitudes.</p>
            </div>
        </div>

    </section>


    <section class="section" id="destacados">

        <div class="section-heading">

            <div>
                <span class="eyebrow">SELECCIÓN DE LA TEMPORADA</span>
                <h2>Modelos destacados<span>.</span></h2>
            </div>

            <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="text-link">
                Ver todo el catálogo ↗
            </a>

        </div>


        <div class="product-grid">

            <article class="product-card">

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-image">

                    <span class="product-tag">DESTACADO</span>

                    <img
                        src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=80"
                        alt="Tenis deportivos rojos">

                </a>

                <div class="product-info">
                    <div>
                        <span class="product-category">Deportivo</span>
                        <h3>Modelo Air Red</h3>
                    </div>
                    <strong>$1,899.00</strong>
                </div>

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-link">
                    Consultar producto ↗
                </a>

            </article>


            <article class="product-card">

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-image">

                    <img
                        src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=80&sat=-100"
                        alt="Calzado deportivo en tonos neutros">

                </a>

                <div class="product-info">
                    <div>
                        <span class="product-category">Urbano</span>
                        <h3>Modelo Street</h3>
                    </div>
                    <strong>$1,699.00</strong>
                </div>

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-link">
                    Consultar producto ↗
                </a>

            </article>


            <article class="product-card">

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-image">

                    <img
                        src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=700&q=80"
                        alt="Tenis deportivos blancos">

                </a>

                <div class="product-info">
                    <div>
                        <span class="product-category">Casual</span>
                        <h3>Modelo Classic</h3>
                    </div>
                    <strong>$1,499.00</strong>
                </div>

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="product-link">
                    Consultar producto ↗
                </a>

            </article>

        </div>

    </section>


    <section class="section best-sellers-section" id="mas-vendidos">

        <div class="section-heading">

            <div>
                <span class="eyebrow">RESULTADOS DE VENTA</span>
                <h2>Los más vendidos<span>.</span></h2>

                <p class="best-sellers-intro">
                    Ranking actualizado con los pedidos que el administrador ha confirmado.
                </p>
            </div>

            <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="text-link">
                Explorar catálogo ↗
            </a>

        </div>

        <div class="product-grid" id="bestSellersGrid" aria-live="polite">

            <p class="best-sellers-empty">
                Cargando productos más vendidos...
            </p>

        </div>

    </section>


    <section class="cta-banner">

        <div>
            <span class="eyebrow">¿LISTO PARA COMENZAR?</span>
            <h2>Todo tu catálogo.<br>En un solo lugar.</h2>
        </div>

        <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-lime">
            Ver productos ↗
        </a>

    </section>

</main>


<footer class="footer">

    <a href="index.php" class="logo">Footly<span>.</span></a>

    <p>Catálogo digital de calzado · Proyecto de distribuidora</p>

    <span>© 2026 · Todos los derechos reservados</span>

</footer>


<style>

.best-sellers-intro {
    color: #777;
    margin-top: 10px;
    max-width: 560px;
}

.best-sellers-empty {
    grid-column: 1 / -1;
    padding: 28px 22px;
    border: 1px dashed #c9c9c9;
    border-radius: 14px;
    color: #666;
    text-align: center;
}

.best-seller-visual {
    min-height: 190px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0ec;
    position: relative;
}

.best-seller-symbol {
    font-size: 64px;
    line-height: 1;
    color: #222;
}

.best-seller-rank {
    position: absolute;
    top: 14px;
    left: 14px;
    background: #d8ff42;
    color: #111;
    padding: 7px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
}

.best-seller-sales {
    font-size: 12px;
    color: #777;
    margin-top: 7px;
}

.best-seller-card .product-link {
    display: inline-block;
    margin-top: 12px;
}

</style>


<script>

(() => {

    'use strict';

    const ORDERS_KEY = 'footly_orders_demo_v1';
    const INVENTORY_KEY = 'footly_inventory_demo_v1';

    const grid = document.getElementById('bestSellersGrid');

    if (!grid) return;

    const read = (key, fallback) => {

        try {

            const value = JSON.parse(localStorage.getItem(key));

            return value == null ? fallback : value;

        } catch (error) {

            return fallback;

        }

    };


    const escapeHTML = value => String(value ?? '').replace(/[&<>"']/g, char => ({

        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'

    })[char]);


    const money = value => Number(value || 0).toLocaleString('es-MX', {

        style: 'currency',
        currency: 'MXN'

    });


    function renderBestSellers() {

        const orders = read(ORDERS_KEY, []);
        const inventory = read(INVENTORY_KEY, []);

        const confirmed = Array.isArray(orders)

            ? orders.filter(order =>
                String(order.status || '').toLowerCase() === 'confirmado'
            )

            : [];


        const ranking = new Map();


        confirmed.forEach(order => {

            const product = order.product || {};

            const code = String(
                product.code ||
                product.model ||
                'producto-sin-codigo'
            );

            const quantity = Math.max(
                0,
                Number(product.quantity) || 0
            );

            if (!quantity) return;


            const current = ranking.get(code) || {

                code,

                model: product.model || 'Modelo Footly',

                brand: product.brand || '',

                category: product.category || 'Calzado',

                price: Number(product.unitPrice) || 0,

                sold: 0

            };


            current.sold += quantity;

            if (!current.price && Number(product.unitPrice)) {

                current.price = Number(product.unitPrice);

            }

            ranking.set(code, current);

        });


        const products = Array.isArray(inventory) ? inventory : [];


        const sorted = [...ranking.values()]

            .map(item => {

                const stockItem = products.find(
                    product => String(product.code) === item.code
                );

                return {

                    ...item,

                    model: stockItem?.model || item.model,

                    brand: stockItem?.brand || item.brand,

                    category: stockItem?.category || item.category,

                    price: Number(stockItem?.price) || item.price

                };

            })

            .sort((a, b) =>
                b.sold - a.sold ||
                a.model.localeCompare(b.model)
            )

            .slice(0, 3);


        if (!sorted.length) {

            grid.innerHTML = `
                <p class="best-sellers-empty">
                    Todavía no hay ventas confirmadas.
                    Cuando el administrador confirme los primeros pedidos,
                    aquí aparecerán automáticamente los modelos más vendidos.
                </p>
            `;

            return;

        }


        grid.innerHTML = sorted.map((product, index) => `

            <article class="product-card best-seller-card">

                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>"
                   class="product-image best-seller-visual"
                   aria-label="Consultar ${escapeHTML(product.model)}">

                    <span class="best-seller-rank">
                        TOP ${index + 1}
                    </span>

                    <span class="best-seller-symbol" aria-hidden="true">
                        ◈
                    </span>

                </a>


                <div class="product-info">

                    <div>

                        <span class="product-category">
                            ${escapeHTML(product.category)}
                        </span>

                        <h3>
                            ${escapeHTML(product.model)}
                        </h3>

                        <p class="best-seller-sales">
                            ${product.sold} par(es) vendidos
                        </p>

                    </div>

                    <strong>
                        ${money(product.price)}
                    </strong>

                </div>


                <a href="<?= htmlspecialchars($enlaceCatalogo, ENT_QUOTES, 'UTF-8') ?>"
                   class="product-link">
                    Consultar producto ↗
                </a>

            </article>

        `).join('');

    }


    window.addEventListener('storage', event => {

        if (
            event.key === ORDERS_KEY ||
            event.key === INVENTORY_KEY
        ) {

            renderBestSellers();

        }

    });


    renderBestSellers();

})();

</script>

</body>
</html>
