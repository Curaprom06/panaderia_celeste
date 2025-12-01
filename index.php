<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul marino */
            --color-secondary: #B8860B; /* Dorado */
            --color-light: #F9FAFB; /* Gris muy claro */
            --color-dark: #111827; /* Gris oscuro */
        }
        /* Opcional: Centrar correctamente el div de login */
        .min-h-screen {
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-[var(--color-light)] text-[var(--color-dark)] font-sans">

    <?php
    // Incluye el archivo de procesamiento de login (que manejará los errores)
    // Usamos 'require_once' para asegurar que esté cargado.
    require_once 'login.php'; 
    
    // Si la sesión ya está activa, redirige al usuario para evitar que vea el login de nuevo.
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if ($_SESSION['rol'] === 'Administrador') {
            header('Location: dashboard_admin.php');
        } else {
            header('Location: punto_venta.php');
        }
        exit();
    }
    ?>
    
    <div id="login" class="min-h-screen flex items-center justify-center bg-[var(--color-primary)] text-white">
        <div class="bg-white text-[var(--color-dark)] rounded-lg shadow-lg p-8 w-full max-w-sm">
            <h1 class="text-2xl font-bold text-center mb-6 text-[var(--color-primary)]">
                Panadería Celeste
            </h1>
            <p class="text-center text-gray-600 mb-6">Iniciar Sesión</p>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" action="index.php"> 
                <div class="mb-4">
                    <label for="usuario" class="block text-sm font-medium text-gray-700">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--color-secondary)] focus:border-[var(--color-secondary)]">
                </div>
                <div class="mb-6">
                    <label for="contraseña" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" id="contraseña" name="contraseña" placeholder="Ingresa tu contraseña" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--color-secondary)] focus:border-[var(--color-secondary)]">
                </div>
                
                <button type="submit" id="loginBtn" 
                        class="w-full bg-[var(--color-primary)] text-white py-2 rounded-lg shadow-lg hover:bg-blue-800 transition duration-300">
                    Iniciar Sesión
                </button>
            </form>
            </div>
    </div>

</body>
</html>