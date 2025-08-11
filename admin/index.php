<?php
require_once 'check_session.php';
require_once '../config/database.php';

$conn = getConnection();

$query_productos = "SELECT COUNT(*) as total FROM productos";
$result_productos = $conn->query($query_productos);
$total_productos = $result_productos->fetch_assoc()['total'];

$query_marcas = "SELECT COUNT(*) as total FROM marcas";
$result_marcas = $conn->query($query_marcas);
$total_marcas = $result_marcas->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Catálogo de Zapatillas</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Corbata_Logo.png">
    <link rel="shortcut icon" type="image/png" href="/Corbata_Logo.png">
    
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Panel de Administración</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <div class="dashboard">
                <h2>Resumen</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3><?php echo $total_productos; ?></h3>
                            <p>Productos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏷️</div>
                        <div class="stat-info">
                            <h3><?php echo $total_marcas; ?></h3>
                            <p>Marcas</p>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        <a href="productos/agregar_nuevo.php" class="action-card">
                            <span class="action-icon">➕</span>
                            <span>Agregar Producto</span>
                        </a>
                        <a href="productos/index.php" class="action-card">
                            <span class="action-icon">📋</span>
                            <span>Ver Productos</span>
                        </a>
                        <a href="marcas/index.php" class="action-card">
                            <span class="action-icon">🏢</span>
                            <span>Gestionar Marcas</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>