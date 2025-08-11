<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$mensaje = $_GET['mensaje'] ?? '';

$query = "SELECT p.*, m.nombre as marca_nombre, 
          (SELECT imagen_path FROM producto_imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen_principal
          FROM productos p
          LEFT JOIN marcas m ON p.marca_id = m.id
          ORDER BY p.created_at DESC";

$result = $conn->query($query);
$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .no-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: #999;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Gestión de Productos</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <div class="page-header">
                <h2>Lista de Productos</h2>
                <div style="display: flex; gap: 1rem;">
                    <a href="agregar_nuevo.php" class="btn-primary">➕ Agregar Producto</a>
                    <a href="recalcular_precios.php" class="btn-secondary">💰 Recalcular Precios</a>
                </div>
            </div>
            
            <div class="table-container">
                <?php if (count($productos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Precio</th>
                                <th>Talles</th>
                                <th>Estado</th>
                                <th>Destacado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <?php if ($producto['imagen_principal']): ?>
                                            <img src="/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="no-image">📷</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['marca_nombre'] ?? 'Sin marca'); ?></td>
                                    <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($producto['talles'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if ($producto['activo']): ?>
                                            <span class="badge badge-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $producto['destacado'] ? '⭐ Sí' : 'No'; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="editar.php?id=<?php echo $producto['id']; ?>" 
                                               class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.9rem;">
                                                ✏️ Editar
                                            </a>
                                            <a href="eliminar.php?id=<?php echo $producto['id']; ?>" 
                                               class="btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.9rem;"
                                               onclick="return confirm('¿Está seguro de eliminar este producto?')">
                                                🗑️ Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #666;">
                        No hay productos registrados. 
                        <a href="agregar_nuevo.php">Agregar el primer producto</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>