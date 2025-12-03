<?php
// gestion_productos.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = $_GET['error'] ?? null;
$success_msg = $_GET['success'] ?? null;

// Obtener la lista de productos
$productos = [];
try {
    // Consulta SQL con JOIN para obtener el nombre de la categoría y el estado
$sql_productos = "
    SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.unidad_medida, p.estado_producto, c.nombre_categoria 
    FROM producto p
    JOIN categoria c ON p.id_categoria = c.id_categoria
    ORDER BY p.id_producto DESC
";
    $stmt_productos = $pdo->query($sql_productos);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_msg = "Error al cargar los productos: " . $e->getMessage();
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Productos - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A;
            --color-secondary: #B8860B;
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
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de Inventario 
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

        <section id="productos" class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-semibold text-[var(--color-primary)]">
                    Listado de Productos
                </h2>
                   
                
                <a href="gestion_categorias.php" class="bg-[var(--color-secondary)] text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
                    + Añadir Categoría
                </a>

                 <a href="crear_producto.php" class="bg-[var(--color-primary)] text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
                    + Añadir Producto
                </a>


            </div>

            <div class="bg-white p-4 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio ($)</th>
        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th> 
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
    </tr>
</thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay productos registrados en el inventario.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($productos as $p): 
                            // Colorear stock bajo (RF 2: Alertas de stock bajo)
                            $stock_class = ($p['stock'] < 10) ? 'font-bold text-red-600' : 'text-gray-900';
                            
                            // Formato de precio
                            $precio_formateado = number_format($p['precio'], 2, '.', ',');
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($p['id_producto']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-medium"><?php echo htmlspecialchars($p['nombre']); ?></p>
                                    <p class="text-xs text-gray-500 truncate w-48"><?php echo htmlspecialchars($p['descripcion']); ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($p['nombre_categoria']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">$<?php echo $precio_formateado; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center <?php echo $stock_class; ?>">
    <?php echo htmlspecialchars($p['stock']); ?> (<?php echo htmlspecialchars($p['unidad_medida']); ?>)
</td>
<?php
    $estado_class = ($p['estado_producto'] === 'Activo') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
?>
<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $estado_class; ?>">
        <?php echo htmlspecialchars($p['estado_producto']); ?>
    </span>
</td>
<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
    <a href="editar_producto.php?id=<?php echo $p['id_producto']; ?>" class="text-white px-2 py-1 rounded bg-[var(--color-secondary)] hover:bg-yellow-700 transition duration-150">
        Editar
    </a>
    
    <?php
        $btn_action = ($p['estado_producto'] === 'Activo') ? 'Inactivar' : 'Activar';
        $btn_color = ($p['estado_producto'] === 'Activo') ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700';
    ?>
    <a href="cambiar_estado_producto.php?id=<?php echo $p['id_producto']; ?>&estado=<?php echo $p['estado_producto']; ?>"
       class="text-white px-2 py-1 rounded <?php echo $btn_color; ?> ml-2 transition duration-150">
        <?php echo $btn_action; ?>
    </a>
</td>
</tr>
                        <?php endforeach; ?>
                        
                    </tbody>
                </table>
            </div>
        </section>
        
    </main>
</div>

</body>
</html>