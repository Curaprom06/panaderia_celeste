<?php
// login.php

// Iniciar sesión para guardar el estado del usuario (quién ha iniciado sesión)
session_start();

// Incluir el archivo de conexión
require 'conexion.php';

// Verificar si la solicitud es de tipo POST (si se envió el formulario)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Obtener y sanear los datos del formulario
    $usuario = $_POST['usuario'] ?? '';
    $contraseña_ingresada = $_POST['contraseña'] ?? '';

    // 2. Preparar la consulta SQL
    // Se usa una consulta preparada para prevenir inyección SQL (seguridad)
    $sql = "SELECT id_usuario, usuario, contraseña, rol, estado FROM usuario WHERE usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // 3. Verificar usuario y contraseña
    if ($user) {
        // En un sistema real, aquí se usaría password_verify()
        // Por ahora, comparamos la contraseña directamente ya que la guardamos sin hash para la prueba.
        
        // 🚨 CAMBIAR ESTO: En producción, guarda la contraseña hasheada y usa password_verify($contraseña_ingresada, $user['contraseña']).
        if ($contraseña_ingresada === $user['contraseña']) {
            
            // 4. Verificar el estado del usuario
            if ($user['estado'] === 'Activo') {
                
                // 5. Iniciar la sesión (Autenticación exitosa)
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['rol'] = $user['rol'];

                // 6. Redirigir según el rol (Control de Acceso Básico)
                if ($_SESSION['rol'] === 'Administrador') {
                    header('Location: dashboard_admin.php'); // Redirigir al dashboard del administrador
                } elseif ($_SESSION['rol'] === 'Empleado') {
                    // 🚨 CAMBIO AQUÍ: Redirección al nuevo Dashboard
                    header('Location: dashboard_empleado.php'); 
                } else {
                    // Redirección por defecto o a una página de error si el rol no existe
                    header('Location: index.php?error=Rol de usuario no reconocido.');
                }
                exit();
                
            } else {
                // Usuario Inactivo
                $error = "Tu cuenta está inactiva. Contacta al administrador.";
            }
        } else {
            // Contraseña incorrecta
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        // Usuario no encontrado
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Login</title>
</head>
<body>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    </body>
</html>