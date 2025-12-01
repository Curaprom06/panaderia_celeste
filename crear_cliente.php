<?php
// crear_cliente.php

session_start();
require_once 'conexion.php'; // Incluir la conexión a la BD

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';

// 2. Procesamiento del Formulario (Método POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener y sanear datos
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // 3. Validación de campos mínimos
    if (empty($nombre) || empty($apellido)) {
        $error = "Los campos Nombre y Apellido son obligatorios.";
    } elseif (empty($email) && empty($telefono)) {
        $error = "Debe proporcionar al menos un Email o un Teléfono.";
    } else {
        
        try {
            // 4. Preparar y ejecutar la consulta de inserción
            $sql = "INSERT INTO cliente (nombre, apellido, telefono, email, direccion, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
$stmt->execute([$nombre, $apellido, $telefono, $email, $direccion]);

// 1. Mensaje de éxito para enviar a la página de destino
$mensaje_exito = "Cliente **{$nombre} {$apellido}** registrado con éxito.";

// 2. Redirigir al usuario a la página de listado (listar_clientes.php)
// Usamos urlencode para asegurar que el mensaje se transmita correctamente.
header('Location: listar_clientes.php?success=' . urlencode($mensaje_exito));
exit; // ¡Es crucial usar exit después de un header Location!
// -----------------------------------------------------------
            
        } catch (PDOException $e) {
            // Error, ej: si el email estuviera como UNIQUE
            $error = "Error al registrar el cliente: " . $e->getMessage();
        }
    }
}

// Estructura HTML del formulario
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Cliente - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; 
            --color-secondary: #B8860B; 
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">


 <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de clientes
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <a href="dashboard_admin.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Panel
                </a>

                <a href="listar_clientes.php" class="text-gray-200 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Volver a Gestion de Clientes
                </a>

                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>




    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold mb-6 text-[var(--color-primary)] text-center">
                Registrar Nuevo Cliente
            </h1>
            <a href="listar_clientes.php" class="text-sm text-gray-600 hover:text-[var(--color-primary)] mb-4 block"><i class="fas fa-arrow-left mr-2"></i> Volver al listado</a>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Éxito:</strong>
                    <span class="block sm:inline"><?php echo $exito; ?></span>
                </div>
            <?php endif; ?>

            <form action="crear_cliente.php" method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div>
                        <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required 
                               value="<?php echo htmlspecialchars($apellido ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                </div>
                
                <div>
                    <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 resize-none"><?php echo htmlspecialchars($direccion ?? ''); ?></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-300">
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script> 
    </body>
</html>