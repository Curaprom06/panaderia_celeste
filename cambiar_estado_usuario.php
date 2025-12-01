<?php
// cambiar_estado_usuario.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden ejecutar esto
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// 2. Obtener datos de la URL (GET)
$id_usuario = $_GET['id'] ?? null;
$estado_actual = $_GET['estado'] ?? null; // 'Activo' o 'Inactivo'

// 3. Validar los datos
if (!$id_usuario || !$estado_actual || !in_array($estado_actual, ['Activo', 'Inactivo'])) {
    // Si faltan datos o son incorrectos, redirigir con un error
    header('Location: dashboard_admin.php?error=Datos incompletos o incorrectos.');
    exit;
}

// 4. Determinar el NUEVO estado
$nuevo_estado = ($estado_actual === 'Activo') ? 'Inactivo' : 'Activo';
$mensaje = "Usuario ID {$id_usuario} cambiado a {$nuevo_estado}.";

// 5. Evitar que un administrador se inactive a sí mismo (seguridad)
if ($id_usuario == $_SESSION['id_usuario'] && $nuevo_estado === 'Inactivo') {
    header('Location: dashboard_admin.php?error=No puedes inactivar tu propia cuenta.');
    exit;
}

try {
    // 6. Preparar y ejecutar la consulta de actualización
    $sql = "UPDATE usuario SET estado = ? WHERE id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevo_estado, $id_usuario]);
    
    // 7. Redirigir de vuelta al listado con un mensaje de éxito
    header('Location: dashboard_admin.php?success=' . urlencode($mensaje));
    exit;

} catch (PDOException $e) {
    // Manejo de error de la base de datos
    header('Location: dashboard_admin.php?error=' . urlencode("Error al actualizar el estado: " . $e->getMessage()));
    exit;
}
?>