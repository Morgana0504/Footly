<?php
require_once "auth.php";
verificarRol("customer");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Consulta el catálogo de calzado Footly, busca modelos y filtra por categoría, talla y disponibilidad.">

    <title>Catálogo de calzado | Footly</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="CSS/general.css">
    <link rel="stylesheet" href="CSS/catalogo.css">
    <link rel="stylesheet" href="CSS/responsive.css">

    <style>
        /* ================= FILTROS Y CATÁLOGO ================= */

        .filter-hint {
            margin-top: 12px;
            font-size: 10px;
            line-height: 1.6;
            color: var(--gray);
        }

        .filter-actions {
            display: grid;
            gap: 9px;
            margin-top: 8px;
        }

        .catalog-count {
            font-size: 11px;
            margin-top: 7px;
            color: var(--gray);
        }

        .product-stock {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin: 12px 0;
            font-size: 10px;
            color: var(--gray);
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .stock-status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .stock-ok {
            color: #287345;
            background: #e9f5ec;
        }

        .stock-low {
            color: #9a6900;
            background: #fff4d8;
        }

        .stock-out {
            color: #b23b35;
            background: #fbe9e8;
        }

        .product-sizes {
            margin: 7px 0 0;
            font-size: 10px;
            line-height: 1.7;
            color: var(--gray);
        }

        .product-sizes strong {
            color: var(--dark);
        }

        .empty-results {
            grid-column: 1 / -1;
            padding: 45px 20px;
            text-align: center;
            background: var(--white);
            border: 1px dashed #cfd3c7;
            border-radius: 16px;
        }

        .empty-results h3 {
            margin-bottom: 8px;
            font-size: 17px;
        }

        .empty-results p {
            color: var(--gray);
            font-size: 12px;
        }

        .filters .check-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 11px;
        }

        .filters input[type="checkbox"] {
            accent-color: #91b92f;
        }

        .filters input[type="search"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            background: #fff;
            font-size: 11px;
        }

        .size-options label {
            display: block;
        }

        .size-options input:focus-visible + span {
            outline: 3px solid #7b9b36;
            outline-offset: 2px;
        }

        .sort-label select {
            border: 1px solid var(--gray-light);
            background: #fff;
        }

        .add-cart:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        /* ================= VENTANA EMERGENTE ================= */

        .variant-modal {
            position: fixed;
            inset: 0;
            margin: auto;

            width: min(92%, 440px);
            max-width: 440px;
            max-height: 90vh;

            padding: 0;
            border: none;
            border-radius: 18px;

            background: #fff;
            color: var(--dark);

            box-shadow: 0 20px 70px rgba(0, 0, 0, .25);

            overflow-y: auto;
        }

        .variant-modal::backdrop {
            background: rgba(23, 25, 22, .65);
            backdrop-filter: blur(3px);
        }

        .variant-modal-content {
            position: relative;
            padding: 32px;
        }

        .variant-modal-content h2 {
            margin: 12px 0 5px;
            font-size: 25px;
            line-height: 1.2;
        }

        .variant-modal-content > .muted {
            margin-bottom: 22px;
            font-size: 11px;
        }

        .variant-modal-close {
            position: absolute;
            top: 12px;
            right: 17px;
            border: none;
            background: transparent;
            color: var(--dark);
            font-size: 28px;
            cursor: pointer;
        }

        .modal-field {
            display: grid;
            gap: 7px;
            margin: 16px 0;
        }

        .modal-field label {
            font-size: 11px;
            font-weight: 700;
        }

        .modal-field select,
        .modal-field input {
            width: 100%;
            min-width: 0;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            background: #fff;
            color: var(--dark);
            font: inherit;
            font-size: 12px;
        }

        .modal-field small {
            color: var(--gray);
            font-size: 10px;
        }

        .modal-variant-info {
            padding: 12px;
            border-radius: 8px;
            background: #f1f3eb;
            color: #52652b;
            font-size: 11px;
            line-height: 1.7;
        }

        .modal-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding-top: 15px;
            border-top: 1px solid var(--gray-light);
            font-size: 12px;
        }

        .modal-total strong {
            font-size: 19px;
        }

        .variant-modal button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        @media (max-width: 760px) {
            .product-stock {
                align-items: flex-start;
                flex-direction: column;
            }

            .filters {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .variant-modal-content {
                padding: 26px 20px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">

    <a href="index.html" class="logo" aria-label="Footly, ir al inicio">
        Footly<span>.</span>
    </a>

    <nav aria-label="Navegación principal">
        <a href="index.php">Inicio</a>
        <a href="catalogo.php" class="active" aria-current="page">Catálogo</a>
        <a href="pedido.php">Pedidos</a>
    </nav>

    <div class="account-actions">
        <span>
            Hola, <?= htmlspecialchars($_SESSION["nombre"]) ?>
        </span>

        <a href="logout.php" class="btn btn-dark">
            Cerrar sesión ↗
        </a>
    </div>

</header>

<main>

    <section class="catalog-hero">
        <span class="eyebrow">EXPLORA NUESTRA COLECCIÓN</span>

        <h1>
            Encuentra tu<br>
            <span>próximo modelo.</span>
        </h1>

        <p>
            Consulta nuestros modelos, revisa las tallas disponibles
            y encuentra el calzado que buscas.
        </p>
    </section>

    <section class="section catalog-layout">

        <!-- ================= FILTROS ================= -->

        <aside class="filters" aria-labelledby="filters-title">

            <h3 id="filters-title">Filtrar productos</h3>

            <form id="filtersForm">

                <label for="buscar">Buscar modelo o código</label>

                <input
                    type="search"
                    id="buscar"
                    name="buscar"
                    placeholder="Ej. Air, Street, NR-001"
                    autocomplete="off"
                >

                <div class="filter-group">

                    <h4>Categoría</h4>

                    <label class="check-label">
                        <input type="checkbox" name="categoria" value="deportivo">
                        Deportivo
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="categoria" value="casual">
                        Casual
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="categoria" value="urbano">
                        Urbano
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="categoria" value="running">
                        Running
                    </label>

                </div>

                <div class="filter-group">

                    <h4>Talla</h4>

                    <div class="size-options">

                        <label>
                            <input type="checkbox" name="talla" value="23">
                            <span>23</span>
                        </label>

                        <label>
                            <input type="checkbox" name="talla" value="24">
                            <span>24</span>
                        </label>

                        <label>
                            <input type="checkbox" name="talla" value="25">
                            <span>25</span>
                        </label>

                        <label>
                            <input type="checkbox" name="talla" value="26">
                            <span>26</span>
                        </label>

                        <label>
                            <input type="checkbox" name="talla" value="27">
                            <span>27</span>
                        </label>

                        <label>
                            <input type="checkbox" name="talla" value="28">
                            <span>28</span>
                        </label>

                    </div>

                    <p class="filter-hint">
                        Solo aparecerán modelos que tengan existencia
                        de la talla seleccionada.
                    </p>

                </div>

                <div class="filter-group">

                    <h4>Disponibilidad</h4>

                    <label class="check-label">
                        <input type="checkbox" name="disponibilidad" value="disponible">
                        Disponible
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="disponibilidad" value="bajo">
                        Existencias limitadas
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="disponibilidad" value="agotado">
                        Agotado
                    </label>

                </div>

                <div class="filter-actions">
                    <button type="reset" class="btn btn-outline btn-full">
                        Limpiar filtros
                    </button>
                </div>

            </form>

            <p class="filter-hint">
                Los precios, tallas y existencias se consultan
                directamente desde el inventario de Footly.
            </p>

        </aside>

        <!-- ================= PRODUCTOS ================= -->

        <div class="catalog-content">

            <div class="catalog-toolbar">

                <div>
                    <h2>Todos los modelos<span>.</span></h2>

                    <p class="muted">
                        Explora los modelos disponibles en Footly.
                    </p>

                    <p class="catalog-count" id="resultCount" aria-live="polite">
                        Cargando productos...
                    </p>
                </div>

                <label class="sort-label" for="orden">

                    Ordenar por

                    <select id="orden" name="orden">
                        <option value="recomendados">Recomendados</option>
                        <option value="precio-asc">Precio: menor a mayor</option>
                        <option value="precio-desc">Precio: mayor a menor</option>
                        <option value="nombre">Nombre del modelo</option>
                    </select>

                </label>

            </div>

            <div class="product-grid" id="productGrid" aria-live="polite">
                <!-- Los productos se generan desde MySQL -->
            </div>

            <p class="small-text muted" style="margin-top:22px;">
                La disponibilidad puede cambiar conforme se actualicen
                las existencias del inventario.
            </p>

        </div>

    </section>

</main>

<!-- ================= VENTANA DE SELECCIÓN ================= -->

<dialog id="variantModal" class="variant-modal">

    <div class="variant-modal-content">

        <button
            type="button"
            class="variant-modal-close"
            id="closeVariantModal"
            aria-label="Cerrar"
        >
            ×
        </button>

        <span class="eyebrow">PERSONALIZA TU PEDIDO</span>

        <h2 id="modalProductName">
            Selecciona tu producto
        </h2>

        <p id="modalProductCode" class="muted"></p>

        <div class="modal-field">

            <label for="modalSize">Talla</label>

            <select id="modalSize">
                <option value="">Selecciona una talla</option>
            </select>

        </div>

        <div class="modal-field">

            <label for="modalColor">Color</label>

            <select id="modalColor" disabled>
                <option value="">Primero selecciona una talla</option>
            </select>

        </div>

        <div id="modalVariantInfo" class="modal-variant-info">
            Selecciona una talla y un color para consultar las existencias.
        </div>

        <div class="modal-field">

            <label for="modalQuantity">Cantidad de pares</label>

            <input
                type="number"
                id="modalQuantity"
                min="1"
                value="1"
                disabled
            >

            <small id="modalStockHelp">
                La cantidad dependerá de las existencias disponibles.
            </small>

        </div>

        <div class="modal-total">
            <span>Subtotal</span>
            <strong id="modalSubtotal">$0.00</strong>
        </div>

        <button
            type="button"
            id="confirmAddCart"
            class="btn btn-dark btn-full"
            disabled
        >
            Agregar al pedido ↗
        </button>

    </div>

</dialog>

<!-- ================= FOOTER ================= -->

<footer class="footer">

    <a href="index.html" class="logo">
        Footly<span>.</span>
    </a>

    <p>Catálogo digital de calzado</p>

    <span>© 2026 Footly</span>

</footer>

<script>
"use strict";

/* ================= CONFIGURACIÓN ================= */

const API_CATALOGO = "api/catalogo.php";
const CLAVE_CARRITO = "footly_cart_v1";

let productos = [];
let productoActual = null;
let varianteActual = null;

const formulario = document.getElementById("filtersForm");
const buscador = document.getElementById("buscar");
const selectorOrden = document.getElementById("orden");
const contenedor = document.getElementById("productGrid");
const contador = document.getElementById("resultCount");

const formatoPrecio = new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN"
});

/* ================= ELEMENTOS DE LA VENTANA ================= */

const variantModal = document.getElementById("variantModal");

const modalProductName = document.getElementById("modalProductName");
const modalProductCode = document.getElementById("modalProductCode");

const modalSize = document.getElementById("modalSize");
const modalColor = document.getElementById("modalColor");
const modalQuantity = document.getElementById("modalQuantity");

const modalStockHelp = document.getElementById("modalStockHelp");
const modalVariantInfo = document.getElementById("modalVariantInfo");
const modalSubtotal = document.getElementById("modalSubtotal");

const confirmAddCart = document.getElementById("confirmAddCart");

/* ================= FUNCIONES GENERALES ================= */

function stockTotal(producto) {
    return producto.variantes.reduce(
        (total, variante) => total + Number(variante.stock || 0),
        0
    );
}

function estadoProducto(producto) {
    const total = stockTotal(producto);

    if (total === 0) return "agotado";
    if (total <= 5) return "bajo";

    return "disponible";
}

function textoEstado(estado) {
    return estado === "disponible"
        ? "Disponible"
        : estado === "bajo"
            ? "Existencias limitadas"
            : "Agotado";
}

function leerSeleccionados(nombre) {
    return Array.from(
        formulario.querySelectorAll(`input[name="${nombre}"]:checked`)
    ).map(el => el.value);
}

function escapar(valor) {
    return String(valor ?? "").replace(
        /[&<>"']/g,
        caracter => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[caracter])
    );
}

/* ================= CREAR TARJETAS ================= */

function crearTarjeta(producto) {

    const total = stockTotal(producto);
    const estado = estadoProducto(producto);

    const tallas = [
        ...new Set(
            producto.variantes.map(v => String(v.talla))
        )
    ];

    const colores = [
        ...new Set(
            producto.variantes.map(v => String(v.color))
        )
    ];

    const precios = producto.variantes.map(
        v => Number(v.precio)
    );

    const precioMin = precios.length ? Math.min(...precios) : 0;
    const precioMax = precios.length ? Math.max(...precios) : 0;

    const precioTexto = precioMin === precioMax
        ? formatoPrecio.format(precioMin)
        : `${formatoPrecio.format(precioMin)} – ${formatoPrecio.format(precioMax)}`;

    const imagen = producto.imagen ||
        "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=80";

    const disponible = producto.variantes.some(
        v => Number(v.stock) > 0
    );

    const boton = disponible
        ? `
            <button
                type="button"
                class="btn btn-dark btn-full add-cart"
                data-product-id="${Number(producto.id)}"
            >
                Agregar al pedido ↗
            </button>
        `
        : `
            <button type="button" class="btn btn-outline btn-full" disabled>
                Producto agotado
            </button>
        `;

    return `
        <article class="product-card" data-code="${escapar(producto.codigo)}">

            <div class="product-image">

                <img
                    src="${escapar(imagen)}"
                    alt="Calzado ${escapar(producto.nombre)}"
                    loading="lazy"
                    onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=80'"
                >

            </div>

            <div class="product-info">

                <div>
                    <span class="product-category">
                        ${escapar(producto.categoria || "Calzado")}
                    </span>

                    <h3>${escapar(producto.nombre)}</h3>
                </div>

                <strong>${precioTexto}</strong>

            </div>

            <p class="product-description">
                ${escapar(producto.descripcion || "Consulta las variantes disponibles de este modelo.")}
            </p>

            <div class="product-stock">

                <span class="stock-status stock-${
                    estado === "disponible"
                        ? "ok"
                        : estado === "bajo"
                            ? "low"
                            : "out"
                }">
                    ${textoEstado(estado)}
                </span>

                <strong>
                    ${total} ${total === 1 ? "par" : "pares"}
                </strong>

            </div>

            <p class="product-sizes">
                <strong>Colores:</strong>
                ${colores.map(escapar).join(" · ")}
            </p>

            <p class="product-sizes">
                <strong>Tallas:</strong>
                ${tallas.map(escapar).join(" · ")}
            </p>

            <p class="product-sizes">
                <strong>Código:</strong>
                ${escapar(producto.codigo)}
            </p>

            ${boton}

        </article>
    `;
}

/* ================= FILTROS ================= */

function aplicarFiltros() {

    const texto = buscador.value
        .trim()
        .toLocaleLowerCase("es-MX");

    const categorias = leerSeleccionados("categoria");
    const tallas = leerSeleccionados("talla");
    const disponibilidades = leerSeleccionados("disponibilidad");

    let resultado = productos.filter(producto => {

        const coincideTexto =
            !texto ||
            `${producto.nombre} ${producto.codigo} ${producto.descripcion}`
                .toLocaleLowerCase("es-MX")
                .includes(texto);

        const coincideCategoria =
            !categorias.length ||
            categorias.includes(producto.categoria);

        const coincideTalla =
            !tallas.length ||
            producto.variantes.some(variante =>
                tallas.includes(String(variante.talla)) &&
                Number(variante.stock) > 0
            );

        const coincideDisponibilidad =
            !disponibilidades.length ||
            disponibilidades.includes(estadoProducto(producto));

        return (
            coincideTexto &&
            coincideCategoria &&
            coincideTalla &&
            coincideDisponibilidad
        );
    });

    if (selectorOrden.value === "precio-asc") {
        resultado.sort((a, b) =>
            Math.min(...a.variantes.map(v => Number(v.precio))) -
            Math.min(...b.variantes.map(v => Number(v.precio)))
        );
    }

    if (selectorOrden.value === "precio-desc") {
        resultado.sort((a, b) =>
            Math.max(...b.variantes.map(v => Number(v.precio))) -
            Math.max(...a.variantes.map(v => Number(v.precio)))
        );
    }

    if (selectorOrden.value === "nombre") {
        resultado.sort((a, b) =>
            a.nombre.localeCompare(b.nombre, "es")
        );
    }

    contador.textContent =
        `${resultado.length} ${
            resultado.length === 1
                ? "producto encontrado"
                : "productos encontrados"
        }`;

    contenedor.innerHTML = resultado.length
        ? resultado.map(crearTarjeta).join("")
        : `
            <div class="empty-results">
                <h3>No encontramos productos</h3>
                <p>
                    Prueba cambiando los filtros o utiliza otra
                    palabra de búsqueda.
                </p>
            </div>
        `;
}

/* ================= CARGAR CATÁLOGO ================= */

async function cargarCatalogo() {

    contador.textContent = "Consultando inventario...";

    try {

        const respuesta = await fetch(API_CATALOGO, {
            headers: {
                "Accept": "application/json"
            }
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || !Array.isArray(datos)) {
            throw new Error(
                datos.error || "Respuesta no válida del servidor."
            );
        }

        productos = datos.map(producto => ({
            ...producto,
            variantes: Array.isArray(producto.variantes)
                ? producto.variantes
                : []
        }));

        aplicarFiltros();

        if (!productos.length) {
            contenedor.innerHTML = `
                <div class="empty-results">
                    <h3>Aún no hay productos registrados</h3>
                    <p>
                        Cuando se agreguen productos activos al inventario,
                        aparecerán aquí.
                    </p>
                </div>
            `;
        }

    } catch (error) {

        console.error("Error al cargar catálogo:", error);

        contador.textContent =
            "No fue posible cargar los productos.";

        contenedor.innerHTML = `
            <div class="empty-results">
                <h3>No se pudo consultar el catálogo</h3>
                <p>
                    Verifica que Apache y MySQL estén activos y que
                    la base de datos Footly tenga productos registrados.
                </p>
                <p>${escapar(error.message)}</p>
            </div>
        `;
    }
}

/* ================= ABRIR VENTANA ================= */

function abrirSelectorProducto(productId) {

    productoActual = productos.find(
        p => Number(p.id) === Number(productId)
    );

    if (!productoActual) {
        alert("No se encontró el producto seleccionado.");
        return;
    }

    const variantesDisponibles = productoActual.variantes.filter(
        v => Number(v.stock) > 0
    );

    if (!variantesDisponibles.length) {
        alert("Este producto no tiene existencias disponibles.");
        return;
    }

    modalProductName.textContent = productoActual.nombre;

    modalProductCode.textContent =
        `Código: ${productoActual.codigo}`;

    const tallas = [
        ...new Set(
            variantesDisponibles.map(v => String(v.talla))
        )
    ].sort((a, b) => Number(a) - Number(b));

    modalSize.innerHTML =
        '<option value="">Selecciona una talla</option>' +
        tallas.map(talla => `
            <option value="${escapar(talla)}">
                ${escapar(talla)}
            </option>
        `).join("");

    modalColor.innerHTML =
        '<option value="">Primero selecciona una talla</option>';

    modalColor.disabled = true;

    modalQuantity.value = 1;
    modalQuantity.disabled = true;
    modalQuantity.removeAttribute("max");

    varianteActual = null;

    modalVariantInfo.textContent =
        "Selecciona una talla y un color para consultar las existencias.";

    modalStockHelp.textContent =
        "La cantidad dependerá de las existencias disponibles.";

    modalSubtotal.textContent =
        formatoPrecio.format(0);

    confirmAddCart.disabled = true;

    variantModal.showModal();
}

/* ================= SELECCIÓN DE TALLA ================= */

modalSize.addEventListener("change", () => {

    const talla = modalSize.value;

    varianteActual = null;

    modalQuantity.disabled = true;
    modalQuantity.value = 1;

    confirmAddCart.disabled = true;

    modalSubtotal.textContent =
        formatoPrecio.format(0);

    if (!talla || !productoActual) {

        modalColor.innerHTML =
            '<option value="">Primero selecciona una talla</option>';

        modalColor.disabled = true;

        return;
    }

    const variantesTalla = productoActual.variantes.filter(v =>
        String(v.talla) === talla &&
        Number(v.stock) > 0
    );

    const colores = [
        ...new Set(
            variantesTalla.map(v => String(v.color))
        )
    ];

    modalColor.innerHTML =
        '<option value="">Selecciona un color</option>' +
        colores.map(color => `
            <option value="${escapar(color)}">
                ${escapar(color)}
            </option>
        `).join("");

    modalColor.disabled = false;

    modalVariantInfo.textContent =
        `${colores.length} color(es) disponibles para la talla ${talla}.`;
});

/* ================= SELECCIÓN DE COLOR ================= */

modalColor.addEventListener("change", actualizarVarianteSeleccionada);

function actualizarVarianteSeleccionada() {

    if (
        !productoActual ||
        !modalSize.value ||
        !modalColor.value
    ) {
        varianteActual = null;

        modalQuantity.disabled = true;
        confirmAddCart.disabled = true;

        return;
    }

    varianteActual = productoActual.variantes.find(v =>
        String(v.talla) === modalSize.value &&
        String(v.color) === modalColor.value &&
        Number(v.stock) > 0
    );

    if (!varianteActual) {

        modalVariantInfo.textContent =
            "Esta combinación no tiene existencias disponibles.";

        modalQuantity.disabled = true;
        confirmAddCart.disabled = true;

        return;
    }

    const stock = Number(varianteActual.stock);
    const precio = Number(varianteActual.precio);

    modalQuantity.min = 1;
    modalQuantity.max = stock;
    modalQuantity.value = 1;
    modalQuantity.disabled = false;

    modalStockHelp.textContent =
        `Existencias disponibles: ${stock} pares.`;

    modalVariantInfo.innerHTML = `
        Talla: <strong>${escapar(varianteActual.talla)}</strong><br>
        Color: <strong>${escapar(varianteActual.color)}</strong><br>
        Precio por par: <strong>${formatoPrecio.format(precio)}</strong><br>
        Disponibles: <strong>${stock}</strong>
    `;

    actualizarSubtotalModal();

    confirmAddCart.disabled = false;
}

/* ================= CANTIDAD Y SUBTOTAL ================= */

modalQuantity.addEventListener("input", () => {

    if (!varianteActual) return;

    const cantidad = Number(modalQuantity.value);
    const stock = Number(varianteActual.stock);

    if (
        !Number.isInteger(cantidad) ||
        cantidad < 1 ||
        cantidad > stock
    ) {

        confirmAddCart.disabled = true;

        modalStockHelp.textContent =
            `Ingresa una cantidad entre 1 y ${stock} pares.`;

        return;
    }

    confirmAddCart.disabled = false;

    modalStockHelp.textContent =
        `Existencias disponibles: ${stock} pares.`;

    actualizarSubtotalModal();
});

function actualizarSubtotalModal() {

    if (!varianteActual) return;

    const cantidad = Number(modalQuantity.value);

    if (!Number.isInteger(cantidad) || cantidad < 1) return;

    const precio = Number(varianteActual.precio);

    modalSubtotal.textContent =
        formatoPrecio.format(precio * cantidad);
}

/* ================= AGREGAR AL CARRITO ================= */

function confirmarAgregarAlPedido() {

    if (!productoActual || !varianteActual) return;

    const cantidad = Number(modalQuantity.value);
    const stock = Number(varianteActual.stock);

    if (
        !Number.isInteger(cantidad) ||
        cantidad < 1 ||
        cantidad > stock
    ) {

        alert(`La cantidad debe estar entre 1 y ${stock} pares.`);

        return;
    }

    let carrito = [];

    try {

        carrito = JSON.parse(
            localStorage.getItem(CLAVE_CARRITO) || "[]"
        );

        if (!Array.isArray(carrito)) {
            carrito = [];
        }

    } catch (error) {

        alert("No fue posible leer el carrito. Intenta nuevamente.");

        console.error(error);

        return;
    }

    const existente = carrito.find(item =>
        Number(item.variant_id) === Number(varianteActual.id)
    );

    const cantidadActual = existente
        ? Number(existente.quantity)
        : 0;

    if (
        !Number.isInteger(cantidadActual) ||
        cantidadActual < 0
    ) {

        alert("La cantidad registrada en el carrito no es válida.");

        return;
    }

    if (cantidadActual + cantidad > stock) {

        alert(
            `Ya tienes ${cantidadActual} par(es) de esta variante. ` +
            `Solo puedes agregar ${Math.max(0, stock - cantidadActual)} más.`
        );

        return;
    }

    if (existente) {

        existente.quantity = cantidadActual + cantidad;
        existente.stock = stock;

    } else {

        carrito.push({
            variant_id: Number(varianteActual.id),
            product_id: Number(productoActual.id),
            code: productoActual.codigo,
            model: productoActual.nombre,
            size: varianteActual.talla,
            color: varianteActual.color,
            price: Number(varianteActual.precio),
            quantity: cantidad,
            stock: stock
        });

    }

    try {

        localStorage.setItem(
            CLAVE_CARRITO,
            JSON.stringify(carrito)
        );

    } catch (error) {

        alert("No fue posible guardar el producto en el carrito.");

        console.error(error);

        return;
    }

    variantModal.close();

    alert(
        `${cantidad} par(es) de ${productoActual.nombre} ` +
        "se agregaron correctamente al pedido."
    );
}

/* ================= CERRAR VENTANA ================= */

document.getElementById("closeVariantModal").addEventListener(
    "click",
    () => {
        variantModal.close();
    }
);

variantModal.addEventListener("click", e => {

    if (e.target === variantModal) {
        variantModal.close();
    }

});

/* ================= CONFIRMAR SELECCIÓN ================= */

confirmAddCart.addEventListener(
    "click",
    confirmarAgregarAlPedido
);

/* ================= EVENTOS DE FILTROS ================= */

buscador.addEventListener("input", aplicarFiltros);

formulario.addEventListener("change", aplicarFiltros);

formulario.addEventListener("submit", e => {
    e.preventDefault();
});

formulario.addEventListener("reset", () => {

    setTimeout(() => {

        selectorOrden.value = "recomendados";

        aplicarFiltros();

    }, 0);

});

selectorOrden.addEventListener("change", aplicarFiltros);

/* ================= EVENTO DE AGREGAR PRODUCTO ================= */

contenedor.addEventListener("click", e => {

    const boton = e.target.closest(".add-cart");

    if (!boton) return;

    abrirSelectorProducto(boton.dataset.productId);

});

/* ================= INICIAR CATÁLOGO ================= */

cargarCatalogo();

</script>

</body>
</html>