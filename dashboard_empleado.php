<?php
// dashboard_empleado.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Empleados y Administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// 2. Si es Administrador, redirigir al dashboard de admin (para evitar confusiones)
if ($_SESSION['rol'] === 'Administrador') {
    header('Location: dashboard_admin.php');
    exit;
}

// Si llega aquí, es un Empleado.
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Empleado - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; 
            --color-secondary: #B8860B; 
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        
        <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl text-center">
            
            <h1 class="text-4xl font-extrabold text-[var(--color-primary)] mb-2">
                ¡Bienvenido, <?php echo $nombre_usuario; ?>!
            </h1>
            <p class="text-lg text-gray-600 mb-8">
                Tu rol es: <span class="font-semibold text-[var(--color-secondary)]">Empleado</span>
            </p>

            <h2 class="text-2xl font-bold mb-6 text-gray-700">Acceso Rápido</h2>

            <a href="punto_venta.php" 
               class="block w-full bg-green-600 text-white py-5 rounded-lg shadow-lg hover:bg-green-700 transition duration-300 transform hover:scale-105 mb-4">
                <span class="text-3xl font-bold block">Iniciar Punto de Venta (PDV)</span>
                <span class="text-sm block mt-1">Registrar ventas rápidamente</span>
            </a>
            
            <a href="logout.php" 
               class="block w-full bg-red-500 text-white py-3 rounded-lg shadow hover:bg-red-600 transition duration-300 mt-6">
                Cerrar Sesión
            </a>
            
            </div>

    </div>
</body>
</html>