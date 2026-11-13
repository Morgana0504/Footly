<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

try {
    $sql = "
        SELECT
            v.id AS id,
            p.id AS product_id,
            p.code AS code,
            p.model AS model,
            p.brand AS brand,
            p.category AS category,
            v.sku AS sku,
            v.size AS size,
            v.color AS color,
            v.price AS price,
            v.stock AS stock,
            v.min_stock AS minimum
        FROM product_variants v
        INNER JOIN products p
            ON v.product_id = p.id
        WHERE p.is_active = 1
          AND v.is_active = 1
        ORDER BY p.id ASC, v.size ASC
    ";

    $consulta = $conexion->query($sql);
    $inventario = $consulta->fetchAll();

    foreach ($inventario as &$producto) {
        $producto['id'] = (int) $producto['id'];
        $producto['product_id'] = (int) $producto['product_id'];
        $producto['size'] = (float) $producto['size'];
        $producto['price'] = (float) $producto['price'];
        $producto['stock'] = (int) $producto['stock'];
        $producto['minimum'] = (int) $producto['minimum'];
    }

    unset($producto);

    echo json_encode(
        $inventario,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'No fue posible consultar el inventario.'
    ]);
}
