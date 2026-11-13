
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Método no permitido.'
        ]);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);

    if (!is_array($datos)) {
        throw new InvalidArgumentException('Los datos enviados no son válidos.');
    }

    $variantId = filter_var(
        $datos['variant_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $tipo = $datos['movement_type'] ?? '';
    $cantidad = filter_var(
        $datos['quantity'] ?? null,
        FILTER_VALIDATE_INT
    );

    $notas = trim($datos['notes'] ?? '');

    if (!$variantId || $variantId < 1) {
        throw new InvalidArgumentException('La variante no es válida.');
    }

    if (!in_array($tipo, ['in', 'out', 'adjustment'], true)) {
        throw new InvalidArgumentException('El tipo de movimiento no es válido.');
    }

    if ($cantidad === false || $cantidad < 0) {
        throw new InvalidArgumentException('La cantidad debe ser un número entero no negativo.');
    }

    if ($tipo !== 'adjustment' && $cantidad < 1) {
        throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
    }

    if (mb_strlen($notas) > 255) {
        throw new InvalidArgumentException('Las notas no pueden superar 255 caracteres.');
    }

    $conexion->beginTransaction();

    // Bloquear la variante mientras se calcula el nuevo inventario.
    $consulta = $conexion->prepare("
        SELECT stock
        FROM product_variants
        WHERE id = :id AND is_active = 1
        FOR UPDATE
    ");

    $consulta->execute(['id' => $variantId]);
    $variante = $consulta->fetch();

    if (!$variante) {
        throw new RuntimeException('No se encontró la variante seleccionada.');
    }

    $stockActual = (int) $variante['stock'];

    if ($tipo === 'in') {
        $nuevoStock = $stockActual + $cantidad;
        $cantidadMovimiento = $cantidad;
    } elseif ($tipo === 'out') {
        if ($cantidad > $stockActual) {
            throw new RuntimeException('No hay existencias suficientes para realizar esta salida.');
        }

        $nuevoStock = $stockActual - $cantidad;
        $cantidadMovimiento = $cantidad;
    } else {
        // En un ajuste, quantity representa la existencia final deseada.
        $nuevoStock = $cantidad;
        $cantidadMovimiento = $nuevoStock - $stockActual;
    }

    $actualizar = $conexion->prepare("
        UPDATE product_variants
        SET stock = :stock
        WHERE id = :id
    ");

    $actualizar->execute([
        'stock' => $nuevoStock,
        'id' => $variantId
    ]);

    $registrar = $conexion->prepare("
        INSERT INTO inventory_movements
            (variant_id, user_id, order_id, movement_type, quantity, notes)
        VALUES
            (:variant_id, NULL, NULL, :movement_type, :quantity, :notes)
    ");

    $registrar->execute([
        'variant_id' => $variantId,
        'movement_type' => $tipo,
        'quantity' => $cantidadMovimiento,
        'notes' => $notas !== '' ? $notas : null
    ]);

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Movimiento registrado correctamente.',
        'previous_stock' => $stockActual,
        'new_stock' => $nuevoStock
    ]);

} catch (InvalidArgumentException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);

} catch (RuntimeException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'No fue posible registrar el movimiento.'
    ]);
}
