<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
    $sql = "
        SELECT
            p.id AS product_id,
            p.code,
            p.model,
            p.category,
            p.description,
            p.image_url,
            v.id AS variant_id,
            v.sku,
            v.size,
            v.color,
            v.price,
            v.stock
        FROM products p
        INNER JOIN product_variants v ON v.product_id = p.id
        WHERE p.is_active = 1 AND v.is_active = 1
        ORDER BY p.id ASC, CAST(v.size AS DECIMAL(8,2)) ASC, v.color ASC
    ";

    $filas = $conexion->query($sql)->fetchAll();
    $productos = [];

    foreach ($filas as $fila) {
        $id = (int)$fila['product_id'];

        if (!isset($productos[$id])) {
            $productos[$id] = [
                'id' => $id,
                'codigo' => $fila['code'],
                'nombre' => $fila['model'],
                'categoria' => mb_strtolower(trim($fila['category'] ?? ''), 'UTF-8'),
                'descripcion' => $fila['description'] ?? '',
                'imagen' => $fila['image_url'] ?? '',
                'variantes' => []
            ];
        }

        $productos[$id]['variantes'][] = [
            'id' => (int)$fila['variant_id'],
            'sku' => $fila['sku'],
            'talla' => $fila['size'],
            'color' => $fila['color'],
            'precio' => (float)$fila['price'],
            'stock' => (int)$fila['stock']
        ];
    }

    echo json_encode(array_values($productos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('API catálogo: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible consultar el catálogo.']);
}
