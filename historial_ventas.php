<?php
// historial_ventas.php

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = $_GET['error'] ?? null;
$success_msg = $_GET['success'] ?? null;

// --- Lógica de Filtros ---
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual por defecto
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy por defecto
$id_usuario = $_GET['id_usuario'] ?? 'todos';

$sql_condiciones = ['v.fecha >= :fecha_inicio', 'v.fecha <= :fecha_fin_siguiente'];
$params = [
    ':fecha_inicio' => $fecha_inicio . ' 00:00:00',
    // Incluir ventas hasta el final del día de la fecha fin
    ':fecha_fin_siguiente' => date('Y-m-d H:i:s', strtotime($fecha_fin . ' +1 day'))
];

if ($id_usuario !== 'todos') {
    $sql_condiciones[] = 'v.id_usuario = :id_usuario';
    $params[':id_usuario'] = $id_usuario;
}
$condiciones = 'WHERE ' . implode(' AND ', $sql_condiciones);
// --- Fin Lógica de Filtros ---

// Obtener la lista de ventas
$ventas = [];
try {
    // Consulta SQL con JOIN para obtener el nombre de usuario del empleado y aplicar filtros
    $sql_ventas = "
        SELECT 
            v.id_venta, 
            v.total, 
            v.fecha, 
            u.usuario AS nombre_empleado,
            u.id_usuario
        FROM venta v
        JOIN usuario u ON v.id_usuario = u.id_usuario
        {$condiciones}
        ORDER BY v.fecha DESC
    ";
    $stmt_ventas = $pdo->prepare($sql_ventas);
    $stmt_ventas->execute($params);
    $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener listado de empleados para el filtro
    $sql_usuarios = "SELECT id_usuario, usuario FROM usuario ORDER BY usuario";
    $stmt_usuarios = $pdo->query($sql_usuarios);
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_msg = "Error al cargar el historial de ventas: " . $e->getMessage();
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial de Ventas - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul marino */
            --color-secondary: #B8860B; /* Dorado */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="text-white text-2xl font-bold">
                Historial de Facturas
            </div>
            <div>
                <a href="dashboard_admin.php" class="text-white hover:text-[var(--color-secondary)] transition duration-150 mr-4">
                    Dashboard
                </a>
                <a href="logout.php" class="text-white bg-red-600 hover:bg-red-700 px-3 py-2 rounded-lg transition duration-150">
                    Cerrar Sesión
                </a>
            </div>
        </nav>
    </header>

  <main class="container mx-auto px-4 py-8">

    <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">Revise aquí su historial de facturas </h1>

    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Filtros de Búsqueda</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="col-span-1">
                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
            </div>
            <div class="col-span-1">
                <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
            </div>
            <div class="col-span-1">
                <label for="id_usuario" class="block text-sm font-medium text-gray-700">Vendedor</label>
                <select name="id_usuario" id="id_usuario" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                    <option value="todos">Todos los Vendedores</option>
                    <?php foreach ($usuarios as $user): ?>
                        <option value="<?php echo $user['id_usuario']; ?>" 
                            <?php echo $id_usuario == $user['id_usuario'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['usuario']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-1">
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded-md hover:bg-indigo-700 transition">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
            </div>
            <div class="col-span-1">
                <a href="exportar_ventas.php?<?php echo http_build_query($_GET); ?>" 
                   class="w-full inline-block text-center bg-green-600 text-white font-bold py-2 px-4 rounded-md hover:bg-green-700 transition">
                   <i class="fas fa-file-excel mr-2"></i> Exportar
                </a>
            </div>
        </form>
    </div>
    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
            

            <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># Factura</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        
            
            
            <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($ventas) > 0): ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($v['id_venta']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?></td>
                                

                                <td class="px-4 py-2 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                    $<?php echo number_format($v['total'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="ver_factura.php?id=<?php echo $v['id_venta']; ?>" target="_blank"
                                       class="text-white px-3 py-1 rounded bg-[var(--color-secondary)] hover:bg-yellow-700 transition duration-150">
                                        <i class="fas fa-eye mr-1"></i> Ver/Imprimir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No se encontraron ventas registradas para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</body>
</html>