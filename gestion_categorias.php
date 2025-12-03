<?php
// gestion_categorias.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = null;
$success_msg = null;

// ===================================
// LÓGICA DE PROCESAMIENTO
// ===================================

// A. Crear Nueva Categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_categoria'])) {
    $nombre = trim($_POST['nombre_categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre)) {
        $error_msg = "El nombre de la categoría no puede estar vacío.";
    } else {
        try {
            // Verificar si ya existe una categoría con ese nombre
            $sql_check = "SELECT id_categoria FROM categoria WHERE nombre_categoria = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$nombre]);
            
            if ($stmt_check->rowCount() > 0) {
                $error_msg = "Ya existe una categoría con el nombre: " . htmlspecialchars($nombre);
            } else {
                // Insertar nueva categoría
                $sql_insert = "INSERT INTO categoria (nombre_categoria, descripcion) VALUES (?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([$nombre, $descripcion]);
                $success_msg = "Categoría '" . htmlspecialchars($nombre) . "' creada con éxito.";
                // Redirigir para evitar reenvío del formulario
                header('Location: gestion_categorias.php?success=' . urlencode($success_msg));
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Error al crear la categoría: " . $e->getMessage();
        }
    }
}

// B. Eliminar Categoría (Simple: Borrado físico. En producción se preferiría un borrado lógico)
if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $id_categoria = (int)$_GET['id'];
    
    try {
        // Opción segura: Verificar si la categoría tiene productos asociados antes de eliminar
        $sql_check_prod = "SELECT COUNT(*) FROM producto WHERE id_categoria = ?";
        $stmt_check_prod = $pdo->prepare($sql_check_prod);
        $stmt_check_prod->execute([$id_categoria]);
        
        if ($stmt_check_prod->fetchColumn() > 0) {
            $error_msg = "No se puede eliminar la categoría porque tiene productos asociados. Primero reasigna o elimina los productos.";
            header('Location: gestion_categorias.php?error=' . urlencode($error_msg));
            exit;
        }

        // Eliminar la categoría
        $sql_delete = "DELETE FROM categoria WHERE id_categoria = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$id_categoria]);
        
        $success_msg = "Categoría eliminada con éxito.";
        header('Location: gestion_categorias.php?success=' . urlencode($success_msg));
        exit;
    } catch (PDOException $e) {
        $error_msg = "Error al eliminar la categoría: " . $e->getMessage();
        header('Location: gestion_categorias.php?error=' . urlencode($error_msg));
        exit;
    }
}

// ===================================
// LÓGICA DE VISUALIZACIÓN
// ===================================

// Obtener lista de categorías
$categorias = [];
try {
    $sql_categorias = "SELECT id_categoria, nombre_categoria, descripcion FROM categoria ORDER BY nombre_categoria ASC";
    $stmt_categorias = $pdo->query($sql_categorias);
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_msg = "Error al cargar las categorías: " . $e->getMessage();
}

// Obtener mensajes de URL (si existen)
$error_msg = $_GET['error'] ?? $error_msg;
$success_msg = $_GET['success'] ?? $success_msg;
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Categorías - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A;
            --color-secondary: #B8860B;
            --color-light: #F9FAFB;
            --color-dark: #111827;
        }
    </style>
</head>
<body class="bg-[var(--color-light)] text-[var(--color-dark)] font-sans">

    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-tags mr-2"></i> Gestión de Categorías
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <a href="gestion_productos.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Productos
                </a>
                
                <a href="dashboard_admin.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Dashboard
                </a>

                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>


    <main class="flex-1 max-w-7xl mx-auto p-8">
        
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

        <section class="mb-8 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold mb-4 text-[var(--color-primary)]">
                Crear Nueva Categoría
            </h2>
            <form action="gestion_categorias.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="nombre_categoria" class="block text-sm font-medium text-gray-700">Nombre de Categoría</label>
                    <input type="text" name="nombre_categoria" id="nombre_categoria" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div class="md:col-span-1">
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <input type="text" name="descripcion" id="descripcion" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div class="md:col-span-1">
                    <button type="submit" name="crear_categoria" class="w-full bg-green-600 text-white py-2 px-4 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                        <i class="fas fa-plus-circle mr-1"></i> Crear Categoría
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold mb-4 text-[var(--color-primary)]">
                Listado de Categorías (<?php echo count($categorias); ?>)
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay categorías registradas.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($c['id_categoria']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold"><?php echo htmlspecialchars($c['nombre_categoria']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-sm truncate"><?php echo htmlspecialchars($c['descripcion'] ?: 'N/A'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-center">
                                <a href="editar_categoria.php?id=<?php echo $c['id_categoria']; ?>"
                                class="text-white px-3 py-1 rounded bg-[var(--color-secondary)] hover:bg-yellow-700 transition duration-150 mr-2">
                                Editar
                                </a>
    
                                <a href="gestion_categorias.php?action=eliminar&id=<?php echo $c['id_categoria']; ?>"
                                onclick="return confirm('¿Estás seguro de que deseas eliminar la categoría <?php echo htmlspecialchars($c['nombre_categoria']); ?>? (Solo se eliminará si no tiene productos asociados)');"
                                class="text-white px-3 py-1 rounded bg-red-600 hover:bg-red-700 transition duration-150">
                                Eliminar
                                </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                    </tbody>
                </table>
            </div>
        </section>
        
    </main>

</body>
</html>