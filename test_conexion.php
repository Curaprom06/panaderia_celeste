<?php
// test_conexion.php
require_once 'conexion.php'; 

if (isset($pdo)) {
    echo "<h1>CONEXIÓN EXITOSA!</h1>";
} else {
    echo "<h1>FALLÓ LA CONEXIÓN (Revisa XAMPP)</h1>";
}
?>