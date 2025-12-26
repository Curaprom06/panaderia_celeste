<?php
// reportes.php - Módulo de reportes para el Administrador

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = '';
$ventas = [];
$empleados = [];
$top_productos = [];
$top_empleados = []; // Variable para el reporte de empleados
$resumen_stats = [
    'total_ingreso' => 0, // Ingreso Total (suma de total)
    'total_transacciones' => 0,
    'total_items_vendidos' => 0,
    'promedio_venta' => 0
];

// Función para formatear dinero
function format_money($number) {
    // Usamos el símbolo '$' para una visualización estándar
    return '$' . number_format($number, 2, '.', ',');
}

// 2. Obtener y sanear filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Por defecto, el primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Por defecto, la fecha actual
$id_empleado_filtro = (int)($_GET['id_empleado'] ?? 0);

// Comprobación simple de formato de fechas
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_inicio) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_fin)) {
    $error_msg = "Error: El formato de la fecha es incorrecto.";
}

try {
    // 2.1. Cargar la lista de empleados para el filtro (Admin y Empleados)
    $sql_empleados = "SELECT id_usuario, nombre, apellido FROM usuario WHERE rol = 'Empleado' OR rol = 'Administrador' ORDER BY nombre";
    $stmt_empleados = $pdo->query($sql_empleados);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    // 2.2. Definir el filtro de empleado para las consultas SQL
    $empleado_filter_sql = '';
    $empleado_filter_bind = [];
    if ($id_empleado_filtro > 0) {
        $empleado_filter_sql = ' AND v.id_usuario = :id_empleado_filtro';
        $empleado_filter_bind[':id_empleado_filtro'] = $id_empleado_filtro;
    }

    // 3. Consulta de Ventas y Resumen Estadístico
    if (empty($error_msg)) {

        // 3.1. Resumen Estadístico y Lista de Ventas
        $sql_ventas = "
            SELECT 
                v.id_venta, 
                v.fecha, 
                v.total, 
                u.nombre AS nombre_empleado, 
                u.apellido AS apellido_empleado
            FROM venta v
            JOIN usuario u ON v.id_usuario = u.id_usuario
            WHERE v.fecha BETWEEN :start_date AND :end_date
            " . $empleado_filter_sql . "
            ORDER BY v.fecha DESC
        ";

        $stmt_ventas = $pdo->prepare($sql_ventas);
        $stmt_ventas->bindParam(':start_date', $fecha_inicio);
        $stmt_ventas->bindParam(':end_date', $fecha_fin);
        
        foreach ($empleado_filter_bind as $key => $value) {
            $stmt_ventas->bindValue($key, $value);
        }
        
        $stmt_ventas->execute();
        $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

        // Calcular Resumen de Estadísticas
        $total_ingreso = array_sum(array_column($ventas, 'total'));
        $total_transacciones = count($ventas);

        if ($total_transacciones > 0) {
            $sql_items = "
                SELECT SUM(dv.cantidad) AS total_items
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                WHERE v.fecha BETWEEN :start_date AND :end_date
                " . $empleado_filter_sql . "
            ";
            $stmt_items = $pdo->prepare($sql_items);
            $stmt_items->bindParam(':start_date', $fecha_inicio);
            $stmt_items->bindParam(':end_date', $fecha_fin);
            foreach ($empleado_filter_bind as $key => $value) {
                $stmt_items->bindValue($key, $value);
            }
            $stmt_items->execute();
            $total_items_vendidos = $stmt_items->fetchColumn();

            $resumen_stats['total_ingreso'] = $total_ingreso;
            $resumen_stats['total_transacciones'] = $total_transacciones;
            $resumen_stats['total_items_vendidos'] = $total_items_vendidos;
            $resumen_stats['promedio_venta'] = $total_ingreso / $total_transacciones;
        }


        // 3.2. Top Empleados (Ingreso)
        $sql_top_empleados = "
            SELECT 
                u.nombre, 
                u.apellido,
                SUM(v.total) AS total_ingreso,
                COUNT(v.id_venta) AS total_transacciones
            FROM venta v
            JOIN usuario u ON v.id_usuario = u.id_usuario
            WHERE v.fecha BETWEEN :start_date AND :end_date
            GROUP BY u.id_usuario, u.nombre, u.apellido
            ORDER BY total_ingreso DESC 
            LIMIT 5";

        $stmt_top_empleados = $pdo->prepare($sql_top_empleados);
        $stmt_top_empleados->bindParam(':start_date', $fecha_inicio);
        $stmt_top_empleados->bindParam(':end_date', $fecha_fin);
        $stmt_top_empleados->execute();
        $top_empleados = $stmt_top_empleados->fetchAll(PDO::FETCH_ASSOC);


        // 3.3. Top Productos/Insumos más vendidos (Cantidad y Ingreso) - LO SOLICITADO
        $sql_top_productos = "
            SELECT 
                pd.nombre AS nombre_producto, 
                SUM(dv.cantidad) AS total_cantidad, 
                SUM(dv.cantidad * dv.precio_unitario) AS total_ingreso 
            FROM detalle_venta dv
            JOIN producto pd ON dv.id_producto = pd.id_producto
            JOIN venta v ON dv.id_venta = v.id_venta
            WHERE v.fecha BETWEEN :start_date AND :end_date
            " . $empleado_filter_sql . "
            GROUP BY pd.id_producto, pd.nombre
            ORDER BY total_cantidad DESC 
            LIMIT 5";

        $stmt_top_productos = $pdo->prepare($sql_top_productos);
        $stmt_top_productos->bindParam(':start_date', $fecha_inicio);
        $stmt_top_productos->bindParam(':end_date', $fecha_fin);

        // Vincular el filtro de empleado si existe
        foreach ($empleado_filter_bind as $key => $value) {
            $stmt_top_productos->bindValue($key, $value);
        }

        $stmt_top_productos->execute();
        $top_productos = $stmt_top_productos->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_msg = "Error de base de datos: " . $e->getMessage();
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes de Ventas - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul Oscuro */
            --color-secondary: #B8860B; /* Dorado */
        }
        .text-primary { color: var(--color-primary); }
        .bg-primary { background-color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <header class="bg-primary text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Panel de Reportes de Ventas</h1>
            <a href="dashboard_admin.php" class="bg-white text-primary px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition duration-300 shadow-md">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
            </a>
        </div>
    </header>

    <main class="container mx-auto p-4 md:p-8">

        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-6" role="alert">
                <strong class="font-bold">¡Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario de Filtros -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-200">
            <h2 class="text-xl font-semibold mb-4 text-primary"><i class="fas fa-filter mr-2"></i> Filtrar Reporte</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label for="id_empleado" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Empleado:</label>
                    <select id="id_empleado" name="id_empleado" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <option value="0">Todos los Empleados</option>
                        <?php foreach ($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_usuario']; ?>" 
                                <?php echo $id_empleado_filtro == $empleado['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition duration-300 shadow-md">
                        <i class="fas fa-search mr-2"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>


        <!-- Resumen de Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-xl border-l-4 border-primary">
                <p class="text-sm font-medium text-gray-500">Ingreso Total</p>
                <p class="text-3xl font-extrabold text-primary"><?php echo format_money($resumen_stats['total_ingreso']); ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-xl border-l-4 border-yellow-600">
                <p class="text-sm font-medium text-gray-500">Transacciones</p>
                <p class="text-3xl font-extrabold text-yellow-600"><?php echo number_format($resumen_stats['total_transacciones']); ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-xl border-l-4 border-green-600">
                <p class="text-sm font-medium text-gray-500">Items Vendidos</p>
                <p class="text-3xl font-extrabold text-green-600"><?php echo number_format($resumen_stats['total_items_vendidos']); ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-xl border-l-4 border-purple-600">
                <p class="text-sm font-medium text-gray-500">Venta Promedio</p>
                <p class="text-3xl font-extrabold text-purple-600"><?php echo format_money($resumen_stats['promedio_venta']); ?></p>
            </div>
        </div>

        <!-- Secciones de Reporte (Top Empleados y Top Productos/Insumos) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Top Empleados (Ingreso) -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200" id="top-empleados">
                <h2 class="text-xl font-semibold p-4 border-b text-primary">
                    <i class="fas fa-users mr-2"></i> Top 5 Empleados (por Ingreso)
                </h2>
                <?php if (!empty($top_empleados)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transacciones</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ingreso Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top_empleados as $te): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($te['nombre'] . ' ' . $te['apellido']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo number_format($te['total_transacciones']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"><?php echo format_money($te['total_ingreso']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="p-4 text-center text-gray-500">No hay datos de empleados en el rango de fechas seleccionado.</p>
                <?php endif; ?>
            </div>

            <!-- Top Productos/Insumos Vendidos (Cantidad) -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200" id="top-productos">
                <h2 class="text-xl font-semibold p-4 border-b text-primary">
                    <i class="fas fa-box-open mr-2"></i> Top 5 Productos/Insumos Vendidos
                </h2>
                <?php if (!empty($top_productos)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Insumo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ingreso Generado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top_productos as $tp): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($tp['nombre_producto']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo number_format($tp['total_cantidad']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"><?php echo format_money($tp['total_ingreso']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="p-4 text-center text-gray-500">No hay datos de productos vendidos en el rango y filtros seleccionados.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- Tabla de Detalle de Ventas -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 mt-8">
            <h2 class="text-xl font-semibold p-4 border-b text-primary"><i class="fas fa-list-ul mr-2"></i> Detalle de Ventas (Transacciones)</h2>
            <?php if (!empty($ventas)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Venta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($venta['id_venta']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime(htmlspecialchars($venta['fecha']))); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($venta['nombre_empleado'] . ' ' . $venta['apellido_empleado']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"><?php echo format_money($venta['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="p-4 text-center text-gray-500">No hay transacciones de venta registradas en el rango y filtros seleccionados.</p>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>