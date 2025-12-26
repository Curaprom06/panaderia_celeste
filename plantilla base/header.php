<?php
// dashboard_admin.php

session_start();

// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no está logueado, redirigir al login
    header('Location: index.php');
    exit;
}

// 2. Verificar si el rol es 'Administrador' (Control de acceso)
if ($_SESSION['rol'] !== 'Administrador') {
    // Si es un Empleado, redirigir a su vista (o mostrar un mensaje de error)
    header('Location: punto_venta.php'); // Redirección temporal
    exit;
}
// Manejo de mensajes de URL (después de redirecciones)
$error_msg = $_GET['error'] ?? null;
$success_msg = $_GET['success'] ?? null;
// ... (Más abajo, en el MAIN, necesitas mostrar estos mensajes)

// Incluir la conexión a la base de datos (necesaria para listar usuarios más adelante)
require_once 'conexion.php'; 



// El usuario y rol están en $_SESSION['usuario'] y $_SESSION['rol']
$nombre_usuario = $_SESSION['usuario'];

?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul marino */
            --color-secondary: #B8860B; /* Dorado */
            --color-light: #F9FAFB;
            --color-dark: #111827;
        }
        .active-menu {
            background-color: var(--color-secondary);
            color: white;
            font-weight: bold;
        }
    </style>
</head>

<body class="bg-[var(--color-light)] text-[var(--color-dark)] font-sans">

   <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-shopping-cart mr-2"></i> Prueba
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <a href="dashboard_admin.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Panel
                </a>
                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>


    <main class="flex-1 p-8">
        

        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p>Error: <?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p>Éxito: <?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        <?php endif; ?>

        
</body>
</html>