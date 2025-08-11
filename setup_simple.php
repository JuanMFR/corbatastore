<?php
// Setup simple sin dependencias externas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setup Admin - Corbata Store</h1>";

// Conexión directa
$host = 'localhost:3307';
$user = 'root';
$pass = '';
$dbname = 'corbatastore';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        die("❌ Error de conexión: " . $conn->connect_error);
    }
    
    echo "✅ Conectado a la base de datos<br><br>";
    
    // Crear tabla admins si no existe
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Tabla 'admins' verificada/creada<br>";
    } else {
        echo "❌ Error con la tabla: " . $conn->error . "<br>";
    }
    
    // Verificar si existe el usuario admin
    $check = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
    
    if ($check && $check->num_rows > 0) {
        // Actualizar contraseña
        $password_hash = password_hash('corbata2024', PASSWORD_DEFAULT);
        $sql = "UPDATE admins SET password = '$password_hash' WHERE username = 'admin'";
        
        if ($conn->query($sql) === TRUE) {
            echo "✅ Contraseña actualizada para usuario 'admin'<br>";
        } else {
            echo "❌ Error actualizando: " . $conn->error . "<br>";
        }
    } else {
        // Crear usuario
        $password_hash = password_hash('corbata2024', PASSWORD_DEFAULT);
        $sql = "INSERT INTO admins (username, password) VALUES ('admin', '$password_hash')";
        
        if ($conn->query($sql) === TRUE) {
            echo "✅ Usuario 'admin' creado exitosamente<br>";
        } else {
            echo "❌ Error creando usuario: " . $conn->error . "<br>";
        }
    }
    
    $conn->close();
    
    echo "<br><div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ Setup Completado</h3>";
    echo "<strong>Credenciales de acceso:</strong><br>";
    echo "URL: <a href='admin/'>admin/</a><br>";
    echo "Usuario: <strong>admin</strong><br>";
    echo "Contraseña: <strong>corbata2024</strong><br>";
    echo "</div>";
    
    echo "<br><div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>IMPORTANTE:</strong> Elimina este archivo después de usarlo";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>