<?php
//  editar_producto.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad (Solo Administradores)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';
$producto_data = [];
$categorias = [];
$id_producto = $_REQUEST['id'] ?? null; // Usa $_REQUEST para GET o POST

// 2. Cargar Categorías (necesario para el campo SELECT)
try {
    $stmt_cat = $pdo->query("SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
}

// --- LÓGICA DE PROCESAMIENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $id_producto) {
    
    // Obtener y sanear datos del formulario POST
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $unidad_medida = trim($_POST['unidad_medida'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);

    if (empty($nombre) || $precio <= 0 || $stock < 0 || empty($unidad_medida) || $id_categoria <= 0) {
        $error = "Por favor, completa todos los campos obligatorios correctamente.";
    } else {
        try {
            // 3. Consulta de actualización
            $sql = "UPDATE producto SET 
                        nombre = ?, 
                        descripcion = ?, 
                        precio = ?, 
                        stock = ?, 
                        unidad_medida = ?, 
                        id_categoria = ? 
                    WHERE id_producto = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $descripcion, $precio, $stock, $unidad_medida, $id_categoria, $id_producto]);
            
            $exito = "Producto ID #{$id_producto} actualizado exitosamente.";

            // Redirigir para recargar los datos actualizados y mostrar el mensaje de éxito
            header('Location: editar_producto.php?id=' . $id_producto . '&success=' . urlencode($exito));
            exit;

        } catch (PDOException $e) {
            $error = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
}

// --- LÓGICA DE CARGA DE DATOS (GET) ---

if ($id_producto) {
    try {
        // 4. Consulta para obtener los datos del producto
        $sql = "SELECT id_producto, nombre, descripcion, precio, stock, unidad_medida, id_categoria FROM producto WHERE id_producto = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_producto]);
        $producto_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto_data) {
            $error = "Producto no encontrado.";
            $id_producto = null;
        }
    } catch (PDOException $e) {
        $error = "Error al buscar el producto: " . $e->getMessage();
    }
} else {
    $error = "ID de producto no especificado.";
}

// Si viene un mensaje de éxito por URL después de la redirección
if (isset($_GET['success'])) {
    $exito = htmlspecialchars($_GET['success']);
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Producto - Panadería Celeste</title>
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
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de Compras
            </h1>

<div class="flex items-center space-x-4 text-white">
                <a href="gestion_productos.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Inventario
                </a>
            


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


    
    <div class="container mx-auto p-8 max-w-3xl bg-white shadow-lg rounded-lg mt-10">
        <h1 class="text-3xl font-bold mb-6 text-[var(--color-primary)]">
            Editar Producto #<?php echo htmlspecialchars($id_producto); ?>: <?php echo htmlspecialchars($producto_data['nombre'] ?? 'Cargando...'); ?>
        </h1>
        
        <a href="gestion_productos.php" class="inline-block mb-6 text-[var(--color-primary)] hover:underline">
            ← Volver a Inventario
        </a>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($exito): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p><?php echo htmlspecialchars($exito); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($producto_data): ?>
        <form method="POST" action="editar_producto.php?id=<?php echo htmlspecialchars($id_producto); ?>" class="space-y-4">
            <input type="hidden" name="id_producto" value="<?php echo htmlspecialchars($producto_data['id_producto']); ?>">
            
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Producto *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo htmlspecialchars($producto_data['nombre']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"><?php echo htmlspecialchars($producto_data['descripcion']); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="precio" class="block text-sm font-medium text-gray-700">Precio de Venta ($) *</label>
                    <input type="number" step="0.01" min="0.01" id="precio" name="precio" required 
                           value="<?php echo htmlspecialchars($producto_data['precio']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-right">
                </div>
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stock *</label>
                    <input type="number" min="0" id="stock" name="stock" required 
                           value="<?php echo htmlspecialchars($producto_data['stock']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-right">
                </div>
                <div>
                    <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de Medida *</label>
                    <input type="text" id="unidad_medida" name="unidad_medida" required 
                           value="<?php echo htmlspecialchars($producto_data['unidad_medida']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div>
                <label for="id_categoria" class="block text-sm font-medium text-gray-700">Categoría *</label>
                <select id="id_categoria" name="id_categoria" required 
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white">
                    <option value="">Seleccione una Categoría</option>
                    <?php 
                    // Llenar el select con las categorías de la BD
                    foreach ($categorias as $cat) {
                        $selected = ($cat['id_categoria'] == $producto_data['id_categoria']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($cat['id_categoria']) . '" ' . $selected . '>' . htmlspecialchars($cat['nombre_categoria']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-[var(--color-secondary)] text-white py-2 px-4 rounded-lg shadow-md hover:bg-yellow-700 transition duration-300">
                    Guardar Cambios
                </button>
            </div>
            
        </form>
        <?php endif; ?>
    </div>

</body>
</html>