<?php
//procesar_venta.php
// 🚨 NO USAR ob_start() para esta prueba
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo usuarios logueados y método POST
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// SIMULACIÓN DE DATOS POST (para prueba de diagnóstico)
// Asumimos que el ID 2 es un producto existente (como 'pan de la abuela' de la imagen)
$cart = [
    '2' => [ 
        'cantidad' => 1,
        'precio' => 2000.00,
    ]
];
$total_venta = 2000.00;
$id_usuario = $_SESSION['id_usuario']; // El empleado que realiza la venta
// 🚨 Nota: La línea de $id_usuario se mantiene para que el script dependa de la sesión

// 2. Validación de datos mínimos
if (empty($cart) || $total_venta <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío o el total es inválido.']);
    exit;
}

// 3. INICIO DE LA TRANSACCIÓN (Todo o Nada)
try { 
    $pdo->beginTransaction();

    // --- A. Insertar en la tabla VENTA ---
    $id_cliente_generico = 1; // ID del cliente que ya existe (Andrea Lopez)

    $fecha_venta = date('Y-m-d H:i:s');
    // CONSULTA CORREGIDA: Incluye id_cliente
    $sql_venta = "INSERT INTO venta (fecha_venta, total_venta, id_usuario, id_cliente) VALUES (?, ?, ?, ?)";
    $stmt_venta = $pdo->prepare($sql_venta);

    // EXECUTE CORREGIDO: Pasa la variable id_cliente_generico
    $stmt_venta->execute([$fecha_venta, $total_venta, $id_usuario, $id_cliente_generico]); 

    // Obtener el ID de la venta recién creada
    $id_venta = $pdo->lastInsertId();

    // --- B. Insertar en la tabla DETALLE_VENTA y Actualizar STOCK ---
    foreach ($cart as $id_producto => $item) {
        $cantidad = $item['cantidad'];
        $precio_unitario = $item['precio'];
        $subtotal = $cantidad * $precio_unitario;

        // 1. Insertar Detalle de Venta
        $sql_detalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        $stmt_detalle->execute([$id_venta, $id_producto, $cantidad, $precio_unitario, $subtotal]);

        // 2. Actualizar Stock del Producto (con backticks)
        $sql_stock = "UPDATE producto SET `stock` = `stock` - ? WHERE id_producto = ?";
        $stmt_stock = $pdo->prepare($sql_stock);
        $stmt_stock->execute([$cantidad, $id_producto]);

        // Opcional: Verificar que se actualizó 1 fila
        if ($stmt_stock->rowCount() === 0) {
            throw new Exception("Error: No se pudo actualizar el stock del Producto ID {$id_producto}.");
        }
    }

    // Si todo lo anterior se ejecuta sin errores, confirmamos la transacción
    $pdo->commit();

    // 4. Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => "Venta registrada con éxito. ID de Venta: {$id_venta}",
        'id_venta' => $id_venta
    ]);

} catch (Exception $e) { 
    // Si algo falla, revertimos todos los cambios
    $pdo->rollBack();

    // 5. Respuesta de error
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar la venta. La transacción fue revertida.', 'detail' => $e->getMessage()]);
}
?>