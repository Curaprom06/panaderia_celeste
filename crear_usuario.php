<?php
// crear_usuario.php

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
    $usuario = trim($_POST['usuario'] ?? '');
    $contraseña = $_POST['contraseña'] ?? ''; // No sanear la contraseña antes de hashearla
    $rol = $_POST['rol'] ?? '';
    $estado = $_POST['estado'] ?? 'Activo'; // Por defecto, Activo
    $dui = trim($_POST['dui'] ?? '');

    // 3. Validación de campos mínimos
    if (empty($nombre) || empty($usuario) || empty($contraseña) || empty($rol)) {
        $error = "Todos los campos obligatorios (Nombre, Usuario, Contraseña, Rol) deben ser llenados.";
    } elseif (!in_array($rol, ['Administrador', 'Empleado'])) {
        $error = "Rol inválido seleccionado.";
    } else {
        // En un sistema real, AQUI SE USARIA HASHING SEGURO (password_hash)
        // Por la simplicidad de la prueba, seguimos con el texto plano.
        $contraseña_almacenar = $contraseña;
        
        try {
            // 4. Verificar si el nombre de usuario ya existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = ?");
            $stmt_check->execute([$usuario]);
            if ($stmt_check->fetchColumn() > 0) {
                $error = "El nombre de usuario '{$usuario}' ya está en uso.";
            } else {
                // 5. Insertar el nuevo usuario en la base de datos
                $sql = "INSERT INTO usuario (nombre, apellido, usuario, contraseña, rol, estado, dui) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([$nombre, $apellido, $usuario, $contraseña_almacenar, $rol, $estado, $dui]);
                
                $exito = "Usuario '{$usuario}' creado exitosamente como {$rol}.";
                
                // Opcional: Limpiar los campos después del éxito (redireccionando)
                // header('Location: dashboard_admin.php?success=' . urlencode($exito));
                // exit;
            }
        } catch (PDOException $e) {
            $error = "Error al guardar el usuario: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Usuario - Panadería Celeste</title>
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
    
    <div class="container mx-auto p-8 max-w-2xl bg-white shadow-lg rounded-lg mt-10">
        <h1 class="text-3xl font-bold mb-6 text-[var(--color-primary)]">Crear Nuevo Usuario</h1>
        
        <a href="dashboard_admin.php" class="inline-block mb-6 text-[var(--color-primary)] hover:underline">
            ← Volver a Gestión de Usuarios
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

        <form method="POST" action="crear_usuario.php" class="space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido</label>
                    <input type="text" id="apellido" name="apellido"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700">Nombre de Usuario *</label>
                    <input type="text" id="usuario" name="usuario" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="contraseña" class="block text-sm font-medium text-gray-700">Contraseña *</label>
                    <input type="password" id="contraseña" name="contraseña" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700">Rol *</label>
                    <select id="rol" name="rol" required 
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white">
                        <option value="">Seleccione un Rol</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Empleado">Empleado</option>
                    </select>
                </div>
                <div>
                    <label for="dui" class="block text-sm font-medium text-gray-700">DUI/Identificación</label>
                    <input type="text" id="dui" name="dui"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-300">
                    Guardar Usuario
                </button>
            </div>
            
        </form>
    </div>

</body>
</html>