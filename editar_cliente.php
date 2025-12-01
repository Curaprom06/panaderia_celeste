<?php
// editar_cliente.php

session_start();
require_once 'conexion.php'; // Incluir la conexión a la BD

// 1. Verificación de Seguridad (Solo Administradores)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';
$cliente_data = [];
$id_cliente = $_GET['id'] ?? null; 

// --- LÓGICA DE PROCESAMIENTO (POST) ---\r\n
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cliente'])) {
    
    // Obtener y sanear datos del formulario POST
    $id_cliente = $_POST['id_cliente'];
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($nombre) || empty($apellido)) {
        $error = "Los campos Nombre y Apellido son obligatorios.";
    } elseif (empty($email) && empty($telefono)) {
        $error = "Debe proporcionar al menos un Email o un Teléfono.";
    } else {
        
        try {
            // 2. Preparar y ejecutar la consulta de actualización
            $sql = "UPDATE cliente SET nombre = ?, apellido = ?, telefono = ?, email = ?, direccion = ? WHERE id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $apellido, $telefono, $email, $direccion, $id_cliente]);

            $exito = "Datos del cliente actualizados con éxito.";
            
            // Redirigir para evitar re-envío del formulario y mostrar el éxito
            header('Location: listar_clientes.php?success=' . urlencode($exito));
            exit;

        } catch (PDOException $e) {
            $error = "Error al actualizar el cliente: " . $e->getMessage();
        }
    }
    
} 

// --- LÓGICA DE CARGA DE DATOS (GET o después de POST con error) ---
if (!$id_cliente) {
    // Si no hay ID, redirigir
    header('Location: listar_clientes.php?error=' . urlencode('ID de cliente no especificado.'));
    exit;
}

try {
    $sql_fetch = "SELECT id_cliente, nombre, apellido, telefono, email, direccion FROM cliente WHERE id_cliente = ?";
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->execute([$id_cliente]);
    $cliente_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$cliente_data) {
        header('Location: listar_clientes.php?error=' . urlencode('Cliente no encontrado.'));
        exit;
    }
    
} catch (PDOException $e) {
    header('Location: listar_clientes.php?error=' . urlencode('Error al cargar los datos del cliente: ' . $e->getMessage()));
    exit;
}


// Si hay un error en POST, cargamos los datos del POST para que no se pierdan.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error) {
    $cliente_data['nombre'] = $nombre;
    $cliente_data['apellido'] = $apellido;
    $cliente_data['telefono'] = $telefono;
    $cliente_data['email'] = $email;
    $cliente_data['direccion'] = $direccion;
}

// Estructura HTML del formulario
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Cliente - <?php echo htmlspecialchars($cliente_data['nombre'] . ' ' . $cliente_data['apellido']); ?></title>
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
                Editar Cliente: <?php echo htmlspecialchars($cliente_data['nombre'] . ' ' . $cliente_data['apellido']); ?>
            </h1>
            <a href="listar_clientes.php" class="text-sm text-gray-600 hover:text-[var(--color-primary)] mb-4 block"><i class="fas fa-arrow-left mr-2"></i> Volver al listado</a>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="editar_cliente.php" method="POST" class="space-y-4">
                <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente_data['id_cliente']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo htmlspecialchars($cliente_data['nombre'] ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div>
                        <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required 
                               value="<?php echo htmlspecialchars($cliente_data['apellido'] ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($cliente_data['telefono'] ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($cliente_data['email'] ?? ''); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                </div>
                
                <div>
                    <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="3"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 resize-none"><?php echo htmlspecialchars($cliente_data['direccion'] ?? ''); ?></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-[var(--color-secondary)] text-white py-2 px-4 rounded-lg shadow-md hover:bg-yellow-700 transition duration-300">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script> 
</body>
</html>