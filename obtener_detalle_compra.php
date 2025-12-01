<?php
// obtener_detalle_compra.php - Devuelve los datos de una compra específica y su detalle en formato JSON

header('Content-Type: application/json');

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// 2. Obtener y validar ID de Compra
$id_compra = (int)($_GET['id'] ?? 0);

if ($id_compra <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de compra inválido.']);
    exit;
}

try {
    // Iniciar Transacción para asegurar la consistencia (aunque solo es lectura)
    $pdo->beginTransaction();

    // 3. Consultar los datos principales de la compra
    $sql_compra = "
        SELECT 
            c.id_compra, c.fecha, c.total, c.nota_compra,
            p.nombre AS nombre_proveedor,
            u.nombre AS nombre_usuario, u.apellido AS apellido_usuario
        FROM compra c
        JOIN proveedor p ON c.id_proveedor = p.id_proveedor
        JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE c.id_compra = :id_compra
    ";
    $stmt_compra = $pdo->prepare($sql_compra);
    $stmt_compra->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
    $stmt_compra->execute();
    $compra = $stmt_compra->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Compra no encontrada.']);
        exit;
    }

    // 4. Consultar el detalle de la compra
    $sql_detalle = "
        SELECT 
            dc.cantidad, dc.precio_unitario, dc.subtotal,
            pr.nombre AS nombre_producto, pr.unidad_medida
        FROM detalle_compra dc
        JOIN producto pr ON dc.id_producto = pr.id_producto
        WHERE dc.id_compra = :id_compra
    ";
    $stmt_detalle = $pdo->prepare($sql_detalle);
    $stmt_detalle->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
    $stmt_detalle->execute();
    $detalle = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    // 5. Devolver datos de éxito
    echo json_encode([
        'success' => true, 
        'compra' => $compra,
        'detalle' => $detalle
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al obtener detalle de compra: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos al cargar el detalle.']);
}

?>