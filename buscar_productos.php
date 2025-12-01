<?php
// buscar_productos.php - Endpoint para la búsqueda AJAX de productos activos

session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔒 Verificación de sesión simple
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

require_once 'conexion.php';

// Obtener la consulta del usuario (query)
$query = $_GET['query'] ?? '';
$query = trim($query);

// Si la consulta es muy corta, no buscamos para ahorrar recursos
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Buscar productos activos cuyo nombre contenga la cadena de búsqueda
    $sql = "
        SELECT 
            p.id_producto, 
            p.nombre, 
            p.precio, 
            p.stock,
            p.unidad_medida,
            c.nombre_categoria
        FROM producto p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.estado_producto = 'Activo' 
          AND p.nombre LIKE ?
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    // Usamos LIKE con comodines '%' para buscar coincidencias parciales
    $searchTerm = '%' . $query . '%'; 
    $stmt->execute([$searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (PDOException $e) {
    // Manejo de error de base de datos
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos.']);
}
?>