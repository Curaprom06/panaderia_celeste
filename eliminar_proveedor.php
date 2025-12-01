<?php
// eliminar_proveedor.php - Lógica para eliminar un proveedor de la base de datos

header('Content-Type: application/json');

session_start();
require_once 'conexion.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

try {
    // 1. Recibir y validar ID
    $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);

    if ($id_proveedor <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de proveedor inválido.']);
        exit;
    }

    // 2. Eliminación en la Base de Datos
    $sql = "DELETE FROM proveedor WHERE id_proveedor = :id_proveedor";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
    $stmt->execute();

    // 3. Respuesta de Éxito
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Proveedor eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'El proveedor no existe o ya fue eliminado.']);
    }

} catch (PDOException $e) {
    error_log("Error al eliminar proveedor: " . $e->getMessage());
    // Considerar verificar si el error es una restricción de clave foránea.
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el proveedor. (Podría estar asociado a productos o compras).']);
}

?>