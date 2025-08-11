<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

// Obtener configuración
$query_config = "SELECT * FROM configuracion WHERE clave IN ('porcentaje_ganancia', 'costo_caja', 'costo_envio')";
$result_config = $conn->query($query_config);
$config = [];
while ($row = $result_config->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

$porcentaje_ganancia = $config['porcentaje_ganancia'] ?? 50;
$costo_caja = $config['costo_caja'] ?? 800;
$costo_envio = $config['costo_envio'] ?? 1500;

$mensaje = '';
$aplicar_marketing = isset($_POST['aplicar_marketing']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener todos los productos
    $query = "SELECT id, nombre, costo FROM productos WHERE activo = 1";
    $result = $conn->query($query);
    
    $actualizados = 0;
    while ($producto = $result->fetch_assoc()) {
        $costo = $producto['costo'];
        
        // Calcular precio con la fórmula
        $costo_total = $costo + $costo_caja + $costo_envio;
        $ganancia = $costo_total * ($porcentaje_ganancia / 100);
        $precio_final = $costo_total + $ganancia;
        
        // Aplicar precio marketing si está seleccionado
        if ($aplicar_marketing) {
            // Redondear al millar más cercano y restar 10
            $precio_final = round($precio_final / 1000) * 1000 - 10;
        }
        
        // Actualizar el precio en la base de datos
        $stmt = $conn->prepare("UPDATE productos SET precio = ? WHERE id = ?");
        $stmt->bind_param("di", $precio_final, $producto['id']);
        
        if ($stmt->execute()) {
            $actualizados++;
        }
        $stmt->close();
    }
    
    $mensaje = "Se actualizaron los precios de $actualizados productos exitosamente.";
}

// Obtener productos para mostrar vista previa
$query = "SELECT id, nombre, costo, precio FROM productos WHERE activo = 1 ORDER BY nombre LIMIT 10";
$result = $conn->query($query);
$productos = [];
while ($row = $result->fetch_assoc()) {
    // Calcular el precio nuevo para preview
    $costo_total = $row['costo'] + $costo_caja + $costo_envio;
    $ganancia = $costo_total * ($porcentaje_ganancia / 100);
    $precio_nuevo = $costo_total + $ganancia;
    
    $row['precio_nuevo'] = $precio_nuevo;
    $row['precio_nuevo_marketing'] = round($precio_nuevo / 1000) * 1000 - 10;
    $productos[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recalcular Precios - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .preview-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .formula-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }
        
        .formula-box h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .formula-display {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            text-align: center;
            border: 2px solid #3498db;
            margin: 1rem 0;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .preview-table th,
        .preview-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .preview-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .precio-actual {
            color: #dc3545;
            text-decoration: line-through;
        }
        
        .precio-nuevo {
            color: #28a745;
            font-weight: bold;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-container {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .checkbox-container label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
        }
        
        .checkbox-container input[type="checkbox"] {
            margin-right: 0.5rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .config-values {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .config-values p {
            margin: 0.5rem 0;
        }
        
        .config-values strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Recalcular Precios Masivamente</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <div class="preview-container">
                <h2>⚙️ Configuración Actual</h2>
                
                <div class="config-values">
                    <p><strong>Costo de Caja:</strong> $<?php echo number_format($costo_caja, 0, ',', '.'); ?></p>
                    <p><strong>Costo de Envío:</strong> $<?php echo number_format($costo_envio, 0, ',', '.'); ?></p>
                    <p><strong>Porcentaje de Ganancia:</strong> <?php echo $porcentaje_ganancia; ?>%</p>
                </div>
                
                <div class="formula-box">
                    <h3>Fórmula de Cálculo:</h3>
                    <div class="formula-display">
                        Precio = (Costo + $<?php echo number_format($costo_caja, 0, ',', '.'); ?> + $<?php echo number_format($costo_envio, 0, ',', '.'); ?>) × <?php echo (1 + $porcentaje_ganancia/100); ?>
                    </div>
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ Advertencia:</strong> Esta acción actualizará TODOS los precios de los productos activos basándose en su costo y la fórmula configurada. Esta acción no se puede deshacer.
                </div>
                
                <h3>Vista Previa (primeros 10 productos):</h3>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Costo</th>
                            <th>Precio Actual</th>
                            <th>Precio Nuevo (Normal)</th>
                            <th>Precio Nuevo (Marketing)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td>$<?php echo number_format($producto['costo'], 0, ',', '.'); ?></td>
                            <td class="precio-actual">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></td>
                            <td class="precio-nuevo">$<?php echo number_format($producto['precio_nuevo'], 0, ',', '.'); ?></td>
                            <td class="precio-nuevo">$<?php echo number_format($producto['precio_nuevo_marketing'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <form method="POST" style="margin-top: 2rem;" onsubmit="return confirm('¿Está seguro de actualizar todos los precios?');">
                    <div class="checkbox-container">
                        <label>
                            <input type="checkbox" name="aplicar_marketing" value="1">
                            Aplicar precio marketing (terminar en 990, ejemplo: $18.990 en vez de $19.000)
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary">Actualizar Todos los Precios</button>
                        <a href="../descuentos/configuracion.php" class="btn-secondary">Cambiar Configuración</a>
                        <a href="index.php" class="btn-secondary">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>