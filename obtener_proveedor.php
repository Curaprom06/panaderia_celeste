<?php
// obtener_proveedor.php - Devuelve los datos de un proveedor específico en formato JSON

header('Content-Type: application/json');

session_start();
require_once 'conexion.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// 1. Obtener y validar ID
$id_proveedor = (int)($_GET['id'] ?? 0);

if ($id_proveedor <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de proveedor inválido.']);
    exit;
}

try {
    // 2. Consultar el proveedor
    $sql = "SELECT id_proveedor, nombre, telefono, email, direccion FROM proveedor WHERE id_proveedor = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id_proveedor, PDO::PARAM_INT);
    $stmt->execute();
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($proveedor) {
        // 3. Devolver datos de éxito
        echo json_encode(['success' => true, 'data' => $proveedor]);
    } else {
        // 4. Devolver error si no se encuentra
        echo json_encode(['success' => false, 'error' => 'Proveedor no encontrado.']);
    }

} catch (PDOException $e) {
    error_log("Error al obtener proveedor: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}

?>