<?php
// gestion_compras.php - Módulo de Registro y Gestión de Compras

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Solo Administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'Administrador') {
    header('Location: index.php');
    exit;
}

$error_msg = '';
$success_msg = '';
$compras = [];
$proveedores = [];
$productos = [];
$id_usuario_actual = $_SESSION['id_usuario'] ?? 0;

// Función para formatear dinero
function format_money($number) {
    return '$' . number_format($number, 2, '.', ',');
}

try {
    // 2. Cargar datos necesarios para el formulario de compra
    
    // 2.1. Proveedores
    $sql_proveedores = "SELECT id_proveedor, nombre FROM proveedor ORDER BY nombre ASC";
    $stmt_proveedores = $pdo->query($sql_proveedores);
    $proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

    // 2.2. Productos (Insumos) - Usamos todos, activos o inactivos, pues podemos comprar insumos que aún no están a la venta.
    $sql_productos = "SELECT id_producto, nombre, precio, stock FROM producto ORDER BY nombre ASC";
    $stmt_productos = $pdo->query($sql_productos);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener el historial de Compras (solo encabezados)
    $sql_compras = "
        SELECT 
            c.id_compra, c.fecha, c.total, 
            p.nombre AS nombre_proveedor, 
            u.nombre AS nombre_usuario
        FROM compra c
        JOIN proveedor p ON c.id_proveedor = p.id_proveedor
        JOIN usuario u ON c.id_usuario = u.id_usuario
        ORDER BY c.fecha DESC, c.id_compra DESC
        LIMIT 50
    ";
    $stmt_compras = $pdo->query($sql_compras);
    $compras = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Error al cargar datos iniciales: " . $e->getMessage();
}

// 4. Manejo de Petición POST para Registrar Compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'registrar_compra') {
    
    // 4.1. Sanear datos de la compra
    $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);
    $total = (float)($_POST['total'] ?? 0);
    $detalle_json = $_POST['detalle_compra'] ?? '[]';
    $detalle_compra = json_decode($detalle_json, true);

    if ($id_proveedor <= 0 || $total <= 0 || empty($detalle_compra)) {
        $error_msg = "Faltan datos obligatorios o el total es inválido para registrar la compra.";
    } else {
        try {
            $pdo->beginTransaction();

            // A. Insertar Encabezado de la Compra (tabla compra)
            // Campos: id_compra, fecha, total, id_proveedor, id_usuario
            $sql_compra = "INSERT INTO compra (fecha, total, id_proveedor, id_usuario) 
                           VALUES (NOW(), ?, ?, ?)";
            $stmt_compra = $pdo->prepare($sql_compra);
            $stmt_compra->execute([$total, $id_proveedor, $id_usuario_actual]);
            $id_compra = $pdo->lastInsertId();

            // B. Iterar sobre el detalle para insertar y actualizar stock
            $sql_detalle = "INSERT INTO detalle_compra (id_compra, id_producto, cantidad, precio_unitario, subtotal) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $pdo->prepare($sql_detalle);

            $sql_stock_update = "UPDATE producto SET stock = stock + ? WHERE id_producto = ?";
            $stmt_stock_update = $pdo->prepare($sql_stock_update);

            foreach ($detalle_compra as $item) {
                $id_producto = (int)$item['id_producto'];
                $cantidad = (int)$item['cantidad'];
                $precio_unitario = (float)$item['precio_unitario'];
                $subtotal = $cantidad * $precio_unitario;

                if ($id_producto > 0 && $cantidad > 0 && $precio_unitario >= 0) {
                    // 1. Insertar Detalle
                    $stmt_detalle->execute([$id_compra, $id_producto, $cantidad, $precio_unitario, $subtotal]);

                    // 2. Actualizar Stock del Producto
                    $stmt_stock_update->execute([$cantidad, $id_producto]);
                }
            }

            $pdo->commit();
            
            // Redirigir con mensaje de éxito
            header('Location: gestion_compras.php?msg=' . urlencode("Compra #{$id_compra} registrada y stock actualizado exitosamente."));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Error al registrar la compra: " . $e->getMessage();
        }
    }
}

// Mostrar mensaje de éxito si viene de la redirección
if (isset($_GET['msg'])) {
    $success_msg = htmlspecialchars($_GET['msg']);
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Compras - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; /* Azul Oscuro */
            --color-secondary: #B8860B; /* Dorado */
        }
        .modal {
            display: none;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de Compras
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <a href="dashboard_admin.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Panel
                </a>
                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <!-- Mensajes de Estado -->
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Éxito:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DE REGISTRO DE NUEVA COMPRA -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between items-center">
                <span><i class="fas fa-file-invoice-dollar mr-2"></i> Registrar Nueva Compra</span>
                <button onclick="openModal('registerModal')"
                        class="bg-[var(--color-secondary)] text-white py-1 px-3 rounded-lg shadow-md hover:bg-yellow-700 transition duration-300 font-semibold text-sm">
                    Abrir Formulario
                </button>
            </h2>
            <p class="text-gray-600">Utiliza este formulario para registrar la factura de compra de insumos y actualizar el inventario automáticamente.</p>
        </div>


        <!-- SECCIÓN DE HISTORIAL DE COMPRAS -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Historial de Últimas Compras</h2>
            
            <?php if (!empty($compras)): ?>
            <div class="table-responsive">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Compra</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrado Por</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                           
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($compras as $c): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($c['id_compra']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($c['fecha'])); ?></td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($c['nombre_proveedor']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($c['nombre_usuario']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right"><?php echo format_money($c['total']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-center">
                               
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="p-4 text-center text-gray-500">No hay compras registradas en el historial.</p>
            <?php endif; ?>
        </div>

    </main>
    
    <!-- Modal de Registro de Nueva Compra -->
    <div id="registerModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex justify-center items-center">
        <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-2xl font-bold text-[var(--color-secondary)]">Registrar Nueva Compra</h3>
                <button onclick="closeModal('registerModal')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            
            <form method="POST" id="compraForm" onsubmit="return validateAndSubmitForm(event)">
                <input type="hidden" name="action" value="registrar_compra">
                <input type="hidden" name="detalle_compra" id="detalle_compra_input">
                <input type="hidden" name="total" id="total_input">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <label for="id_proveedor" class="block text-sm font-medium text-gray-700">Seleccionar Proveedor (*)</label>
                        <select name="id_proveedor" id="id_proveedor" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 text-gray-800">
                            <option value="">-- Seleccione un Proveedor --</option>
                            <?php foreach ($proveedores as $p): ?>
                                <option value="<?php echo $p['id_proveedor']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="fecha" class="block text-sm font-medium text-gray-700">Fecha de Compra</label>
                        <input type="date" id="fecha" value="<?php echo date('Y-m-d'); ?>" disabled
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 bg-gray-100 text-gray-600">
                    </div>
                </div>

                <!-- SECCIÓN DE DETALLE -->
                <h4 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-1">Detalle de Insumos / Productos</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4 items-end">
                    <div class="col-span-12 md:col-span-4">
                        <label for="select_producto" class="block text-sm font-medium text-gray-700">Producto / Insumo</label>
                        <select id="select_producto" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 text-gray-800">
                            <option value="">-- Seleccione un producto --</option>
                            <?php foreach ($productos as $prod): ?>
                                <option 
                                    value="<?php echo $prod['id_producto']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                    data-precio-sugerido="<?php echo $prod['precio']; ?>">
                                    <?php echo htmlspecialchars($prod['nombre']); ?> (Stock: <?php echo $prod['stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label for="input_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                        <input type="number" id="input_cantidad" min="1" value="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 text-right">
                    </div>
                    <div class="col-span-5 md:col-span-3">
                        <label for="input_precio" class="block text-sm font-medium text-gray-700">Precio Unitario de Compra ($)</label>
                        <input type="number" id="input_precio" step="0.01" min="0.01" value="0.01"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 text-right">
                    </div>
                    <div class="col-span-3 md:col-span-3">
                        <button type="button" onclick="addItemToDetail()"
                                class="w-full bg-green-500 text-white py-2 rounded-md shadow-md hover:bg-green-600 mt-0 md:mt-6">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
                </div>

                <!-- TABLA DE DETALLE DE COMPRA -->
                <div class="table-responsive bg-gray-50 rounded-lg p-4 mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">P. Unitario</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody id="detalle_compra_body" class="bg-white divide-y divide-gray-200">
                            <!-- Filas del detalle se insertarán aquí por JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="px-4 py-2 text-right text-base font-bold text-gray-900">TOTAL FINAL:</th>
                                <th id="total_display" class="px-4 py-2 text-right text-xl font-extrabold text-[var(--color-primary)]">$0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="closeModal('registerModal')"
                            class="bg-gray-300 text-gray-800 py-2 px-4 rounded-md mr-3 hover:bg-gray-400">
                        Cancelar Compra
                    </button>
                    <button type="submit" id="submitCompraBtn"
                            class="bg-[var(--color-primary)] text-white py-2 px-4 rounded-md shadow-lg hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-check-circle mr-1"></i> Confirmar Compra y Actualizar Stock
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Almacena el detalle de la compra en JavaScript
        let detail = [];
        
        // Función para formatear dinero en JS
        function formatCurrency(amount) {
            return '$' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // --- LÓGICA DE DETALLE DE COMPRA ---

        // Listener para cargar el precio sugerido al seleccionar producto
        document.addEventListener('DOMContentLoaded', () => {
            const selectProducto = document.getElementById('select_producto');
            const inputPrecio = document.getElementById('input_precio');
            
            selectProducto.addEventListener('change', () => {
                const selectedOption = selectProducto.options[selectProducto.selectedIndex];
                const precioSugerido = selectedOption.getAttribute('data-precio-sugerido');
                
                if (precioSugerido) {
                    inputPrecio.value = parseFloat(precioSugerido).toFixed(2);
                }
            });
            updateTotal(); // Inicializa el total en 0.00
            
            // Poner el foco en el proveedor
            document.getElementById('id_proveedor').focus();
        });


        // Añade un producto al detalle de compra
        function addItemToDetail() {
            const selectProducto = document.getElementById('select_producto');
            const id_producto = parseInt(selectProducto.value);
            const nombre = selectProducto.options[selectProducto.selectedIndex].getAttribute('data-nombre');
            const cantidad = parseInt(document.getElementById('input_cantidad').value);
            const precio_unitario = parseFloat(document.getElementById('input_precio').value);

            if (!id_producto || isNaN(cantidad) || cantidad <= 0 || isNaN(precio_unitario) || precio_unitario <= 0) {
                alert('Por favor, seleccione un producto, ingrese una cantidad válida y un precio unitario mayor a cero.');
                return;
            }

            const subtotal = cantidad * precio_unitario;

            // Verificar si el producto ya está en el detalle
            let existingItem = detail.find(item => item.id_producto === id_producto);

            if (existingItem) {
                existingItem.cantidad += cantidad;
                // Si el precio de compra es diferente, podrías promediar, pero por ahora solo actualizaremos el unitario con el último ingresado
                existingItem.precio_unitario = precio_unitario;
                existingItem.subtotal = existingItem.cantidad * existingItem.precio_unitario;
            } else {
                detail.push({
                    id_producto,
                    nombre,
                    cantidad,
                    precio_unitario,
                    subtotal
                });
            }

            // Limpiar y repoblar el formulario de item
            document.getElementById('select_producto').value = '';
            document.getElementById('input_cantidad').value = '1';
            document.getElementById('input_precio').value = '0.01';

            renderDetailTable();
            updateTotal();
        }

        // Elimina un producto del detalle
        function removeItem(id_producto) {
            detail = detail.filter(item => item.id_producto !== id_producto);
            renderDetailTable();
            updateTotal();
        }

        // Renderiza la tabla de detalle
        function renderDetailTable() {
            const body = document.getElementById('detalle_compra_body');
            body.innerHTML = '';

            detail.forEach(item => {
                const row = body.insertRow();
                row.className = 'hover:bg-blue-50';

                // Producto
                row.insertCell().textContent = item.nombre;
                
                // Cantidad
                row.insertCell().innerHTML = `<span class="block w-full text-right">${item.cantidad}</span>`;

                // P. Unitario
                row.insertCell().innerHTML = `<span class="block w-full text-right">${formatCurrency(item.precio_unitario)}</span>`;
                
                // Subtotal
                row.insertCell().innerHTML = `<span class="block w-full text-right font-semibold">${formatCurrency(item.subtotal)}</span>`;
                
                // Eliminar
                const deleteCell = row.insertCell();
                deleteCell.className = 'text-center';
                deleteCell.innerHTML = `
                    <button type="button" onclick="removeItem(${item.id_producto})" 
                            class="text-red-500 hover:text-red-700" title="Eliminar Item">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
            });

            // Desactivar botón de submit si no hay items
            document.getElementById('submitCompraBtn').disabled = detail.length === 0;
        }

        // Calcula y actualiza el total
        function updateTotal() {
            const total = detail.reduce((sum, item) => sum + item.subtotal, 0);
            document.getElementById('total_display').textContent = formatCurrency(total);
            document.getElementById('total_input').value = total.toFixed(2); // Para enviar al servidor
        }

        // --- LÓGICA DE SUBMIT DEL FORMULARIO ---
        function validateAndSubmitForm(event) {
            const id_proveedor = document.getElementById('id_proveedor').value;
            const total = parseFloat(document.getElementById('total_input').value);

            if (!id_proveedor) {
                alert('Debe seleccionar un Proveedor.');
                event.preventDefault();
                return false;
            }

            if (detail.length === 0) {
                alert('Debe agregar al menos un producto/insumo al detalle de la compra.');
                event.preventDefault();
                return false;
            }

            if (total <= 0) {
                alert('El total de la compra debe ser mayor a $0.00.');
                event.preventDefault();
                return false;
            }

            // Llenar el campo oculto con el detalle JSON
            // Solo necesitamos los datos de la DB: id_producto, cantidad, precio_unitario, subtotal
            const detailToSend = detail.map(item => ({
                id_producto: item.id_producto,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario,
                subtotal: item.subtotal 
            }));

            document.getElementById('detalle_compra_input').value = JSON.stringify(detailToSend);

            // Desactivar botón para evitar doble click
            document.getElementById('submitCompraBtn').disabled = true;

            // Permitir el envío del formulario
            return true;
        }

        // --- Funciones para Modales ---
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>