<?php
// gestión_proveedores.php - Vista principal, gestión de modales y listado de datos

session_start();
require_once 'conexion.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

$proveedores = [];
$error_db = '';

// 2. Lógica para cargar la lista de proveedores
try {
    $sql = "SELECT id_proveedor, nombre, telefono, email, direccion FROM proveedor ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al cargar proveedores: " . $e->getMessage();
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Proveedores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: #1E3A8A; 
            --color-secondary: #B8860B; 
        }
        /* Estilo base para el overlay del modal */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: none; /* Oculto por defecto, se maneja con JS */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

 <!-- Navbar -->
    <header class="bg-[var(--color-primary)] shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-shopping-cart mr-2"></i> Gestión de Proveedores
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
    

    <!-- Contenedor para mensajes de notificación (Toast) -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2">
        <!-- Las notificaciones se inyectarán aquí -->
    </div>

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold text-[var(--color-primary)] mb-6"></h1>

        <!-- Botón para abrir el modal de Agregar Nuevo Proveedor -->
        <button id="open-add-modal" 
            class="bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-300 mb-6">
            + Agregar Nuevo Proveedor
        </button>
        
        <!-- ============================================== -->
        <!-- TABLA DE PROVEEDORES -->
        <!-- ============================================== -->
        <div class="bg-white p-4 rounded-xl shadow-lg overflow-x-auto">
            <h2 class="text-xl font-semibold mb-4">Lista de Proveedores</h2>
            
            <?php if (!empty($error_db)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error de Base de Datos:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_db); ?></span>
                </div>
            <?php elseif (empty($proveedores)): ?>
                <p id="no-suppliers-msg" class="p-4 text-center text-gray-500">No se encontraron proveedores registrados. Usa el botón "Agregar Nuevo Proveedor" para comenzar.</p>
                <div id="suppliers-table-container" class="hidden">
            <?php else: ?>
                <div id="suppliers-table-container">
            <?php endif; ?>

                <table id="suppliers-table" class="min-w-full divide-y divide-gray-200 <?php echo empty($proveedores) ? 'hidden' : ''; ?>">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="suppliers-table-body" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr class="hover:bg-gray-50" data-id="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($proveedor['nombre']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($proveedor['telefono']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($proveedor['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($proveedor['direccion']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                <button data-id="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>" 
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                        class="open-edit-modal text-indigo-600 hover:text-indigo-900 transition duration-150">
                                    Editar
                                </button>
                                <button data-id="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                        class="open-delete-modal text-red-600 hover:text-red-900 transition duration-150">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php if (empty($proveedores)): ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- FIN DE TABLA DE PROVEEDORES -->
    </div>


    <!-- ============================================== -->
    <!-- MODAL 1: AGREGAR NUEVO PROVEEDOR (SIN CAMBIOS FUNCIONALES) -->
    <!-- ============================================== -->
    <div id="add-supplier-modal" class="modal-overlay fixed inset-0 flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 transform transition-all duration-300 scale-100">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-[var(--color-primary)]">Agregar Nuevo Proveedor</h3>
                <button id="close-add-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="add-supplier-form" class="mt-4 space-y-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre (*)</label>
                    <input type="text" id="nombre" name="nombre" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono (*)</label>
                    <input type="tel" id="telefono" name="telefono" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección (*)</label>
                    <textarea id="direccion" name="direccion" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 rows-3"></textarea>
                </div>
                
                <div class="pt-4 border-t flex justify-end">
                    <button type="button" id="cancel-add-modal" 
                            class="mr-2 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                        Cancelar
                    </button>
                    <button type="submit" id="submit-add-supplier"
                            class="bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-green-700 transition duration-150">
                        Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- ============================================== -->
    <!-- MODAL 2: EDITAR PROVEEDOR (NUEVO CONTENIDO) -->
    <!-- ============================================== -->
    <div id="edit-supplier-modal" class="modal-overlay fixed inset-0 flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 transform transition-all duration-300 scale-100">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-bold text-[var(--color-secondary)]">Editar Proveedor: <span id="edit-supplier-name-display"></span></h3>
                <button id="close-edit-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Indicador de carga -->
            <div id="edit-loading" class="text-center py-8 text-gray-500 hidden">
                Cargando datos del proveedor...
            </div>

            <form id="edit-supplier-form" class="mt-4 space-y-4 hidden">
                <input type="hidden" id="edit_id_proveedor" name="id_proveedor">
                
                <div>
                    <label for="edit_nombre" class="block text-sm font-medium text-gray-700">Nombre (*)</label>
                    <input type="text" id="edit_nombre" name="nombre" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="edit_telefono" class="block text-sm font-medium text-gray-700">Teléfono (*)</label>
                    <input type="tel" id="edit_telefono" name="telefono" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="edit_email" name="email" 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="edit_direccion" class="block text-sm font-medium text-gray-700">Dirección (*)</label>
                    <textarea id="edit_direccion" name="direccion" required 
                           class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 rows-3"></textarea>
                </div>
                
                <div class="pt-4 border-t flex justify-end">
                    <button type="button" id="cancel-edit-modal" 
                            class="mr-2 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                        Cancelar
                    </button>
                    <button type="submit" id="submit-edit-supplier"
                            class="bg-[var(--color-secondary)] text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-yellow-700 transition duration-150">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================== -->
    <!-- MODAL 3: CONFIRMACIÓN DE ELIMINACIÓN -->
    <!-- ============================================== -->
    <div id="delete-supplier-modal" class="modal-overlay fixed inset-0 flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 transform transition-all duration-300 scale-100">
            <div class="flex justify-between items-center pb-3 border-b border-red-200">
                <h3 class="text-xl font-bold text-red-600">Confirmar Eliminación</h3>
                <button id="close-delete-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="mt-4">
                <p class="text-gray-700">¿Estás seguro de que deseas eliminar al proveedor:</p>
                <p id="delete-supplier-name-display" class="font-bold text-red-600 mt-2 text-lg"></p>
                <p class="text-sm text-gray-500 mt-1">Esta acción es irreversible.</p>
            </div>
            
            <div class="pt-4 border-t mt-4 flex justify-end">
                <button type="button" id="cancel-delete-modal" 
                        class="mr-2 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                    Cancelar
                </button>
                <button type="button" id="execute-delete-supplier" data-id=""
                        class="bg-red-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-red-700 transition duration-150">
                    Eliminar
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================== -->
    <!-- SCRIPT DE JAVASCRIPT (LÓGICA) -->
    <!-- ============================================== -->
    <script>
        // --- FUNCIÓN PARA NOTIFICACIONES (TOAST) ---
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notification-container');
            const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = type === 'success' ? '✅' : '❌';
            
            const notification = document.createElement('div');
            notification.className = `${color} text-white px-6 py-3 rounded-lg shadow-xl mb-3 flex items-center transition-all duration-300 transform opacity-0 translate-x-full`;
            notification.innerHTML = `<span class="text-xl mr-3">${icon}</span> ${message}`;
            
            container.appendChild(notification);

            // Transición de entrada
            setTimeout(() => {
                notification.classList.remove('opacity-0', 'translate-x-full');
            }, 10);

            // Transición de salida y eliminación
            setTimeout(() => {
                notification.classList.add('opacity-0', 'translate-x-full');
                notification.addEventListener('transitionend', () => {
                    notification.remove();
                });
            }, 4000);
        }

        // --- Referencias a Elementos ---
        const addModal = document.getElementById('add-supplier-modal');
        const editModal = document.getElementById('edit-supplier-modal');
        const deleteModal = document.getElementById('delete-supplier-modal');
        const openAddBtn = document.getElementById('open-add-modal');
        const closeAddBtn = document.getElementById('close-add-modal');
        const cancelAddBtn = document.getElementById('cancel-add-modal');
        const closeEditBtn = document.getElementById('close-edit-modal');
        const cancelEditBtn = document.getElementById('cancel-edit-modal');
        const closeDeleteBtn = document.getElementById('close-delete-modal');
        const cancelDeleteBtn = document.getElementById('cancel-delete-modal');
        const executeDeleteBtn = document.getElementById('execute-delete-supplier');
        const addForm = document.getElementById('add-supplier-form');
        const editForm = document.getElementById('edit-supplier-form');
        const tableBody = document.getElementById('suppliers-table-body');
        const editSupplierNameDisplay = document.getElementById('edit-supplier-name-display');
        const deleteSupplierNameDisplay = document.getElementById('delete-supplier-name-display');
        const editLoading = document.getElementById('edit-loading');


        /**
         * Función para mostrar/ocultar modales
         */
        function toggleModal(modal, show) {
            modal.style.display = show ? 'flex' : 'none';
        }

        // --- MANEJADORES DE APERTURA Y CIERRE DE MODALES ---

        document.addEventListener('DOMContentLoaded', () => {
            toggleModal(addModal, false);
            toggleModal(editModal, false);
            toggleModal(deleteModal, false);
            setupTableListeners();
        });

        // Apertura/Cierre del Modal de Agregar
        openAddBtn.addEventListener('click', () => {
            addForm.reset(); 
            toggleModal(addModal, true);
        });
        [closeAddBtn, cancelAddBtn].forEach(btn => {
            btn.addEventListener('click', () => toggleModal(addModal, false));
        });

        // Cierre del Modal de Editar
        [closeEditBtn, cancelEditBtn].forEach(btn => {
            btn.addEventListener('click', () => toggleModal(editModal, false));
        });
        
        // Cierre del Modal de Eliminar
        [closeDeleteBtn, cancelDeleteBtn].forEach(btn => {
            btn.addEventListener('click', () => toggleModal(deleteModal, false));
        });


        // --- LÓGICA DE LA TABLA ---

        /**
         * Configura los listeners para los botones de Editar y Eliminar de todas las filas.
         * Se debe llamar al cargar la página y después de agregar/editar filas.
         */
        function setupTableListeners() {
            // Edit Listeners
            document.querySelectorAll('.open-edit-modal').forEach(button => {
                button.removeEventListener('click', openEditModalHandler); // Limpiar para evitar duplicados
                button.addEventListener('click', openEditModalHandler);
            });

            // Delete Listeners
            document.querySelectorAll('.open-delete-modal').forEach(button => {
                button.removeEventListener('click', openDeleteModalHandler); // Limpiar para evitar duplicados
                button.addEventListener('click', openDeleteModalHandler);
            });
        }
        
        /**
         * Manejador para abrir el modal de edición y cargar datos.
         */
        async function openEditModalHandler(e) {
            const id = e.currentTarget.dataset.id;
            const nombre = e.currentTarget.dataset.nombre;
            
            editSupplierNameDisplay.textContent = nombre;
            toggleModal(editModal, true);
            
            // Mostrar carga y ocultar formulario
            editLoading.classList.remove('hidden');
            editForm.classList.add('hidden');
            
            try {
                const response = await fetch(`obtener_proveedor.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('edit_id_proveedor').value = data.id_proveedor;
                    document.getElementById('edit_nombre').value = data.nombre;
                    document.getElementById('edit_telefono').value = data.telefono;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_direccion').value = data.direccion;
                    
                    editForm.classList.remove('hidden');
                } else {
                    showNotification(result.error || 'No se pudieron cargar los datos.', 'error');
                }
            } catch (error) {
                console.error('Error al obtener datos del proveedor:', error);
                showNotification('Error de conexión al cargar datos.', 'error');
            } finally {
                editLoading.classList.add('hidden');
            }
        }

        /**
         * Manejador para abrir el modal de eliminación.
         */
        function openDeleteModalHandler(e) {
            const id = e.currentTarget.dataset.id;
            const nombre = e.currentTarget.dataset.nombre;
            
            deleteSupplierNameDisplay.textContent = nombre;
            executeDeleteBtn.setAttribute('data-id', id); // Guardar ID en el botón de confirmación
            toggleModal(deleteModal, true);
        }

        /**
         * Actualiza una fila existente en la tabla después de una edición.
         */
        function updateSupplierRow(data) {
            const row = document.querySelector(`tr[data-id="${data.id_proveedor}"]`);
            if (row) {
                const cells = row.querySelectorAll('td');
                cells[0].textContent = data.nombre;
                cells[1].textContent = data.telefono;
                cells[2].textContent = data.email;
                cells[3].textContent = data.direccion;
                
                // Actualizar data attributes en los botones para futuras ediciones/eliminaciones
                const editBtn = row.querySelector('.open-edit-modal');
                const deleteBtn = row.querySelector('.open-delete-modal');
                if(editBtn) editBtn.setAttribute('data-nombre', data.nombre);
                if(deleteBtn) deleteBtn.setAttribute('data-nombre', data.nombre);
                
                showNotification('Proveedor actualizado correctamente.', 'success');
            }
        }
        
        /**
         * Añade una nueva fila de proveedor a la tabla.
         */
        function addSupplierRow(data) {
            const newRow = tableBody.insertRow();
            newRow.className = 'hover:bg-gray-50';
            newRow.setAttribute('data-id', data.id_proveedor);

            newRow.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${data.nombre}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.telefono}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.email}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.direccion}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                    <button data-id="${data.id_proveedor}" data-nombre="${data.nombre}" class="open-edit-modal text-indigo-600 hover:text-indigo-900 transition duration-150">Editar</button>
                    <button data-id="${data.id_proveedor}" data-nombre="${data.nombre}" class="open-delete-modal text-red-600 hover:text-red-900 transition duration-150">Eliminar</button>
                </td>
            `;
            
            setupTableListeners();
            
            document.getElementById('no-suppliers-msg')?.classList.add('hidden');
            document.getElementById('suppliers-table')?.classList.remove('hidden');
        }
        
        /**
         * Elimina una fila de la tabla después de la eliminación en el backend.
         */
        function removeSupplierRow(id_proveedor) {
            const row = document.querySelector(`tr[data-id="${id_proveedor}"]`);
            if (row) {
                row.remove();
                showNotification('Proveedor eliminado correctamente.', 'success');
                
                // Si la tabla queda vacía, mostrar el mensaje
                if (tableBody.children.length === 0) {
                     document.getElementById('no-suppliers-msg')?.classList.remove('hidden');
                     document.getElementById('suppliers-table')?.classList.add('hidden');
                }
            }
        }


        // --- LÓGICA DE ENVÍO DE FORMULARIOS ---
        
        // 1. Envío del Formulario de AGREGAR (ya implementado, pero repetido por completitud)
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault(); 
            const submitBtn = document.getElementById('submit-add-supplier');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = new FormData(addForm);
            
            try {
                const response = await fetch('guardar_proveedor.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    toggleModal(addModal, false);
                    addForm.reset();
                    addSupplierRow(result.data);
                } else {
                    showNotification(result.error || 'Error desconocido al guardar el proveedor.', 'error');
                }

            } catch (error) {
                console.error('Error de conexión:', error);
                showNotification('Error de conexión con el servidor.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Proveedor';
            }
        });

        // 2. Envío del Formulario de EDICIÓN
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-edit-supplier');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Actualizando...';

            const formData = new FormData(editForm);
            
            try {
                const response = await fetch('actualizar_proveedor.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    toggleModal(editModal, false);
                    updateSupplierRow(result.data); // Actualiza la fila en la vista
                } else {
                    showNotification(result.error || 'Error desconocido al actualizar.', 'error');
                }

            } catch (error) {
                console.error('Error de conexión:', error);
                showNotification('Error de conexión con el servidor.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Cambios';
            }
        });

        // 3. Ejecución de ELIMINACIÓN
        executeDeleteBtn.addEventListener('click', async (e) => {
            const id_proveedor = e.currentTarget.getAttribute('data-id');
            const deleteBtn = e.currentTarget;
            
            deleteBtn.disabled = true;
            deleteBtn.textContent = 'Eliminando...';

            const formData = new FormData();
            formData.append('id_proveedor', id_proveedor);

            try {
                const response = await fetch('eliminar_proveedor.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    toggleModal(deleteModal, false);
                    removeSupplierRow(id_proveedor); // Elimina la fila de la vista
                } else {
                    showNotification(result.error || 'Error desconocido al eliminar.', 'error');
                }

            } catch (error) {
                console.error('Error de conexión:', error);
                showNotification('Error de conexión con el servidor.', 'error');
            } finally {
                deleteBtn.disabled = false;
                deleteBtn.textContent = 'Eliminar';
            }
        });
    </script>
</body>
</html>