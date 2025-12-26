<?php
// actualizar_proveedor.php - Lógica para actualizar los datos de un proveedor existente

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
    // 1. Recibir y Sanear Datos
    $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // 2. Validación de Datos
    if ($id_proveedor <= 0 || empty($nombre) || empty($telefono) || empty($direccion)) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos o ID de proveedor inválido.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        echo json_encode(['success' => false, 'error' => 'El formato del correo electrónico no es válido.']);
        exit;
    }

    // 3. Actualización en la Base de Datos
    $sql = "UPDATE proveedor SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion WHERE id_proveedor = :id_proveedor";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // 4. Respuesta de Éxito
    echo json_encode([
        'success' => true, 
        'message' => 'Proveedor actualizado correctamente.',
        // Devolver los datos actualizados para refrescar la fila sin recargar
        'data' => [
            'id_proveedor' => $id_proveedor,
            'nombre' => $nombre,
            'telefono' => $telefono,
            'email' => $email,
            'direccion' => $direccion,
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error al actualizar proveedor: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el proveedor.']);
}

?>