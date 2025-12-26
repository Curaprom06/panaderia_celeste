<?php
// guardar_proveedor.php - Lógica para guardar un nuevo proveedor en la base de datos

// Configuración de respuesta en JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos y la verificación de sesión
session_start();
require_once 'conexion.php'; // **Asegúrate de que este archivo exista y funcione**

// Verificar si el usuario está logueado y si el método es POST
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método de solicitud no permitido.']);
    exit;
}

try {
    // 1. Recibir y Sanear Datos
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // 2. Validación de Datos (básica)
    if (empty($nombre) || empty($telefono) || empty($direccion)) {
        echo json_encode(['success' => false, 'error' => 'Los campos Nombre, Teléfono y Dirección son obligatorios.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        echo json_encode(['success' => false, 'error' => 'El formato del correo electrónico no es válido.']);
        exit;
    }

    // 3. Inserción en la Base de Datos con PDO (Prepared Statement)
    // Esto previene inyección SQL
    $sql = "INSERT INTO proveedor (nombre, telefono, email, direccion) VALUES (:nombre, :telefono, :email, :direccion)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':direccion', $direccion);
    
    $stmt->execute();
    
    // Obtener el ID del proveedor recién insertado
    $id_proveedor = $pdo->lastInsertId();

    // 4. Respuesta de Éxito
    echo json_encode([
        'success' => true, 
        'message' => 'Proveedor registrado correctamente.',
        // Devolver los datos para actualizar la tabla sin recargar
        'data' => [
            'id_proveedor' => $id_proveedor,
            'nombre' => $nombre,
            'telefono' => $telefono,
            'email' => $email,
            'direccion' => $direccion,
        ]
    ]);

} catch (PDOException $e) {
    // 5. Manejo de Errores de Base de Datos
    // Se registra el error en el log, pero se envía un mensaje genérico al cliente.
    error_log("Error al guardar proveedor: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al registrar el proveedor. Intente de nuevo.']);
}

?>