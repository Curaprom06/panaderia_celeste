<?php
// exportar_ventas.php - Genera un archivo CSV con el detalle de las ventas

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores pueden acceder
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

// 2. Obtener y validar Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$id_usuario = $_GET['id_usuario'] ?? 'todos';

$sql_condiciones = ['v.fecha >= :fecha_inicio', 'v.fecha <= :fecha_fin_siguiente'];
$params = [
    ':fecha_inicio' => $fecha_inicio . ' 00:00:00',
    ':fecha_fin_siguiente' => date('Y-m-d H:i:s', strtotime($fecha_fin . ' +1 day'))
];

if ($id_usuario !== 'todos') {
    $sql_condiciones[] = 'v.id_usuario = :id_usuario';
    $params[':id_usuario'] = $id_usuario;
}

$condiciones = 'WHERE ' . implode(' AND ', $sql_condiciones);

// 3. Consulta de DETALLE DE VENTA
$sql_detalle = "
    SELECT 
        v.id_venta, 
        v.fecha, 
        u.usuario AS vendedor,
        p.nombre AS nombre_producto,
        dv.cantidad,
        dv.precio_unitario,
        dv.subtotal
    FROM venta v
    JOIN usuario u ON v.id_usuario = u.id_usuario
    JOIN detalle_venta dv ON v.id_venta = dv.id_venta
    JOIN producto p ON dv.id_producto = p.id_producto
    {$condiciones}
    ORDER BY v.fecha DESC, v.id_venta
";

$stmt_detalle = $pdo->prepare($sql_detalle);
$stmt_detalle->execute($params);
$detalles = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

// 4. Configuración de Headers para la descarga de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Reporte_Ventas_'.date('Ymd').'.csv"');

// 5. Salida del Archivo CSV
$output = fopen('php://output', 'w');

// Escribir encabezados
fputcsv($output, [
    'ID Venta', 'Fecha', 'Hora', 'Vendedor', 'Producto', 
    'Cantidad', 'Precio Unitario', 'Subtotal Linea'
], ';'); // Usamos punto y coma (;) como separador para compatibilidad con Excel

// Escribir datos
foreach ($detalles as $row) {
    $fecha_obj = new DateTime($row['fecha']);
    
    // Crear la fila para el CSV
    fputcsv($output, [
        $row['id_venta'],
        $fecha_obj->format('Y-m-d'),
        $fecha_obj->format('H:i:s'),
        $row['vendedor'],
        $row['nombre_producto'],
        $row['cantidad'],
        // Usar punto como separador decimal para Excel
        number_format($row['precio_unitario'], 2, '.', ''), 
        number_format($row['subtotal'], 2, '.', '')
    ], ';');
}

fclose($output);
exit;
?>