<?php
// editar_usuario.php

session_start();
require_once 'conexion.php'; // Incluir la conexión a la BD

// 1. Verificación de Seguridad (Solo Administradores)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';
$usuario_data = [];
$id_usuario = $_GET['id'] ?? null; // Obtener el ID de la URL

// --- LÓGICA DE PROCESAMIENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    
    // Obtener y sanear datos del formulario POST
    $id_usuario = $_POST['id_usuario'];
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $estado = $_POST['estado'] ?? 'Activo';
    $dui = trim($_POST['dui'] ?? '');
    $nueva_contraseña = $_POST['contraseña'] ?? '';

    if (empty($nombre) || empty($usuario) || empty($rol)) {
        $error = "Los campos Nombre, Usuario y Rol son obligatorios.";
    } elseif (!in_array($rol, ['Administrador', 'Empleado'])) {
        $error = "Rol inválido seleccionado.";
    } else {
        try {
            // 2. Construir la consulta de actualización
            $sql = "UPDATE usuario SET nombre = ?, apellido = ?, usuario = ?, rol = ?, estado = ?, dui = ?";
            $params = [$nombre, $apellido, $usuario, $rol, $estado, $dui];

            // Si se ingresó una nueva contraseña, actualizarla (usando texto plano para la prueba)
            if (!empty($nueva_contraseña)) {
                // 🚨 Recordatorio: Aquí se debe usar password_hash() en producción
                $sql .= ", contraseña = ?";
                $params[] = $nueva_contraseña;
            }
            
            $sql .= " WHERE id_usuario = ?";
            $params[] = $id_usuario;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $exito = "Usuario ID #{$id_usuario} actualizado exitosamente.";

            // Redirigir para forzar la recarga de datos actualizados en el formulario
            header('Location: editar_usuario.php?id=' . $id_usuario . '&success=' . urlencode($exito));
            exit;

        } catch (PDOException $e) {
            $error = "Error al actualizar el usuario: " . $e->getMessage();
        }
    }
}

// --- LÓGICA DE CARGA DE DATOS (GET) ---

// Se utiliza $id_usuario del GET o del POST (después de fallar la validación)
if ($id_usuario) {
    try {
        $sql = "SELECT id_usuario, nombre, apellido, usuario, rol, estado, dui, contraseña FROM usuario WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario]);
        $usuario_data = $stmt->fetch();

        if (!$usuario_data) {
            $error = "Usuario no encontrado.";
            $id_usuario = null;
        }
    } catch (PDOException $e) {
        $error = "Error al buscar el usuario: " . $e->getMessage();
    }
} else {
    $error = "ID de usuario no especificado.";
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
    <title>Editar Usuario - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A;
            --color-secondary: #B8860B;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <div class="container mx-auto p-8 max-w-2xl bg-white shadow-lg rounded-lg mt-10">
        <h1 class="text-3xl font-bold mb-6 text-[var(--color-primary)]">
            Editar Usuario #<?php echo htmlspecialchars($id_usuario); ?>
        </h1>
        
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
        
        <?php if ($usuario_data): ?>
        <form method="POST" action="editar_usuario.php" class="space-y-4">
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuario_data['id_usuario']); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($usuario_data['nombre']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido</label>
                    <input type="text" id="apellido" name="apellido"
                           value="<?php echo htmlspecialchars($usuario_data['apellido']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700">Nombre de Usuario *</label>
                    <input type="text" id="usuario" name="usuario" required 
                           value="<?php echo htmlspecialchars($usuario_data['usuario']); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                <div>
                    <label for="contraseña" class="block text-sm font-medium text-gray-700">Contraseña (Dejar vacío para no cambiar)</label>
                    <input type="password" id="contraseña" name="contraseña" placeholder="Dejar vacío para mantener la actual"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700">Rol *</label>
                    <select id="rol" name="rol" required 
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white">
                        <option value="Administrador" <?php echo ($usuario_data['rol'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="Empleado" <?php echo ($usuario_data['rol'] == 'Empleado') ? 'selected' : ''; ?>>Empleado</option>
                    </select>
                </div>
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado *</label>
                    <select id="estado" name="estado" required 
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white">
                        <option value="Activo" <?php echo ($usuario_data['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo ($usuario_data['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label for="dui" class="block text-sm font-medium text-gray-700">DUI/Identificación</label>
                <input type="text" id="dui" name="dui"
                       value="<?php echo htmlspecialchars($usuario_data['dui']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
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