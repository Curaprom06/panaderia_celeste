<?php
// editar_categoria.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = null;
$success_msg = null;
$categoria = null; // Variable para almacenar los datos de la categoría a editar

// Obtener el ID de la categoría de la URL
$id_categoria = (int)($_GET['id'] ?? 0);

if ($id_categoria <= 0) {
    header('Location: gestion_categorias.php?error=' . urlencode('ID de categoría no válido.'));
    exit;
}

// ===================================
// LÓGICA DE PROCESAMIENTO: Actualizar datos (POST)
// ===================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_categoria'])) {
    
    $id = (int)$_POST['id_categoria'];
    $nombre = trim($_POST['nombre_categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($id !== $id_categoria) {
        // Validación básica de seguridad
        $error_msg = "Error de seguridad: ID de formulario no coincide con ID de URL.";
    } elseif (empty($nombre)) {
        $error_msg = "El nombre de la categoría no puede estar vacío.";
    } else {
        try {
            // 1. Verificar unicidad: Asegurar que el nuevo nombre no exista ya en OTRA categoría
            $sql_check = "SELECT id_categoria FROM categoria WHERE nombre_categoria = ? AND id_categoria != ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$nombre, $id]);
            
            if ($stmt_check->rowCount() > 0) {
                $error_msg = "Ya existe otra categoría con el nombre: " . htmlspecialchars($nombre);
            } else {
                // 2. Actualizar la categoría
                $sql_update = "UPDATE categoria SET nombre_categoria = ?, descripcion = ? WHERE id_categoria = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$nombre, $descripcion, $id]);
                
                $success_msg = "Categoría '" . htmlspecialchars($nombre) . "' actualizada con éxito.";
                // Redirigir a la lista con mensaje de éxito (Patrón Post/Redirect/Get)
                header('Location: gestion_categorias.php?success=' . urlencode($success_msg));
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Error al actualizar la categoría: " . $e->getMessage();
        }
    }
}

// ===================================
// LÓGICA DE VISUALIZACIÓN: Cargar datos iniciales (GET)
// ===================================
try {
    $sql_fetch = "SELECT id_categoria, nombre_categoria, descripcion FROM categoria WHERE id_categoria = ?";
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->execute([$id_categoria]);
    $categoria = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        header('Location: gestion_categorias.php?error=' . urlencode('Categoría no encontrada.'));
        exit;
    }
    
    // Si hubo un error en el POST, usamos los datos enviados por POST para repoblar el formulario.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error_msg) {
         $categoria['nombre_categoria'] = $_POST['nombre_categoria'];
         $categoria['descripcion'] = $_POST['descripcion'];
    }

} catch (PDOException $e) {
    header('Location: gestion_categorias.php?error=' . urlencode('Error de base de datos al cargar: ' . $e->getMessage()));
    exit;
}

// Definir variables de color (copiadas de gestion_productos.php para consistencia de diseño)
$color_primary = '#1E3A8A';
$color_secondary = '#B8860B';
$color_light = '#F9FAFB';
$color_dark = '#111827';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Categoría - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: <?php echo $color_primary; ?>;
            --color-secondary: <?php echo $color_secondary; ?>;
            --color-light: <?php echo $color_light; ?>;
            --color-dark: <?php echo $color_dark; ?>;
        }
    </style>
</head>
<body class="bg-[var(--color-light)] text-[var(--color-dark)] font-sans">

    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-edit mr-2"></i> Editar Categoría
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <a href="gestion_categorias.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Categorías
                </a>
                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>


    <main class="flex-1 max-w-4xl mx-auto p-8">
        
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p>Error: <?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php endif; ?>

        <section class="mb-8 bg-white p-6 rounded-lg shadow-xl border-t-4 border-[var(--color-secondary)]">
            <h2 class="text-2xl font-semibold mb-4 text-[var(--color-primary)]">
                Editando: <?php echo htmlspecialchars($categoria['nombre_categoria']); ?> (ID: <?php echo $categoria['id_categoria']; ?>)
            </h2>
            
            <form action="editar_categoria.php?id=<?php echo $categoria['id_categoria']; ?>" method="POST" class="space-y-4">
                
                <input type="hidden" name="id_categoria" value="<?php echo htmlspecialchars($categoria['id_categoria']); ?>">
                
                <div>
                    <label for="nombre_categoria" class="block text-sm font-medium text-gray-700">Nombre de Categoría</label>
                    <input type="text" name="nombre_categoria" id="nombre_categoria" required 
                           value="<?php echo htmlspecialchars($categoria['nombre_categoria']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[var(--color-secondary)] focus:border-[var(--color-secondary)]">
                </div>
                
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[var(--color-secondary)] focus:border-[var(--color-secondary)]"><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <a href="gestion_categorias.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-50 hover:bg-gray-100 transition duration-150">
                        Cancelar
                    </a>
                    <button type="submit" name="editar_categoria" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--color-primary)] hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] transition duration-150">
                        <i class="fas fa-save mr-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </section>
        
    </main>
</div>

</body>
</html>