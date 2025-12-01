<?php
// ver_factura.php - Muestra los detalles de una venta y permite imprimirla

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// 2. Obtener y validar ID de Venta
$id_venta = (int)($_GET['id'] ?? 0);

if ($id_venta === 0) {
    die("Error: ID de venta no especificado.");
}

$venta_header = null;
$detalle_venta = [];
$error_msg = '';

// Función para formatear dinero
function format_money($number) {
    return '$' . number_format($number, 2, '.', ',');
}

try {
    // 3. Consulta Principal (Encabezado de la Venta)
    $sql_header = "
        SELECT 
            v.id_venta, 
            v.fecha, 
            v.total, 
            u.nombre AS nombre_empleado, 
            u.apellido AS apellido_empleado
        FROM venta v
        JOIN usuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_venta = ?
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([$id_venta]);
    $venta_header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$venta_header) {
        $error_msg = "No se encontró la venta con ID {$id_venta}.";
    } else {
        // 4. Consulta de Detalle (Productos vendidos)
        $sql_detalle = "
            SELECT 
                dv.cantidad, 
                dv.precio_unitario, 
                dv.subtotal, 
                p.nombre AS nombre_producto
            FROM detalle_venta dv
            JOIN producto p ON dv.id_producto = p.id_producto
            WHERE dv.id_venta = ?
            ORDER BY p.nombre
        ";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        $stmt_detalle->execute([$id_venta]);
        $detalle_venta = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_msg = "Error de base de datos: " . $e->getMessage();
}

if ($error_msg) {
    die("<h1>{$error_msg}</h1><a href='reportes.php'>Volver a Reportes</a>");
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura N° <?php echo $id_venta; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        /* Estilos generales (Web View) */
        :root {
            --color-primary: #1E3A8A; /* Azul Oscuro */
            --color-accent: #10B981; /* Verde esmeralda */
        }
        .invoice-container {
            max-width: 800px;
        }

        /* Estilos para impresión (Print View) */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
            }
            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100%;
            }
            /* Ocultar botones y encabezados innecesarios */
            .no-print {
                display: none !important;
            }
            .border-invoice {
                border-color: #000 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-200 font-sans p-4">

    <!-- Botones de Acción (Visible solo en pantalla, no en impresión) -->
    <div class="no-print flex justify-end mb-4 invoice-container mx-auto">
        <a href="historial_ventas.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-150 mr-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <button onclick="window.print()" class="bg-[var(--color-accent)] hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-150">
            <i class="fas fa-print mr-1"></i> Imprimir Factura
        </button>
    </div>
    
    <!-- Contenedor de la Factura -->
    <div class="invoice-container mx-auto bg-white p-6 sm:p-10 rounded-lg shadow-xl border border-gray-300 border-invoice">
        
        <!-- Encabezado de la Factura -->
        <div class="flex justify-between items-start border-b-4 border-dashed pb-4 mb-6 border-gray-300">
            <div>
                <h1 class="text-4xl font-extrabold text-[var(--color-primary)]">PANADERÍA CELESTE</h1>
                <p class="text-gray-600 mt-1">Recibo de Venta Oficial</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-700 font-semibold">N° FACTURA:</p>
                <p class="text-3xl font-extrabold text-[var(--color-accent)]">#<?php echo $venta_header['id_venta']; ?></p>
            </div>
        </div>

        <!-- Información de la Transacción -->
        <div class="flex justify-between text-sm mb-8">
            <div class="space-y-1">
                <p><strong>Fecha de Venta:</strong> <?php echo date("d/m/Y H:i:s", strtotime($venta_header['fecha'])); ?></p>
                <p><strong>Atendido por:</strong> <?php echo htmlspecialchars("{$venta_header['nombre_empleado']} {$venta_header['apellido_empleado']}"); ?></p>
            </div>
            <div>
                <p class="font-bold text-gray-700 text-right">Gracias por su compra.</p>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-300 text-sm font-semibold text-gray-600 uppercase">
                        <th class="py-2 px-4">Producto</th>
                        <th class="py-2 px-4 text-center">Cant.</th>
                        <th class="py-2 px-4 text-right">Precio Unitario</th>
                        <th class="py-2 px-4 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle_venta as $item): ?>
                    <tr class="border-b border-gray-200 text-gray-800">
                        <td class="py-2 px-4"><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo number_format($item['cantidad']); ?></td>
                        <td class="py-2 px-4 text-right"><?php echo format_money($item['precio_unitario']); ?></td>
                        <td class="py-2 px-4 text-right"><?php echo format_money($item['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen de Totales -->
        <div class="flex justify-end">
            <div class="w-full sm:w-1/2 space-y-2">
                <div class="flex justify-between font-semibold text-gray-700 border-t pt-2">
                    <span>Total (IVA Incluido):</span>
                    <span><?php echo format_money($venta_header['total']); ?></span>
                </div>
                <div class="flex justify-between font-extrabold text-2xl text-[var(--color-primary)]">
                    <span>TOTAL PAGADO:</span>
                    <span><?php echo format_money($venta_header['total']); ?></span>
                </div>
            </div>
        </div>

        <!-- Pie de página / Mensaje Final -->
        <div class="mt-10 pt-4 border-t border-gray-300 text-center text-xs text-gray-500">
            <p>Este es un recibo generado por el sistema de Punto de Venta.</p>
            <p>Para reclamos o consultas, por favor contacte a la administración local.</p>
        </div>

    </div>

    <script>
        // Función para forzar la ventana de impresión al cargar la página (solo si se accede por URL)
        document.addEventListener('DOMContentLoaded', () => {
            // Se puede agregar un temporizador si se desea asegurar que el DOM esté completamente renderizado
            window.print();
        });
    </script>
</body>
</html>