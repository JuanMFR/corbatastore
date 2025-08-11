<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($nombre)) {
        $error = 'El nombre de la marca es obligatorio';
    } else {
        $stmt = $conn->prepare("INSERT INTO marcas (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        
        if ($stmt->execute()) {
            $mensaje = 'Marca creada exitosamente';
            header('Location: index.php?mensaje=' . urlencode($mensaje));
            exit();
        } else {
            $error = 'Error al crear la marca';
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Marca - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Crear Nueva Marca</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre de la Marca *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary">Guardar Marca</button>
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>