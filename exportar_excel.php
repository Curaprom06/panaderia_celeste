<?php
// exportar_excel.php - Script para exportar datos detallados a un archivo CSV (Excel)

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    http_response_code(403);
    die("Acceso denegado.");
}

// 2. Obtener y sanear filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$id_empleado_filtro = (int)($_GET['id_empleado'] ?? 0);

// 3. Preparar la consulta SQL
$empleado_filtro_sql = '';
$params = [
    'fecha_inicio' => $fecha_inicio . ' 00:00:00',
    'fecha_fin' => $fecha_fin . ' 23:59:59'
];

if ($id_empleado_filtro > 0) {
    $empleado_filtro_sql = ' AND v.id_usuario = :id_empleado_filtro';
    $params['id_empleado_filtro'] = $id_empleado_filtro;
}

// Consulta DETALLADA para la exportación
$sql_export = "
    SELECT 
        v.id_venta, 
        v.fecha, 
        v.total, 
        u.nombre AS nombre_empleado, 
        u.apellido AS apellido_empleado,
        p.nombre AS nombre_producto,
        dv.cantidad,
        dv.precio_unitario,
        (dv.cantidad * dv.precio_unitario) AS subtotal_linea
    FROM venta v 
    JOIN detalle_venta dv ON v.id_venta = dv.id_venta
    JOIN producto p ON dv.id_producto = p.id_producto
    JOIN usuario u ON v.id_usuario = u.id_usuario 
    WHERE v.fecha BETWEEN :fecha_inicio AND :fecha_fin
    " . $empleado_filtro_sql . " 
    ORDER BY v.id_venta DESC, v.fecha DESC
";

try {
    $stmt_export = $pdo->prepare($sql_export);
    $stmt_export->execute($params);
    $data = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

    // 4. Configurar Cabeceras para Descarga de CSV
    $filename = "Reporte_Ventas_Panaderia_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 5. Crear el archivo CSV y escribir los datos
    $output = fopen('php://output', 'w');

    // Nombres de las columnas (Cabecera)
    fputcsv($output, [
        'ID Venta', 
        'Fecha y Hora', 
        'Total Venta (COP)', 
        'Vendedor Nombre', 
        'Vendedor Apellido', 
        'Producto', 
        'Cantidad', 
        'Precio Unitario (COP)', 
        'Subtotal Linea (COP)'
    ], ';'); // Usamos el punto y coma (;) como separador para compatibilidad con Excel

    // Escribir las filas de datos
    foreach ($data as $row) {
        // Aseguramos que los valores numéricos usen punto para decimal y no coma
        $export_row = [
            $row['id_venta'],
            $row['fecha'],
            number_format((float)$row['total'], 2, '.', ''), // Formateo de Total
            $row['nombre_empleado'],
            $row['apellido_empleado'],
            $row['nombre_producto'],
            (int)$row['cantidad'],
            number_format((float)$row['precio_unitario'], 2, '.', ''), // Formateo de Precio Unitario
            number_format((float)$row['subtotal_linea'], 2, '.', '') // Formateo de Subtotal
        ];
        fputcsv($output, $export_row, ';');
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}
?>