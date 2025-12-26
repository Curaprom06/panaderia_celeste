<?php
// conexion.php

$host = 'localhost'; 
$db = 'panaderia_celeste'; 
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';
$port = '3306';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Esto previene el Error 500 fatal si la conexión falla.
    die("Error de conexión a la base de datos: " . $e->getMessage()); 
}
?>