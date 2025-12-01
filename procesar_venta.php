<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'conexion.php';

// Validar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida.']);
    exit;
}

// Leer datos JSON del fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cart']) || !isset($data['total'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos o incompletos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener la fecha y hora actual en PHP (Formato MySQL)
    // Esto soluciona el error 1364 (Field 'fecha' doesn't have a default value)
    $fecha_actual = date('Y-m-d H:i:s'); 

    // 2. Insertar venta, pasando $fecha_actual como parámetro seguro
    $stmt = $pdo->prepare("INSERT INTO venta (id_usuario, total, fecha) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['id_usuario'], $data['total'], $fecha_actual]);
    $idVenta = $pdo->lastInsertId();

    // Insertar detalles y actualizar stock
    $stmtDetalle = $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");

    foreach ($data['cart'] as $id => $item) {
        $stmtDetalle->execute([$idVenta, $id, $item['cantidad'], $item['precio']]);
        $stmtStock->execute([$item['cantidad'], $id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_venta' => $idVenta]);

} catch (PDOException $e) {
    $pdo->rollBack();
    // Capturamos el error específico y lo devolvemos
    error_log("Error al procesar la venta: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Exception $e) {
    $pdo->rollBack();
    error_log("Error general al procesar la venta: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
?>