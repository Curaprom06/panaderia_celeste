<?php
// reportes.php - Módulo de reportes para el Administrador

session_start();
// Asegúrate de que tu archivo 'conexion.php' está disponible
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
$tendencia_diaria = []; 
$podium_vendedores = []; // NUEVO: Datos para el podio de vendedores
$resumen_stats = [
    'totals' => 0,
    'total_transacciones' => 0,
    'total_items_vendidos' => 0,
    'promedio_venta' => 0,
    'total_ganancia' => 0 
];

// Función para formatear dinero
function format_money($number) {
    return '$' . number_format($number, 2, '.', ',');
}

// 2. Obtener y sanear filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Por defecto, el primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Por defecto, la fecha actual
$id_empleado_filtro = (int)($_GET['id_empleado'] ?? 0);

try {
    // 2.1. Cargar la lista de empleados para el filtro (Admin y Empleados)
    $sql_empleados = "SELECT id_usuario, nombre, apellido, rol FROM usuario WHERE rol = 'Empleado' OR rol = 'Administrador' ORDER BY nombre";
    $stmt_empleados = $pdo->query($sql_empleados);
    $empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

    // 3. Preparar la consulta base de Ventas
    $empleado_filtro_sql = '';
    $params = [
        'fecha_inicio' => $fecha_inicio . ' 00:00:00',
        'fecha_fin' => $fecha_fin . ' 23:59:59'
    ];

    if ($id_empleado_filtro > 0) {
        $empleado_filtro_sql = ' AND v.id_usuario = :id_empleado_filtro';
        $params['id_empleado_filtro'] = $id_empleado_filtro;
    }

    // 3.1. Obtener listado detallado de Ventas
    $sql_ventas = "
        SELECT 
            v.id_venta, v.fecha, v.total, 
            u.nombre AS nombre_empleado, u.apellido AS apellido_empleado 
        FROM venta v 
        JOIN usuario u ON v.id_usuario = u.id_usuario 
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
        " . $empleado_filtro_sql . " 
        ORDER BY v.fecha DESC
    ";
    $stmt_ventas = $pdo->prepare($sql_ventas);
    $stmt_ventas->execute($params);
    $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

    // 3.2. Obtener estadísticas de resumen (aplicando filtro de empleado)
    $sql_stats = "
        SELECT 
            SUM(v.total) AS totals,
            COUNT(DISTINCT v.id_venta) AS total_transacciones,
            SUM(dv.cantidad) AS total_items_vendidos
        FROM venta v
        JOIN detalle_venta dv ON v.id_venta = dv.id_venta
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
        " . $empleado_filtro_sql;
    
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    $resumen_stats['totals'] = (float)($stats['totals'] ?? 0);
    $resumen_stats['total_transacciones'] = (int)($stats['total_transacciones'] ?? 0);
    $resumen_stats['total_items_vendidos'] = (int)($stats['total_items_vendidos'] ?? 0);
    
    if ($resumen_stats['total_transacciones'] > 0) {
        $resumen_stats['promedio_venta'] = $resumen_stats['totals'] / $resumen_stats['total_transacciones'];
    }

    // 3.3. Obtener Total de Compras (Costo de Mercancía) y Ganancia (Lógica Condicional)
    $totals = 0; 
    $costo_aplicado_a_ganancia = 0; 

    // Solo calculamos y aplicamos el costo de compras si el filtro es GLOBAL
    if ($id_empleado_filtro == 0) {
        $sql_compras = "SELECT SUM(total) AS totals FROM compra WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin";
        $stmt_compras = $pdo->prepare($sql_compras);
        $stmt_compras->execute([
            'fecha_inicio' => $fecha_inicio . ' 00:00:00',
            'fecha_fin' => $fecha_fin . ' 23:59:59'
        ]);
        $compras_data = $stmt_compras->fetch(PDO::FETCH_ASSOC);
        $totals = (float)($compras_data['totals'] ?? 0);
        $costo_aplicado_a_ganancia = $totals;
    }
    
    $resumen_stats['total_ganancia'] = $resumen_stats['totals'] - $costo_aplicado_a_ganancia;


    // 3.4. Obtener Top Productos Vendidos (aplicando filtro de empleado)
    $sql_top_productos = "
        SELECT 
            p.nombre AS nombre_producto, 
            SUM(dv.cantidad) AS total_cantidad,
            SUM(dv.cantidad * dv.precio_unitario) AS total_ingreso
        FROM detalle_venta dv
        JOIN venta v ON dv.id_venta = v.id_venta
        JOIN producto p ON dv.id_producto = p.id_producto
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
        " . $empleado_filtro_sql . " 
        GROUP BY p.nombre
        ORDER BY total_ingreso DESC
        LIMIT 10
    ";
    $stmt_top_productos = $pdo->prepare($sql_top_productos);
    $stmt_top_productos->execute($params);
    $top_productos = $stmt_top_productos->fetchAll(PDO::FETCH_ASSOC);

    // 3.5. Obtener Ventas Agrupadas por Día para el Gráfico de Líneas (aplicando filtro de empleado)
    $sql_tendencia = "
        SELECT 
            DATE(v.fecha) AS dia, 
            SUM(v.total) AS total_diario
        FROM venta v 
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
        " . $empleado_filtro_sql . " 
        GROUP BY dia
        ORDER BY dia ASC
    ";
    $stmt_tendencia = $pdo->prepare($sql_tendencia);
    $stmt_tendencia->execute($params);
    $tendencia_diaria = $stmt_tendencia->fetchAll(PDO::FETCH_ASSOC);

    // 3.6. Obtener Podium de Vendedores (RANKING GLOBAL - Ignora $id_empleado_filtro)
    $sql_podium = "
        SELECT 
            u.nombre AS nombre_empleado, 
            u.apellido AS apellido_empleado,
            SUM(v.total) AS total_ventas
        FROM venta v 
        JOIN usuario u ON v.id_usuario = u.id_usuario 
        WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
        GROUP BY u.id_usuario, u.nombre, u.apellido
        ORDER BY total_ventas DESC
        LIMIT 5
    ";
    // Usamos solo los parámetros de fecha, ya que es un ranking GLOBAL.
    $stmt_podium = $pdo->prepare($sql_podium);
    $stmt_podium->execute([
        'fecha_inicio' => $fecha_inicio . ' 00:00:00',
        'fecha_fin' => $fecha_fin . ' 23:59:59'
    ]);
    $podium_vendedores = $stmt_podium->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    $error_msg = "Error de base de datos: " . $e->getMessage();
}

$nombre_empleado_filtrado = "Global";
if ($id_empleado_filtro > 0) {
    $found = array_filter($empleados, function($e) use ($id_empleado_filtro) {
        return $e['id_usuario'] == $id_empleado_filtro;
    });
    if (!empty($found)) {
        $e = reset($found);
        $nombre_empleado_filtrado = htmlspecialchars($e['nombre'] . ' ' . $e['apellido']);
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes - Panadería Celeste</title>
    <!-- Incluye Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Incluye Chart.js para los gráficos dinámicos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul oscuro */
            --color-secondary: #B8860B; /* Dorado oscuro */
        }
        body { font-family: 'Inter', sans-serif; }

/* ... código CSS anterior ... */

/* Estilos específicos para impresión */
@media print {
    /* Oculta la barra de navegación y los controles (filtros, botones) */
    header, 
    .bg-gray-50 > .max-w-7xl > .bg-white.p-6.rounded-xl.shadow-xl, /* Contenedor de Filtros */
    .print\\:hidden, /* Todos los elementos con esta clase (el botón de imprimir) */
    .max-h-\\[600px\\] { /* Deshabilita el scroll de la tabla de ventas para imprimir todo */
        display: none !important;
    }
    
    /* Restablece la visibilidad de la tabla de ventas, forzando todos los registros */
    .overflow-x-auto,
    .max-h-\\[600px\\] {
        max-height: none !important;
        overflow: visible !important;
    }

    /* Mejora la visualización del contenedor principal en impresión */
    .max-w-7xl,
    .max-w-7xl > .space-y-8 {
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}

    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Barra de Navegación -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-[var(--color-primary)]">Reporte de Ventas</h1>
            <a href="dashboard_admin.php" class="text-sm font-semibold text-gray-600 hover:text-gray-800 transition duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Volver al Panel
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6 space-y-8">
        
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>



        <!-- 2. Sección de Filtros -->
        <div class="bg-white p-6 rounded-xl shadow-xl">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Filtros del Reporte</h2>
            <form method="GET" action="reportes.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] p-2">
                </div>
                <div>
                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] p-2">
                </div>
                <div>
                    <label for="id_empleado" class="block text-sm font-medium text-gray-700">Filtrar por Empleado</label>
                    <select name="id_empleado" id="id_empleado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] p-2">
                        <option value="0" <?php echo $id_empleado_filtro == 0 ? 'selected' : ''; ?>>-- Global (Todos) --</option>
                        <?php foreach ($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_usuario']; ?>" 
                                <?php echo $id_empleado_filtro == $empleado['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido'] . ' (' . $empleado['rol'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                    Generar Reporte
                </button>
            </form>

<div class="flex space-x-4 mt-4 border-t pt-4">
    <button onclick="window.print()" class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 print:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 4v1h10V4a1 1 0 00-1-1H6a1 1 0 00-1 1zm-1 8h12a1 1 0 001-1V6a1 1 0 00-1-1H4a1 1 0 00-1 1v5a1 1 0 001 1zm2 3h8v-3H6v3zm-3 2h14v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4z" clip-rule="evenodd" />
        </svg>
        Imprimir Reporte
    </button>
    
    <a href="exportar_excel.php?fecha_inicio=<?php echo htmlspecialchars($fecha_inicio); ?>&fecha_fin=<?php echo htmlspecialchars($fecha_fin); ?>&id_empleado=<?php echo $id_empleado_filtro; ?>" 
       class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition duration-150 **print:hidden**">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v9.586l3.293-3.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0l-5-5a1 1 0 111.414-1.414L9 13.586V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Exportar a Excel
    </a>
</div>

        </div>

        <!-- Título del Reporte Filtrado -->
        <h2 class="text-2xl font-extrabold text-gray-900 pt-4 border-t border-gray-200">
            Reporte de Ventas para: <span class="text-[var(--color-primary)]"><?php echo $nombre_empleado_filtrado; ?></span>
        </h2>

        <!-- 3. Tarjetas de Resumen - LÓGICA CONDICIONAL -->
        <div class="grid grid-cols-2 <?php echo $id_empleado_filtro == 0 ? 'md:grid-cols-5' : 'md:grid-cols-4'; ?> gap-6">
            
            <!-- Tarjeta 1: Total Ventas -->
            <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-green-500 col-span-2 <?php echo $id_empleado_filtro == 0 ? 'md:col-span-1' : 'md:col-span-1'; ?>">
                <h3 class="text-lg font-semibold text-gray-700">Total Ingreso (Ventas)</h3>
                <p class="text-3xl font-bold text-green-600 mt-1"><?php echo format_money($resumen_stats['totals']); ?></p>
            </div>
            
            <?php if ($id_empleado_filtro == 0): ?>
                <!-- Tarjeta 2: Total Compras (Costo) - SOLO GLOBAL -->
                <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-red-500">
                    <h3 class="text-lg font-semibold text-gray-700">Total Compras (Costo)</h3>
                    <p class="text-3xl font-bold text-red-600 mt-1"><?php echo format_money($totals); ?></p>
                    <p class="text-xs text-gray-400 mt-2 font-semibold">Costo total aplicado al cálculo de ganancia.</p>
                </div>

                <!-- Tarjeta 3: Ganancia/Pérdida - SOLO GLOBAL -->
                <?php 
                    $ganancia_color = $resumen_stats['total_ganancia'] >= 0 ? 'text-blue-600 border-blue-500' : 'text-red-600 border-red-500';
                ?>
                <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 <?php echo $ganancia_color; ?>">
                    <h3 class="text-lg font-semibold text-gray-700">Ganancia/Pérdida</h3>
                    <p class="text-3xl font-bold mt-1 <?php echo $ganancia_color; ?>"><?php echo format_money($resumen_stats['total_ganancia']); ?></p>
                </div>
            <?php else: ?>
                <!-- Tarjeta Adicional: Items Vendidos - SOLO EMPLEADO -->
                <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-gray-700">Total Ítems Vendidos</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-1"><?php echo number_format($resumen_stats['total_items_vendidos']); ?></p>
                    <p class="text-xs text-gray-400 mt-2 font-semibold">Métrica clave de productividad.</p>
                </div>
            <?php endif; ?>

            <!-- Tarjeta Transacciones -->
            <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-yellow-500">
                <h3 class="text-lg font-semibold text-gray-700">Transacciones</h3>
                <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo number_format($resumen_stats['total_transacciones']); ?></p>
            </div>

            <!-- Tarjeta Promedio Venta -->
            <div class="bg-white p-5 rounded-xl shadow-lg border-b-4 border-indigo-500">
                <h3 class="text-lg font-semibold text-gray-700">Promedio Venta</h3>
                <p class="text-3xl font-bold text-indigo-600 mt-1"><?php echo format_money($resumen_stats['promedio_venta']); ?></p>
            </div>
        </div>

        <!-- 4. Sección de GRÁFICOS DINÁMICOS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-4">

            <!-- Gráfico 1: Tendencia de Ventas Diarias (Líneas) -->
            <div class="bg-white p-6 rounded-xl shadow-xl">
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Tendencia de Ingreso Diario</h3>
                <div class="h-80">
                    <canvas id="ventasDiariasChart"></canvas>
                </div>
            </div>

            <!-- Gráfico 2: Top 10 Productos (Barras) -->
            <div class="bg-white p-6 rounded-xl shadow-xl">
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Ingreso por Top 10 Productos</h3>
                <div class="h-80">
                    <canvas id="topProductosChart"></canvas>
                </div>
            </div>

        </div>

        <!-- 4.1. Sección de PODIUM DE VENDEDORES (NUEVO) -->
        <div class="bg-white p-6 rounded-xl shadow-xl mt-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">🏆 Podium Global de Vendedores (Ingreso Total)</h3>
            <div class="h-96">
                <canvas id="podiumVendedoresChart"></canvas>
            </div>
        </div>

        <!-- 5. Tablas Detalladas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pt-4">

            <!-- Columna 1: Detalle Top 10 Productos -->
            <div class="bg-white p-6 rounded-xl shadow-xl lg:col-span-1">
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Detalle Top 10 Productos</h3>
                <?php if (!empty($top_productos)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ingreso</th>
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

            <!-- Columna 2 y 3: Listado de Ventas -->
            <div class="bg-white p-6 rounded-xl shadow-xl lg:col-span-2">
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Detalle de Transacciones (<?php echo $resumen_stats['total_transacciones']; ?>)</h3>
                <?php if (!empty($ventas)): ?>
                <div class="overflow-x-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Venta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendedor</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($v['id_venta']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($v['nombre_empleado'] . ' ' . $v['apellido_empleado']); ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"><?php echo format_money($v['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="p-4 text-center text-gray-500">No hay transacciones de venta en el rango y filtros seleccionados.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- BLOQUE DE SCRIPT: Implementación de Chart.js -->
    <script>
        // 1. Prepara los datos PHP para JavaScript (JSON)
        const tendenciaDiariaData = <?php echo json_encode($tendencia_diaria); ?>;
        const topProductosData = <?php echo json_encode($top_productos); ?>;
        const podiumVendedoresData = <?php echo json_encode($podium_vendedores); ?>; // NUEVO: Datos para el podio


        // 2. FUNCIÓN PARA EL GRÁFICO DE LÍNEAS (Ventas Diarias)
        function crearVentasDiariasChart() {
            // Eliminar gráfico anterior si existe
            const chartDom = document.getElementById('ventasDiariasChart');
            if (chartDom && chartDom.chart) {
                chartDom.chart.destroy();
            }

            if (tendenciaDiariaData.length === 0) return;

            // Extraer etiquetas (días) y valores (totales)
            const labels = tendenciaDiariaData.map(item => item.dia.substring(5, 10).replace('-', '/')); // Formato MM/DD
            const data = tendenciaDiariaData.map(item => parseFloat(item.total_diario));

            const ctx = chartDom.getContext('2d');
            // Almacenar la instancia del gráfico en el elemento DOM
            chartDom.chart = new Chart(ctx, { 
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ingreso Diario (COP)',
                        data: data,
                        backgroundColor: 'rgba(30, 58, 138, 0.4)', // Color primario (Azul)
                        borderColor: 'rgba(30, 58, 138, 1)',
                        borderWidth: 2,
                        tension: 0.3, // Suaviza la línea
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '$' + context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Ingreso'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        }
                    }
                }
            });
        }


        // 3. FUNCIÓN PARA EL GRÁFICO DE BARRAS (Top Productos)
        function crearTopProductosChart() {
            // Eliminar gráfico anterior si existe
            const chartDom = document.getElementById('topProductosChart');
            if (chartDom && chartDom.chart) {
                chartDom.chart.destroy();
            }
            
            if (topProductosData.length === 0) return;
            
            // Extraer etiquetas (productos) y valores (ingresos)
            const labels = topProductosData.map(item => item.nombre_producto);
            const data = topProductosData.map(item => parseFloat(item.total_ingreso));

            const ctx = chartDom.getContext('2d');
            // Almacenar la instancia del gráfico en el elemento DOM
            chartDom.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ingreso Total Generado',
                        data: data,
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.7)', 
                            'rgba(16, 185, 129, 0.7)', 
                            'rgba(245, 158, 11, 0.7)', 
                            'rgba(239, 68, 68, 0.7)', 
                            'rgba(34, 197, 94, 0.7)', 
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(249, 115, 22, 0.7)',
                            'rgba(6, 182, 212, 0.7)',
                            'rgba(132, 204, 22, 0.7)',
                        ],
                        borderColor: [
                            'rgb(79, 70, 229)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)',
                            'rgb(34, 197, 94)',
                            'rgb(99, 102, 241)',
                            'rgb(139, 92, 246)',
                            'rgb(249, 115, 22)',
                            'rgb(6, 182, 212)',
                            'rgb(132, 204, 22)',
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Hace el gráfico horizontal para mejor lectura de nombres largos
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (context.parsed.x !== null) {
                                        label += ': $' + context.parsed.x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Ingreso'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Producto'
                            }
                        }
                    }
                }
            });
        }
        
        // 4. FUNCIÓN PARA EL GRÁFICO DE BARRAS (Podium Vendedores)
        function crearPodiumVendedoresChart() {
            // Eliminar gráfico anterior si existe
            const chartDom = document.getElementById('podiumVendedoresChart');
            if (chartDom && chartDom.chart) {
                chartDom.chart.destroy();
            }
            
            if (podiumVendedoresData.length === 0) return;
            
            const labels = podiumVendedoresData.map(item => item.nombre_empleado + ' ' + item.apellido_empleado);
            const data = podiumVendedoresData.map(item => parseFloat(item.total_ventas));

            const ctx = chartDom.getContext('2d');
            chartDom.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas Totales Generadas',
                        data: data,
                        backgroundColor: [
                            'rgba(255, 215, 0, 0.8)', // Oro (1er lugar)
                            'rgba(192, 192, 192, 0.8)', // Plata (2do lugar)
                            'rgba(205, 127, 50, 0.8)', // Bronce (3er lugar)
                            'rgba(30, 58, 138, 0.7)', // Azul (4to+)
                            'rgba(30, 58, 138, 0.7)', // Azul (4to+)
                        ],
                        borderColor: [
                            'rgb(255, 215, 0)',
                            'rgb(192, 192, 192)',
                            'rgb(205, 127, 50)',
                            'rgb(30, 58, 138)',
                            'rgb(30, 58, 138)',
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Gráfico horizontal para el ranking
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (context.parsed.x !== null) {
                                        label += ': $' + context.parsed.x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                    }
                                    return label;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Top 5 Empleados por Ingreso Generado'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Ingreso Generado'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        },
                        y: {
                            // Invertir el eje Y para que el 1er lugar esté arriba
                            reverse: false,
                            title: {
                                display: true,
                                text: 'Vendedor'
                            }
                        }
                    }
                }
            });
        }


        // 5. Inicializar todos los gráficos cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', () => {
            crearVentasDiariasChart();
            crearTopProductosChart();
            crearPodiumVendedoresChart(); // Llamada al nuevo gráfico
        });
    </script>
</body>
</html>