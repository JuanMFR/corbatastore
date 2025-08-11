<?php
// Test simple de configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Configuración</h2>";

// Test 1: PHP funcionando
echo "✅ PHP está funcionando<br><br>";

// Test 2: Verificar archivos
echo "<strong>Verificando archivos:</strong><br>";
$env_file = __DIR__ . '/.env';
$env_php = __DIR__ . '/config/env.php';
$db_php = __DIR__ . '/config/database.php';

echo "- .env existe: " . (file_exists($env_file) ? "✅ Sí" : "❌ No") . "<br>";
echo "- config/env.php existe: " . (file_exists($env_php) ? "✅ Sí" : "❌ No") . "<br>";
echo "- config/database.php existe: " . (file_exists($db_php) ? "✅ Sí" : "❌ No") . "<br><br>";

// Test 3: Intentar cargar configuración
echo "<strong>Intentando conectar a la base de datos:</strong><br>";

try {
    // Conexión directa sin usar los archivos de config
    $host = 'localhost:3307';
    $user = 'root';
    $pass = '';
    $dbname = 'corbatastore';
    
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Conexión exitosa a la base de datos<br>";
        
        // Verificar tabla admins
        $result = $conn->query("SHOW TABLES LIKE 'admins'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Tabla 'admins' existe<br>";
            
            // Contar usuarios
            $count = $conn->query("SELECT COUNT(*) as total FROM admins");
            if ($count) {
                $row = $count->fetch_assoc();
                echo "ℹ️ Usuarios en la tabla: " . $row['total'] . "<br>";
            }
        } else {
            echo "⚠️ Tabla 'admins' no existe<br>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Información PHP:</strong><br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL extension: " . (extension_loaded('mysqli') ? "✅ Cargada" : "❌ No cargada") . "<br>";
?>