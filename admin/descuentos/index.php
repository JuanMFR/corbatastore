<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$mensaje = $_GET['mensaje'] ?? '';

// Obtener descuentos activos
$query = "SELECT d.*, m.nombre as marca_nombre,
          CASE 
            WHEN d.tipo = 'marca' THEN CONCAT('Marca: ', m.nombre)
            WHEN d.tipo = 'producto' THEN 'Productos específicos'
            ELSE d.tipo
          END as tipo_descripcion,
          (SELECT COUNT(*) FROM descuento_productos WHERE descuento_id = d.id) as productos_count
          FROM descuentos d
          LEFT JOIN marcas m ON d.marca_id = m.id
          ORDER BY d.activo DESC, d.fecha_fin DESC";

$result = $conn->query($query);
$descuentos = [];
while ($row = $result->fetch_assoc()) {
    $descuentos[] = $row;
}

// Desactivar descuentos vencidos
$conn->query("UPDATE descuentos SET activo = 0 WHERE fecha_fin < CURDATE() AND activo = 1");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Descuentos - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .descuento-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .descuento-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .descuento-card.activo {
            border-left: 4px solid #28a745;
        }
        
        .descuento-card.inactivo {
            opacity: 0.6;
            border-left: 4px solid #dc3545;
        }
        
        .descuento-info h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .descuento-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .badge-porcentaje {
            background: #ffc107;
            color: #000;
        }
        
        .badge-tipo {
            background: #17a2b8;
            color: white;
        }
        
        .badge-activo {
            background: #28a745;
            color: white;
        }
        
        .badge-inactivo {
            background: #dc3545;
            color: white;
        }
        
        .descuento-fechas {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Gestión de Descuentos</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_filter($descuentos, function($d) { return $d['activo'] == 1; })); ?>
                    </div>
                    <div class="stat-label">Descuentos Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count($descuentos); ?>
                    </div>
                    <div class="stat-label">Total Descuentos</div>
                </div>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <a href="crear.php" class="btn-primary">➕ Crear Nuevo Descuento</a>
                <a href="configuracion.php" class="btn-secondary" style="margin-left: 1rem;">⚙️ Configuración de Precios</a>
            </div>
            
            <h2>Lista de Descuentos</h2>
            
            <?php if (count($descuentos) > 0): ?>
                <?php foreach ($descuentos as $descuento): ?>
                    <div class="descuento-card <?php echo $descuento['activo'] ? 'activo' : 'inactivo'; ?>">
                        <div class="descuento-info">
                            <h3><?php echo htmlspecialchars($descuento['nombre']); ?></h3>
                            <div>
                                <span class="descuento-badge badge-porcentaje">
                                    -<?php echo $descuento['valor_descuento']; ?>%
                                </span>
                                <span class="descuento-badge badge-tipo">
                                    <?php echo htmlspecialchars($descuento['tipo_descripcion']); ?>
                                </span>
                                <span class="descuento-badge <?php echo $descuento['activo'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $descuento['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </div>
                            <div class="descuento-fechas">
                                📅 Desde: <?php echo date('d/m/Y', strtotime($descuento['fecha_inicio'])); ?> 
                                - Hasta: <?php echo date('d/m/Y', strtotime($descuento['fecha_fin'])); ?>
                                <?php if ($descuento['tipo'] == 'producto'): ?>
                                    | 📦 <?php echo $descuento['productos_count']; ?> productos
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="btn-group">
                            <a href="editar.php?id=<?php echo $descuento['id']; ?>" class="btn-secondary" style="padding: 0.5rem 1rem;">
                                ✏️ Editar
                            </a>
                            <?php if ($descuento['activo']): ?>
                                <a href="toggle.php?id=<?php echo $descuento['id']; ?>&action=desactivar" 
                                   class="btn-secondary" style="padding: 0.5rem 1rem;"
                                   onclick="return confirm('¿Desactivar este descuento?')">
                                    ⏸️ Desactivar
                                </a>
                            <?php else: ?>
                                <a href="toggle.php?id=<?php echo $descuento['id']; ?>&action=activar" 
                                   class="btn-primary" style="padding: 0.5rem 1rem;"
                                   onclick="return confirm('¿Activar este descuento?')">
                                    ▶️ Activar
                                </a>
                            <?php endif; ?>
                            <a href="eliminar.php?id=<?php echo $descuento['id']; ?>" 
                               class="btn-danger" style="padding: 0.5rem 1rem;"
                               onclick="return confirm('¿Está seguro de eliminar este descuento?')">
                                🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px;">
                    <p style="color: #666;">No hay descuentos creados.</p>
                    <a href="crear.php" class="btn-primary" style="margin-top: 1rem;">Crear Primer Descuento</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>