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

<div id="app" class="flex min-h-screen">
    
    <aside class="w-64 bg-[var(--color-primary)] text-white p-6 space-y-6">
        <h2 class="text-2xl font-bold border-b border-white/30 pb-4">Panadería Celeste</h2>
        <nav class="space-y-2">
            <a href="dashboard_admin.php?module=usuarios" class="block py-2.5 px-4 rounded transition duration-200 active-menu">
                Gestión de Usuarios
            </a>
            <a href="gestion_productos.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
                Inventario/Productos
            </a>

            <a href="gestion_categorias.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
                Gestion de Categorías
            </a>

<a href="gestion_compras.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
                Gestión de Compras
            </a>


<a href="gestion_proveedores.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
                Gestión de Proveedores
            </a>

<a href="listar_clientes.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
                Gestión de Clientes
            </a>


            <a href="reportes.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
    Reportes (Ventas/Stock)
</a>

            <a href="historial_ventas.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-white/10">
Historial de Facturas
</a>



        <p class="text-sm text-white mb-2">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
        
        <a href="logout.php" class="block text-center bg-red-600 py-2 rounded transition duration-200 hover:bg-red-700 mt-50">
            Cerrar Sesión
        </a>
    </aside>

    <main class="flex-1 p-8">
        
        <header class="mb-8 border-b pb-4">
            <h1 class="text-4xl font-light text-[var(--color-dark)]">Dashboard de Administración</h1>
        </header>

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
        
        <section id="usuarios" class="p-6">
            


        <section id="usuarios" class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-semibold text-[var(--color-primary)]">
                    Gestión de Usuarios
                </h2>
                <a href="crear_usuario.php" class="bg-[var(--color-primary)] text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
    + Agregar usuario
</a>
            </div>

            <div class="bg-white p-4 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuario
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rol
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body" class="bg-white divide-y divide-gray-200">
    
    <?php
    // Se asume que $pdo ya está disponible desde la inclusión de 'conexion.php' al inicio del archivo
    
    try {
        // 1. Consulta SQL para obtener todos los usuarios, ordenados por ID
        $sql_usuarios = "SELECT id_usuario, nombre, apellido, usuario, rol, estado FROM usuario ORDER BY id_usuario ASC";
        $stmt_usuarios = $pdo->query($sql_usuarios);
        
        // 2. Iterar sobre los resultados y generar las filas HTML
        while ($user = $stmt_usuarios->fetch()) {
            
            // Definir estilos basados en los valores de la BD
            $rol_color = ($user['rol'] === 'Administrador') ? 'text-green-600' : 'text-blue-600';
            $estado_color = ($user['estado'] === 'Activo') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            // Determinar si el usuario actual es el logueado para deshabilitar la acción de inactivar
            $self_edit = ($user['id_usuario'] == $_SESSION['id_usuario']);
            
            // Definir acción y color del botón Inactivar/Activar
            $btn_action = ($user['estado'] === 'Activo') ? 'Inactivar' : 'Activar';
            $btn_color = ($user['estado'] === 'Activo') ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700';
            $disabled_attr = $self_edit ? 'disabled title="No puedes cambiar tu propio estado"' : '';
            
            echo '<tr>';
            
            // Columna 1: Nombre Completo
            echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) . '</td>';
            
            // Columna 2: Usuario
            echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($user['usuario']) . '</td>';
            
            // Columna 3: Rol
            echo '<td class="px-6 py-4 whitespace-nowrap ' . $rol_color . '">' . htmlspecialchars($user['rol']) . '</td>';
            
            // Columna 4: Estado (con etiqueta de color)
            echo '<td class="px-6 py-4 whitespace-nowrap">';
            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $estado_color . '">';
            echo htmlspecialchars($user['estado']);
            echo '</span>';
            echo '</td>';
            
            // Columna 5: Acciones (Editar/Inactivar)
            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
            
            // Botón Editar
            echo '<a href="editar_usuario.php?id=' . $user['id_usuario'] . '" class="px-2 py-1 border rounded bg-[var(--color-secondary)] text-white hover:bg-yellow-700 transition duration-150">';
            echo 'Editar';
            echo '</a>';
            
            
            // Botón Inactivar/Activar (usamos un enlace <a> que simula el botón)
$btn_action = ($user['estado'] === 'Activo') ? 'Inactivar' : 'Activar';
$btn_color = ($user['estado'] === 'Activo') ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700';

// Solo si NO es el usuario logueado, se crea el enlace
if (!$self_edit) {
    // Enlace que pasa el ID y el estado actual a cambiar_estado_usuario.php
    echo '<a href="cambiar_estado_usuario.php?id=' . $user['id_usuario'] . '&estado=' . $user['estado'] . '" 
             class="px-2 py-1 border rounded ' . $btn_color . ' text-white ml-2 transition duration-150">';
    echo $btn_action;
    echo '</a>';
} else {
    // Si es el usuario logueado, mostramos el botón deshabilitado (no es clickable)
    echo '<button disabled title="No puedes cambiar tu propio estado" 
             class="px-2 py-1 border rounded bg-gray-400 text-white ml-2 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150">';
    echo $btn_action;
    echo '</button>';
}
            
            echo '</td>';
            echo '</tr>';
        }
    } catch (PDOException $e) {
        // Muestra un error si la conexión o la consulta fallan
        echo '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error al cargar usuarios: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
    }
    ?>

</tbody>
                </table>
            </div>
        </section>
        
    </main>
</div>

</body>
</html>