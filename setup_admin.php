<?php
// Script para configurar el usuario administrador
require_once 'config/database.php';

$conn = getConnection();

// Verificar si la tabla admins existe
$result = $conn->query("SHOW TABLES LIKE 'admins'");
if ($result->num_rows == 0) {
    // Crear la tabla si no existe
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "✅ Tabla 'admins' creada correctamente.<br>";
    } else {
        die("❌ Error creando tabla: " . $conn->error);
    }
}

// Verificar si ya existe un usuario admin
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$username = 'admin';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Actualizar la contraseña del usuario existente
    $password_hash = password_hash('corbata2024', PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $update->bind_param("ss", $password_hash, $username);
    
    if ($update->execute()) {
        echo "✅ Contraseña del usuario 'admin' actualizada correctamente.<br>";
    } else {
        echo "❌ Error actualizando contraseña: " . $conn->error . "<br>";
    }
    $update->close();
} else {
    // Crear nuevo usuario admin
    $password_hash = password_hash('corbata2024', PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $insert->bind_param("ss", $username, $password_hash);
    
    if ($insert->execute()) {
        echo "✅ Usuario 'admin' creado correctamente.<br>";
    } else {
        echo "❌ Error creando usuario: " . $conn->error . "<br>";
    }
    $insert->close();
}

$stmt->close();
$conn->close();

echo "<br><strong>📋 Información de acceso:</strong><br>";
echo "URL: <a href='/admin/login.php'>/admin/login.php</a><br>";
echo "Usuario: <code>admin</code><br>";
echo "Contraseña: <code>corbata2024</code><br>";
echo "<br><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.";
?>