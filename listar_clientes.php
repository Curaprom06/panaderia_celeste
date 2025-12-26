<?php
// listar_clientes.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = $_GET['error'] ?? null;
$success_msg = $_GET['success'] ?? null;
$clientes = [];

try {
    // 2. Obtener todos los clientes
    $sql = "SELECT id_cliente, nombre, apellido, telefono, email, direccion, fecha_registro FROM cliente ORDER BY apellido, nombre";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_msg = "Error al cargar los clientes: " . $e->getMessage();
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listado de Clientes</title>
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
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de Clientes
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

    
    <div class="p-6 md:p-10">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-[var(--color-primary)]">Gestión de Clientes</h1>
            <a href="crear_cliente.php" class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-300">
                <i class="fas fa-plus mr-2"></i> Nuevo Cliente
            </a>
        </div>
        
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Éxito:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Completo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reg.</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay clientes registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cliente['id_cliente']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($cliente['nombre']) . ' ' . htmlspecialchars($cliente['apellido']); ?>
                                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($cliente['direccion']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($cliente['telefono'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($cliente['email'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('Y-m-d', strtotime($cliente['fecha_registro'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="editar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" 
                                           class="text-[var(--color-secondary)] hover:text-yellow-900 transition duration-150">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>