<?php
//  cambiar_estado_producto.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// 2. Obtener datos de la URL (GET)
$id_producto = $_GET['id'] ?? null;
$estado_actual = $_GET['estado'] ?? null; 

// 3. Validar los datos
if (!$id_producto || !in_array($estado_actual, ['Activo', 'Inactivo'])) {
    header('Location: gestion_productos.php?error=Datos de producto incompletos o incorrectos.');
    exit;
}

// 4. Determinar el NUEVO estado
$nuevo_estado = ($estado_actual === 'Activo') ? 'Inactivo' : 'Activo';
$mensaje = "Producto ID {$id_producto} cambiado a {$nuevo_estado}.";

try {
    // 5. Preparar y ejecutar la consulta de actualización (Asumimos el campo 'estado_producto')
    $sql = "UPDATE producto SET estado_producto = ? WHERE id_producto = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevo_estado, $id_producto]);
    
    // 6. Redirigir de vuelta al listado con un mensaje de éxito
    header('Location: gestion_productos.php?success=' . urlencode($mensaje));
    exit;

} catch (PDOException $e) {
    header('Location: gestion_productos.php?error=' . urlencode("Error al actualizar el estado del producto: " . $e->getMessage()));
    exit;
}
?>