<?php
//  crear_producto.php

session_start();
require_once 'conexion.php'; 

// Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';
$categorias = [];

// 1. Cargar las categorías (necesario para el campo SELECT)
try {
    $stmt_cat = $pdo->query("SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
}

// 2. Procesamiento del Formulario (Método POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    
    // Obtener y sanear datos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0); // Convertir a número flotante
    $stock = (int)($_POST['stock'] ?? 0); // Convertir a número entero
    $unidad_medida = trim($_POST['unidad_medida'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $fecha_registro = date('Y-m-d H:i:s');

    // 3. Validación de campos obligatorios/coherencia
    if (empty($nombre) || $precio <= 0 || $stock < 0 || empty($unidad_medida) || $id_categoria <= 0) {
        $error = "Por favor, completa todos los campos obligatorios correctamente (Nombre, Precio > 0, Stock, Unidad y Categoría).";
    } else {
        try {
            // 4. Insertar el nuevo producto en la base de datos
            $sql = "INSERT INTO producto (nombre, descripcion, precio, stock, unidad_medida, fecha_registro, id_categoria) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([$nombre, $descripcion, $precio, $stock, $unidad_medida, $fecha_registro, $id_categoria]);
            
            $exito = "Producto '{$nombre}' agregado al inventario exitosamente.";
            
            // Redirigir al listado con mensaje de éxito
            header('Location: gestion_productos.php?success=' . urlencode($exito));
            exit;

        } catch (PDOException $e) {
            $error = "Error al guardar el producto: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Producto - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A;
            --color-secondary: #B8860B;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <div class="container mx-auto p-8 max-w-3xl bg-white shadow-lg rounded-lg mt-10">
        <h1 class="text-3xl font-bold mb-6 text-[var(--color-primary)]">Registrar Nuevo Producto</h1>
        
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

        <form method="POST" action="crear_producto.php" class="space-y-4">
            
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Producto *</label>
                <input type="text" id="nombre" name="nombre" required 
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="precio" class="block text-sm font-medium text-gray-700">Precio de Venta ($) *</label>
                    <input type="number" step="0.01" min="0.01" id="precio" name="precio" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-right">
                </div>
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stock Inicial *</label>
                    <input type="number" min="0" id="stock" name="stock" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 text-right">
                </div>
                <div>
                    <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de Medida *</label>
                    <input type="text" id="unidad_medida" name="unidad_medida" required 
                           placeholder="Ej: Unidad, Gramos, Litros, Taza"
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
                        echo '<option value="' . htmlspecialchars($cat['id_categoria']) . '">' . htmlspecialchars($cat['nombre_categoria']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-300">
                    Guardar Producto
                </button>
            </div>
            
        </form>
    </div>

</body>
</html>