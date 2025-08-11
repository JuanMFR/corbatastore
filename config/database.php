<?php
// Detectar si estamos en desarrollo local o producción
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_localhost) {
    // Configuración para desarrollo local
    define('DB_HOST', 'localhost:3307');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'corbatastore');
} else {
    // Configuración para producción (Hostinger)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u884501120_corbata');
    define('DB_PASS', 'CorbataMiel5.');
    define('DB_NAME', 'u884501120_corbatadb');
}

function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
?>