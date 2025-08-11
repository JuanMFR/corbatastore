<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'agregar') {
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (!empty($nombre)) {
            $stmt = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            
            if ($stmt->execute()) {
                $mensaje = 'Marca agregada exitosamente';
            } else {
                $error = 'Error al agregar la marca. Puede que ya exista.';
            }
            $stmt->close();
        } else {
            $error = 'El nombre de la marca es obligatorio';
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM productos WHERE marca_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($count > 0) {
        $error = 'No se puede eliminar la marca porque tiene productos asociados';
    } else {
        $stmt = $conn->prepare("DELETE FROM marcas WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = 'Marca eliminada exitosamente';
        } else {
            $error = 'Error al eliminar la marca';
        }
        $stmt->close();
    }
}

$query = "SELECT m.*, COUNT(p.id) as total_productos 
          FROM marcas m 
          LEFT JOIN productos p ON m.id = p.marca_id 
          GROUP BY m.id 
          ORDER BY m.nombre";
$result = $conn->query($query);
$marcas = [];
while ($row = $result->fetch_assoc()) {
    $marcas[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .marca-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .marca-form h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .form-inline {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .form-inline .form-group {
            flex: 1;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Gestión de Marcas</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="marca-form">
                <h3>Agregar Nueva Marca</h3>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="agregar">
                    <div class="form-group">
                        <label for="nombre">Nombre de la Marca</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <button type="submit" class="btn-primary">Agregar Marca</button>
                </form>
            </div>
            
            <div class="table-container">
                <h2>Lista de Marcas</h2>
                <?php if (count($marcas) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marcas as $marca): ?>
                                <tr>
                                    <td><?php echo $marca['id']; ?></td>
                                    <td><?php echo htmlspecialchars($marca['nombre']); ?></td>
                                    <td><?php echo $marca['total_productos']; ?> productos</td>
                                    <td>
                                        <?php if ($marca['total_productos'] == 0): ?>
                                            <a href="?eliminar=<?php echo $marca['id']; ?>" 
                                               class="btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.9rem;"
                                               onclick="return confirm('¿Está seguro de eliminar esta marca?')">
                                                🗑️ Eliminar
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.9rem;">
                                                No se puede eliminar (tiene productos)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #666;">
                        No hay marcas registradas.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>