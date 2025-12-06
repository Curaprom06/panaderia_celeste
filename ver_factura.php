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
    // Para tiquete, a menudo se usa solo el símbolo y el separador de miles
    return '$' . number_format($number, 0, '.', ','); 
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

        /* ---------------------------------------------------------------------- */
        /* 🚀 ESTILOS PARA IMPRESIÓN POS (80mm) 🚀 */
        /* ---------------------------------------------------------------------- */
        @media print {
            /* 💥 CAMBIO CRUCIAL: Ancho del papel para Tiquete POS (80mm) */
            body, .invoice-container {
                width: 80mm; /* Ancho estándar de rollo de recibo */
                margin: 0 auto !important; /* Elimina márgenes de impresión */
                padding: 0;
                box-shadow: none !important;
                border: none !important;
                font-size: 10pt; /* Fuente más pequeña para tiquete */
                font-family: 'Consolas', 'Courier New', monospace; /* Fuente monoespaciada */
            }

            /* Configuración del Contenedor */
            .invoice-container {
                max-width: 80mm !important;
                padding: 0 4mm !important; /* Relleno lateral mínimo */
            }
            
            /* Ocultar elementos innecesarios */
            .no-print {
                display: none !important;
            }
            
            /* Asegurar que todos los textos y fondos sean monocromáticos */
            * {
                color: #000 !important;
                background-color: transparent !important;
            }
            
            /* Reducir y centrar el encabezado principal */
            h1 {
                font-size: 1.2em !important; 
                font-weight: bold !important;
                text-align: center !important;
            }
            
            /* Estilos de tabla optimizados para 80mm */
            table {
                width: 100%;
                font-size: 9pt;
            }
            thead tr {
                border-bottom: 1px dashed #000; /* Separación de línea punteada */
            }
            th, td {
                padding: 1px 0;
                line-height: 1.2;
            }
            /* Forzar que los layouts flexibles se conviertan en bloques apilados (centrados) */
            .flex {
                display: block !important; 
                text-align: center;
            }
            .justify-between, .justify-end {
                text-align: center; /* Centra el contenido del div, si aplica */
            }
            .text-right {
                text-align: right !important;
            }
            .text-left {
                text-align: left !important;
            }
            
        }
    </style>
</head>
<body class="bg-gray-200 font-sans p-4">

    <div class="no-print flex justify-end mb-4 invoice-container mx-auto">
        <a href="historial_ventas.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-150 mr-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <button onclick="window.print()" class="bg-[var(--color-accent)] hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-150">
            <i class="fas fa-print mr-1"></i> Imprimir Factura
        </button>
    </div>
    
    <div class="invoice-container mx-auto bg-white p-6 sm:p-10 rounded-lg shadow-xl border border-gray-300 border-invoice">
        
        <div class="text-center pb-2 mb-4 border-b-2 border-dashed border-gray-300">
            <h1 class="font-extrabold text-[var(--color-primary)]">PANADERÍA CELESTE</h1>
            
            <p class="text-xs text-gray-600">NIT 901,222,111-1 | Documento Equivalente</p>
            <p class="text-xs text-gray-600">Cra 2 #5-16 Brr La Gaviota</p>
            <p class="text-xs text-gray-600">IBAGUÉ-TOLIMA-COLOMBIA</p>
            <p class="text-xs text-gray-600">Tel: 2712255 | panceleste@gmail.com</p>
            <p class="text-xs text-gray-600 mt-1 font-bold">No responsables de iva - Act. Económica 1081</p>
        </div>

        <div class="text-sm mb-4">
            <p class="text-left"><strong>FACTURA N°:</strong> #<?php echo $venta_header['id_venta']; ?></p>
            <p class="text-left"><strong>Fecha:</strong> <?php echo date("d/m/Y H:i", strtotime($venta_header['fecha'])); ?></p>
            <p class="text-left"><strong>Cajero:</strong> <?php echo htmlspecialchars("{$venta_header['nombre_empleado']} {$venta_header['apellido_empleado']}"); ?></p>
            <p class="text-center mt-2 font-bold border-t border-b border-dashed py-1">DETALLE DE LA VENTA</p>
        </div>

        <div class="mb-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-sm font-bold text-gray-900 uppercase">
                        <th class="py-1 text-left">Producto</th>
                        <th class="py-1 text-center">Precio Unitario</th>
                        <th class="py-1 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle_venta as $item): ?>
                    <tr class="text-gray-800">
                        <td class="py-0 text-left"><?php echo number_format($item['cantidad']) . " x " . htmlspecialchars($item['nombre_producto']); ?></td>
                        <td class="py-0 text-center"><?php echo format_money($item['precio_unitario']); ?></td>
                        <td class="py-0 text-right font-bold"><?php echo format_money($item['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-right border-t border-dashed pt-2">
            <div class="flex justify-end mb-1">
                <span class="font-bold text-lg mr-2">TOTAL A PAGAR:</span>
                <span class="font-extrabold text-2xl text-[var(--color-primary)]"><?php echo format_money($venta_header['total']); ?></span>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-dashed text-center text-xs text-gray-600">
            <p class="font-bold">¡GRACIAS POR SU COMPRA!</p>
            <p>Este es un recibo no fiscal generado por el sistema POS.</p>
            <p>Para reclamos o consultas, por favor contacte a la administración local.</p>

        </div>

    </div>

    <script>
        // Función para forzar la ventana de impresión al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            window.print();
        });
    </script>
</body>
</html>