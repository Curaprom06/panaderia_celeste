<?php
// ver_detalle_venta.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$id_venta = $_GET['id'] ?? null;
$error_msg = '';
$venta = null;
$detalles = [];

// 2. Validar ID de Venta
if (!$id_venta || !is_numeric($id_venta)) {
    header('Location: historial_ventas.php?error=ID de Venta inválido.');
    exit;
}

try {
    // 3. Obtener datos de la Venta principal
    $sql_venta = "
        SELECT v.id_venta, v.total, v.fecha, u.usuario AS nombre_empleado
        FROM venta v
        JOIN usuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_venta = ?
    ";
    $stmt_venta = $pdo->prepare($sql_venta);
    $stmt_venta->execute([$id_venta]);
    $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $error_msg = "Venta no encontrada.";
    } else {
        // 4. Obtener los Detalles de la Venta (productos)
        $sql_detalles = "
            SELECT 
                dv.cantidad, 
                dv.precio_unitario, 
                p.nombre AS nombre_producto,
                (dv.cantidad * dv.precio_unitario) AS subtotal
            FROM detalle_venta dv
            JOIN producto p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?
        ";
        $stmt_detalles = $pdo->prepare($sql_detalles);
        $stmt_detalles->execute([$id_venta]);
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_msg = "Error al cargar los detalles de la venta: " . $e->getMessage();
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de Venta #<?php echo htmlspecialchars($id_venta); ?> - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul marino */
            --color-secondary: #B8860B; /* Dorado */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <!-- Navbar (simplificado) -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="text-white text-2xl font-bold">
                Admin | Detalle de Venta
            </div>
            <div>
                <a href="historial_ventas.php" class="text-white hover:text-gray-300 transition duration-150">
                    < Regresar al Historial
                </a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">

        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php elseif ($venta): ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">
                Detalle de Venta #<?php echo htmlspecialchars($venta['id_venta']); ?>
            </h1>

            <!-- Información General de la Venta -->
            <div class="bg-white shadow-xl rounded-lg p-6 mb-8 border-l-4 border-[var(--color-secondary)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fecha y Hora:</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($venta['fecha']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Empleado:</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($venta['nombre_empleado']); ?></p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <p class="text-lg font-medium text-gray-500">TOTAL DE LA VENTA:</p>
                    <p class="text-3xl font-extrabold text-[var(--color-primary)]">$<?php echo number_format($venta['total'], 2); ?></p>
                </div>
            </div>

            <!-- Tabla de Productos Vendidos -->
            <h2 class="text-2xl font-bold text-gray-700 mb-4">Productos Incluidos</h2>

            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cantidad
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Precio Unitario
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($detalles) > 0): ?>
                                <?php foreach ($detalles as $d): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($d['nombre_producto']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            <?php echo htmlspecialchars($d['cantidad']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            $<?php echo number_format($d['precio_unitario'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                            $<?php echo number_format($d['subtotal'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        No se encontraron productos para esta venta.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </main>
</body>
</html>