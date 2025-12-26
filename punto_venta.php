<?php
// punto_venta.php - Interfaz del Punto de Venta (PDV)

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad: Acceso solo a usuarios logueados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Inicialización de variables
$productos_json = "[]";
$error = '';
$nombre_usuario = htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['usuario']);
$id_usuario = $_SESSION['id_usuario'] ?? 0; // Asumimos que el ID de usuario está en la sesión

try {
    // 2. Obtener productos activos para la venta
    $sql_productos = "
        SELECT p.id_producto, p.nombre, p.precio, p.stock, c.nombre_categoria 
        FROM producto p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.estado_producto = 'Activo'
        ORDER BY c.nombre_categoria, p.nombre
    ";
    $stmt_productos = $pdo->query($sql_productos);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    $productos_json = json_encode($productos);
    
} catch (PDOException $e) {
    $error = "Error al cargar productos: " . $e->getMessage();
}


// 3. Procesamiento del Checkout (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'], $_POST['total_final'])) {
    
    // Validaciones básicas
    if ($id_usuario === 0) {
        // Redirige al PDV con error si no hay ID de empleado.
        header('Location: punto_venta.php?error=no_empleado');
        exit;
    } 
    
    $cart_data = json_decode($_POST['cart_data'], true);
    $total_final = (float)$_POST['total_final'];

    if (empty($cart_data)) {
        // Redirige al PDV con error si el carrito está vacío.
        header('Location: punto_venta.php?error=carrito_vacio');
        exit;
    }
    
    try {
        $pdo->beginTransaction();

        // 3.1. Insertar en la tabla 'venta'
        $sql_venta = "INSERT INTO venta (id_usuario, fecha, total) VALUES (?, NOW(), ?)";
        $stmt_venta = $pdo->prepare($sql_venta);
        $stmt_venta->execute([$id_usuario, $total_final]);
        $id_venta = $pdo->lastInsertId(); // Obtener el ID de la venta recién creada

        // 3.2. Insertar en la tabla 'detalle_venta' y actualizar 'stock'
        foreach ($cart_data as $id_producto => $item) {
            $cantidad = (int)$item['cantidad'];
            $precio_unitario = (float)$item['precio'];
            $subtotal = $cantidad * $precio_unitario;
            
            // Validar que la cantidad no sea negativa o cero
            if ($cantidad <= 0) {
                 throw new Exception("Error de datos: La cantidad para el producto ID {$id_producto} debe ser positiva.");
            }

            // Insertar detalle
            $sql_detalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_detalle = $pdo->prepare($sql_detalle);
            $stmt_detalle->execute([$id_venta, $id_producto, $cantidad, $precio_unitario, $subtotal]);

            // Actualizar stock del producto
            // Nota: Si el stock queda negativo o viola una restricción de UNSIGNED INT, PDOException saltará aquí.
            $sql_stock = "UPDATE producto SET stock = stock - ? WHERE id_producto = ?";
            $stmt_stock = $pdo->prepare($sql_stock);
            $stmt_stock->execute([$cantidad, $id_producto]);
        }

        $pdo->commit();

        // Redirigir a la Factura
        $redirect_url = "ver_factura.php?id=" . urlencode($id_venta);
        header("Location: {$redirect_url}");
        exit; 
        
    } catch (PDOException $e) {
        // Capturamos el error de la DB y lo mostramos en pantalla
        $pdo->rollBack();
        $error = "⚠️ **ERROR CRÍTICO EN LA BASE DE DATOS**:\n\n**Descripción del Fallo:** La transacción falló. Esto puede ser por falta de stock o una restricción de datos.\n\n**Mensaje Detallado de la DB:** " . $e->getMessage();
    } catch (Exception $e) {
        // Captura otros errores (como la validación de cantidad negativa/cero)
        $pdo->rollBack();
        $error = "Error de Lógica de Venta: " . $e->getMessage();
    }
}

// Manejo de mensajes de error de redirección (errores de validación)
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'no_empleado') {
        $error = "Error de sesión: No se encontró el ID del vendedor. Vuelva a iniciar sesión.";
    } elseif ($_GET['error'] === 'carrito_vacio') {
        $error = "No se puede finalizar la venta: El carrito está vacío.";
    }
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Punto de Venta - Panadería Celeste</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; 
            --color-secondary: #B8860B; 
        }
        .card-producto {
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }
        .card-producto:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-md">
        <div class="max-w-full mx-auto py-3 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-white">
                <i class="fas fa-cash-register mr-2"></i> Punto de Venta (PDV)
            </h1>
            <div class="flex items-center space-x-4 text-white">
                <span class="text-sm">Vendedor: <span class="font-semibold"><?php echo $nombre_usuario; ?></span></span>
                <a href="dashboard_empleado.php" class="text-gray-200 hover:text-white transition duration-150">
                    <i class="fas fa-home mr-1"></i> Inicio
                </a>
                <a href="logout.php" class="text-red-300 hover:text-red-100 transition duration-150">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <main class="flex flex-col lg:flex-row max-w-full mx-auto p-4 lg:space-x-4 min-h-[calc(100vh-60px)]">

        <!-- Columna de Productos (Izquierda - 2/3) -->
        <div class="lg:w-2/3 space-y-4">
            
            <?php if ($error): ?>
            <!-- Contenedor de Error: Ahora puede mostrar mensajes largos de la DB -->
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative whitespace-pre-wrap" role="alert">
                <strong class="font-bold">Error en Venta:</strong>
                <span class="block mt-1"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <div class="bg-white p-4 rounded-xl shadow-lg">
                <input type="text" id="filter" placeholder="Buscar producto por nombre..."
                       class="w-full p-3 border border-gray-300 rounded-lg focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)]">
            </div>

            <!-- Lista de Productos -->
            <div id="product-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">
                <!-- Las tarjetas de productos se inyectarán aquí con JavaScript -->
            </div>

        </div>

        <!-- Columna del Carrito (Derecha - 1/3) -->
        <div class="lg:w-1/3 mt-4 lg:mt-0 sticky top-4">
            <div class="bg-white p-6 rounded-xl shadow-2xl space-y-4">
                <h2 class="text-2xl font-bold text-gray-800 border-b pb-2 mb-4">
                    <i class="fas fa-shopping-cart mr-2 text-[var(--color-secondary)]"></i> Carrito de Venta
                </h2>
                
                <!-- Detalles del Carrito -->
                <div id="cart-details" class="space-y-3 min-h-[150px] max-h-[400px] overflow-y-auto">
                    <p id="empty-cart-message" class="text-gray-500 text-center">El carrito está vacío.</p>
                    <!-- Los items del carrito se inyectarán aquí -->
                </div>

                <!-- Totales -->
                <div class="border-t pt-4 space-y-2">
                    <div class="flex justify-between font-semibold text-lg">
                        <span>Total Items:</span>
                        <span id="item-count" class="text-gray-700">0</span>
                    </div>
                    <div class="flex justify-between font-extrabold text-2xl">
                        <span class="text-[var(--color-primary)]">TOTAL A PAGAR:</span>
                        <span id="total" class="text-[var(--color-primary)]">$0.00</span>
                    </div>
                </div>

                <!-- Formulario Oculto para Checkout (Maneja el POST) -->
                <form id="checkout-form" method="POST">
                    <input type="hidden" name="cart_data" id="cart-data-input">
                    <input type="hidden" name="total_final" id="total-final-input">
                    
                    <button type="button" id="checkout-button" disabled
                            class="w-full bg-[var(--color-secondary)] text-white py-3 rounded-lg shadow-md font-bold text-xl 
                                   hover:bg-yellow-700 transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed mt-4">
                        <i class="fas fa-check-circle mr-2"></i> Finalizar Venta
                    </button>
                    <button type="button" id="cancel-button" 
                            class="w-full mt-2 bg-red-500 text-white py-2 rounded-lg shadow-md font-semibold hover:bg-red-600 transition duration-300">
                        <i class="fas fa-times-circle mr-1"></i> Cancelar Venta
                    </button>
                </form>

                <!-- Área de Mensajes (No usamos alert()) -->
                <div id="message-box" class="mt-4 p-3 bg-blue-100 text-blue-800 rounded-lg hidden"></div>

            </div>
        </div>
    </main>

    <script>
    // Data PHP
    const productsData = <?php echo $productos_json; ?>;
    
    // Elementos DOM
    const productListEl = document.getElementById('product-list');
    const cartDetailsEl = document.getElementById('cart-details');
    const filterEl = document.getElementById('filter');
    const totalEl = document.getElementById('total');
    const itemCountEl = document.getElementById('item-count');
    const emptyCartMessageEl = document.getElementById('empty-cart-message');
    const checkoutButtonEl = document.getElementById('checkout-button');
    const cancelButtonEl = document.getElementById('cancel-button');
    const checkoutFormEl = document.getElementById('checkout-form');
    const cartDataInputEl = document.getElementById('cart-data-input');
    const totalFinalInputEl = document.getElementById('total-final-input');
    const messageBoxEl = document.getElementById('message-box');

    // Estado Global
    let cart = {};
    
    // --- UTILIDADES ---
    function formatCurrency(amount) {
        // Usamos Intl.NumberFormat para un formato de moneda más robusto (COP)
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 2
        }).format(amount);
    }
    
    function showMessage(text, type = 'success') {
        let bgColor = 'bg-green-100';
        let textColor = 'text-green-800';
        if (type === 'error') {
            bgColor = 'bg-red-100';
            textColor = 'text-red-800';
        }
        messageBoxEl.className = `mt-4 p-3 ${bgColor} ${textColor} rounded-lg`;
        messageBoxEl.innerHTML = text; // Usamos innerHTML para el formato en negrita
        messageBoxEl.style.display = 'block';
        setTimeout(() => {
            messageBoxEl.style.display = 'none';
        }, 5000);
    }

    // --- MANEJO DEL CARRITO ---
    
    function updateCartDisplay() {
        cartDetailsEl.innerHTML = '';
        let total = 0;
        let totalItems = 0;

        if (Object.keys(cart).length === 0) {
            emptyCartMessageEl.style.display = 'block';
            checkoutButtonEl.disabled = true;
        } else {
            emptyCartMessageEl.style.display = 'none';
            checkoutButtonEl.disabled = false;
            
            for (const id in cart) {
                const item = cart[id];
                const subtotal = item.precio * item.cantidad;
                total += subtotal;
                totalItems += item.cantidad;

                const itemEl = document.createElement('div');
                itemEl.id = `cart-item-${id}`;
                itemEl.className = 'flex justify-between items-center p-2 bg-gray-50 rounded-md shadow-sm';
                
                // *** ESTRUCTURA HTML FINAL CON BOTONES Y INPUT ***
                itemEl.innerHTML = `
                    <div class="flex-grow min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">${item.nombre}</p>
                        <p class="text-xs text-gray-500">P/U: ${formatCurrency(item.precio)}</p>
                    </div>
                    
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        
                        <div class="flex items-center space-x-0 border border-gray-300 rounded-md">
                            <button onclick="removeFromCart(${id})" title="Restar 1"
                                class="text-gray-700 hover:bg-gray-200 transition duration-150 rounded-l-md w-6 h-6 flex items-center justify-center text-sm font-bold p-0">
                                -
                            </button>
                            <input type="number" id="qty-input-${id}" value="${item.cantidad}" min="1" max="${item.stock_disponible}"
                                onblur="updateQuantityFromInput(${id}, this.value)"
                                onkeydown="if(event.key === 'Enter') { event.preventDefault(); updateQuantityFromInput(${id}, this.value); this.blur(); }"
                                class="w-10 h-6 text-center text-sm font-semibold border-y-0 border-x border-gray-300 p-0 m-0 focus:ring-0 focus:border-gray-300">
                            <button onclick="addToCart(${id})" title="Sumar 1"
                                class="text-gray-700 hover:bg-gray-200 transition duration-150 rounded-r-md w-6 h-6 flex items-center justify-center text-sm font-bold p-0">
                                +
                            </button>
                        </div>

                        <span class="font-bold text-md text-[var(--color-primary)] min-w-[70px] text-right">${formatCurrency(subtotal)}</span>
                        
                        <button onclick="deleteItemFromCart(${id})" title="Eliminar del carrito"
                                class="text-red-500 hover:text-red-700 transition duration-150 p-1">
                            <i class="fas fa-trash-alt text-sm"></i>
                        </button>
                    </div>
                `;
                // *** FIN ESTRUCTURA HTML ***
                
                cartDetailsEl.appendChild(itemEl);
            }
        }

        totalEl.textContent = formatCurrency(total);
        itemCountEl.textContent = totalItems;
        
        // Guardar el total y el carrito para el envío al servidor
        // Nota: Si el total es 0, toFixed(2) lo convierte a "0.00"
        totalFinalInputEl.value = total.toFixed(2); 
        cartDataInputEl.value = JSON.stringify(cart);
    }

    // Función que maneja el incremento de 1 (Botón + y Click en producto)
    function addToCart(id) {
        const product = productsData.find(p => p.id_producto == id);
        if (!product) return;

        const stock = parseInt(product.stock);
        
        if (cart[id]) {
            const newQty = cart[id].cantidad + 1;
            if (newQty <= stock) {
                cart[id].cantidad = newQty;
            } else {
                showMessage(`Stock insuficiente para **${product.nombre}**. Máximo: **${stock}**`, 'error');
            }
        } else {
            if (stock > 0) {
                cart[id] = {
                    id: product.id_producto,
                    nombre: product.nombre,
                    precio: parseFloat(product.precio),
                    cantidad: 1,
                    stock_disponible: stock
                };
            } else {
                showMessage(`El producto **${product.nombre}** está agotado.`, 'error');
                return;
            }
        }
        
        filterEl.value = '';
        updateCartDisplay();
        renderProducts(filterEl.value); 
    }

    // Función que maneja el decremento de 1 (Botón -)
    function removeFromCart(id) {
        if (cart[id]) {
            cart[id].cantidad--;
            if (cart[id].cantidad <= 0) {
                delete cart[id]; // Elimina si la cantidad llega a cero
            }
        }
        updateCartDisplay();
        renderProducts(filterEl.value); 
    }
    
    // Función: Elimina el producto completamente (Botón basura)
    function deleteItemFromCart(id) {
        if (cart[id]) {
            if (confirm(`¿Está seguro de que desea eliminar ${cart[id].nombre} completamente del carrito?`)) {
                delete cart[id];
                updateCartDisplay();
                renderProducts(filterEl.value); 
            }
        }
    }
    
    // 💡 NUEVA FUNCIÓN AÑADIDA: Actualiza la cantidad desde la casilla numérica 💡
    function updateQuantityFromInput(id, newQuantityStr) {
        let newQuantity = parseInt(newQuantityStr);
        const item = cart[id];

        if (!item) {
            // Esto solo pasa si intentas modificar un producto que ya fue eliminado
            updateCartDisplay(); 
            return;
        }

        const stock = item.stock_disponible;
        let originalQuantity = item.cantidad;
        
        if (isNaN(newQuantity) || newQuantity < 1) {
            // Si el valor es inválido, lo ajustamos a la cantidad original o a 1 si no estaba.
            showMessage('La cantidad debe ser un número entero positivo (mínimo 1).', 'error');
            newQuantity = originalQuantity > 0 ? originalQuantity : 1; 
        } else if (newQuantity > stock) {
            // Excede el stock disponible
            showMessage(`⚠️ **Stock insuficiente** para **${item.nombre}**. El stock disponible es: **${stock}** unidades. La cantidad se ajustó automáticamente.`, 'error');
            newQuantity = stock; // Fija la cantidad al stock máximo disponible
        }
        
        // Evitar re-renderizado si la cantidad no cambió
        if (item.cantidad === newQuantity) {
            document.getElementById(`qty-input-${id}`).value = newQuantity; // Asegura que la casilla muestre el valor correcto
            return; 
        }

        // Si la nueva cantidad es válida y diferente, actualizamos el carrito
        item.cantidad = newQuantity;
        document.getElementById(`qty-input-${id}`).value = newQuantity; // Asegura que la casilla muestre el valor final validado

        // Re-renderizar todo
        updateCartDisplay();
        renderProducts(filterEl.value); 
    }

    function cancelSale() {
        if (confirm("¿Está seguro de que desea cancelar la venta actual?")) {
            cart = {};
            filterEl.value = '';
            updateCartDisplay();
            renderProducts(filterEl.value); 
            showMessage("Venta cancelada. El carrito ha sido vaciado.", 'error');
        }
    }
    
    // --- MANEJO DE PRODUCTOS ---

    function renderProducts(filter = '') {
        productListEl.innerHTML = '';
        const lowerCaseFilter = filter.toLowerCase();

        const filteredProducts = productsData.filter(p => 
            p.nombre.toLowerCase().includes(lowerCaseFilter) ||
            p.nombre_categoria.toLowerCase().includes(lowerCaseFilter)
        );
        
        if (filteredProducts.length === 0) {
                productListEl.innerHTML = `<p class="col-span-4 text-center text-gray-500 py-8">No se encontraron productos.</p>`;
        } else {
            filteredProducts.forEach(product => {
                const id = product.id_producto;
                const currentCartQty = cart[id] ? cart[id].cantidad : 0;
                const remainingStock = product.stock - currentCartQty;
                
                const isDisabled = remainingStock <= 0;
                const stockText = isDisabled ? 'AGOTADO' : `Stock: ${remainingStock}`;

                const card = document.createElement('div');
                card.className = `card-producto bg-white p-4 rounded-xl shadow-md ${isDisabled ? 'opacity-60 grayscale' : 'hover:ring-2 hover:ring-[var(--color-secondary)]'}`;
                
                if (!isDisabled) {
                    card.setAttribute('onclick', `addToCart(${id})`);
                } else {
                    card.classList.add('cursor-default');
                }
                
                const cartBadge = currentCartQty > 0 
                    ? `<span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">${currentCartQty}</span>`
                    : '';
                        
                card.innerHTML = `
                    <div class="relative">
                        <h3 class="text-lg font-bold text-gray-800 truncate">${product.nombre}</h3>
                        ${cartBadge}
                    </div>
                    <p class="text-xs font-medium text-gray-500 mb-2">${product.nombre_categoria}</p>
                    <div class="mt-2 flex justify-between items-center">
                        <span class="text-xl font-extrabold text-[var(--color-primary)]">${formatCurrency(product.precio)}</span>
                        <span class="text-xs font-semibold ${isDisabled ? 'text-red-600' : 'text-gray-500'}">${stockText}</span>
                    </div>
                `;
                productListEl.appendChild(card);
            });
        }
    }

    // --- EVENT LISTENERS ---

    filterEl.addEventListener('input', (e) => {
        renderProducts(e.target.value); 
    });

    checkoutButtonEl.addEventListener('click', () => {
             if (Object.keys(cart).length === 0) {
                showMessage("El carrito está vacío. Agregue productos para continuar.", 'error');
                return;
            }
            
            checkoutButtonEl.disabled = true;
            checkoutButtonEl.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...`;

            checkoutFormEl.submit();
    });
    
    cancelButtonEl.addEventListener('click', cancelSale);


    // --- INICIALIZACIÓN ---
    document.addEventListener('DOMContentLoaded', () => {
        renderProducts();
        updateCartDisplay();
    });

</script>
</body>
</html>