<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$mensaje = '';
$error = '';

// Obtener configuración actual
$query = "SELECT * FROM configuracion WHERE clave IN ('porcentaje_ganancia', 'costo_caja', 'costo_envio')";
$result = $conn->query($query);
$config = [];
while ($row = $result->fetch_assoc()) {
    $config[$row['clave']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $porcentaje_ganancia = $_POST['porcentaje_ganancia'] ?? 50;
    $costo_caja = $_POST['costo_caja'] ?? 800;
    $costo_envio = $_POST['costo_envio'] ?? 1500;
    
    // Actualizar configuración
    $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
    
    // Actualizar porcentaje de ganancia
    $clave = 'porcentaje_ganancia';
    $stmt->bind_param("ss", $porcentaje_ganancia, $clave);
    $stmt->execute();
    
    // Actualizar costo de caja
    $clave = 'costo_caja';
    $stmt->bind_param("ss", $costo_caja, $clave);
    $stmt->execute();
    
    // Actualizar costo de envío
    $clave = 'costo_envio';
    $stmt->bind_param("ss", $costo_envio, $clave);
    $stmt->execute();
    
    $stmt->close();
    
    $mensaje = 'Configuración actualizada exitosamente';
    
    // Recargar configuración
    $result = $conn->query($query);
    $config = [];
    while ($row = $result->fetch_assoc()) {
        $config[$row['clave']] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Precios - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .config-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .config-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .config-card h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .formula-display {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }
        
        .formula-display h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .formula-box {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            text-align: center;
            border: 2px solid #3498db;
            margin: 1rem 0;
        }
        
        .example-calculation {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .example-calculation h4 {
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        
        .calc-line {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid #bbdefb;
        }
        
        .calc-line.total {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 2px solid #1976d2;
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .input-with-prefix {
            display: flex;
            align-items: center;
        }
        
        .input-prefix {
            background: #e9ecef;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 5px 0 0 5px;
            font-weight: 600;
        }
        
        .input-with-prefix input {
            border-radius: 0 5px 5px 0;
            border-left: none;
        }
        
        .input-suffix {
            background: #e9ecef;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-left: none;
            border-radius: 0 5px 5px 0;
            font-weight: 600;
        }
        
        .info-alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .info-alert h4 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Configuración de Precios</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <div class="config-container">
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="config-card">
                    <h2>⚙️ Configuración de Cálculo de Precios</h2>
                    
                    <div class="info-alert">
                        <h4>ℹ️ Información Importante</h4>
                        <p>Estos valores se utilizan para calcular automáticamente el precio de venta de los productos basándose en el costo ingresado.</p>
                        <p>Los cambios afectarán solo a los productos nuevos o editados después de guardar esta configuración.</p>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="costo_caja">Costo de Caja (por producto)</label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">$</span>
                                <input type="number" 
                                       id="costo_caja" 
                                       name="costo_caja" 
                                       step="0.01" 
                                       min="0"
                                       value="<?php echo $config['costo_caja']['valor'] ?? 800; ?>"
                                       required>
                            </div>
                            <small>Costo fijo que se suma a cada producto por concepto de empaque/caja</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="costo_envio">Costo Promedio de Envío</label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">$</span>
                                <input type="number" 
                                       id="costo_envio" 
                                       name="costo_envio" 
                                       step="0.01" 
                                       min="0"
                                       value="<?php echo $config['costo_envio']['valor'] ?? 1500; ?>"
                                       required>
                            </div>
                            <small>Costo promedio estimado de envío por producto</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="porcentaje_ganancia">Porcentaje de Ganancia</label>
                            <div class="input-with-prefix">
                                <input type="number" 
                                       id="porcentaje_ganancia" 
                                       name="porcentaje_ganancia" 
                                       step="0.01" 
                                       min="0" 
                                       max="500"
                                       value="<?php echo $config['porcentaje_ganancia']['valor'] ?? 50; ?>"
                                       required
                                       style="border-radius: 5px 0 0 5px; border-right: none;">
                                <span class="input-suffix">%</span>
                            </div>
                            <small>Porcentaje de ganancia que se aplicará sobre el costo total</small>
                        </div>
                        
                        <button type="submit" class="btn-primary">Guardar Configuración</button>
                        <a href="index.php" class="btn-secondary" style="margin-left: 1rem;">Volver</a>
                    </form>
                </div>
                
                <div class="config-card">
                    <h2>📊 Fórmula de Cálculo</h2>
                    
                    <div class="formula-display">
                        <h3>Fórmula Aplicada:</h3>
                        <div class="formula-box">
                            Precio Final = (Costo Producto + Costo Caja + Costo Envío) × (1 + Porcentaje Ganancia / 100)
                        </div>
                        
                        <div class="example-calculation">
                            <h4>Ejemplo de Cálculo:</h4>
                            <p>Si un producto tiene un costo de <strong>$10,000</strong>:</p>
                            <div style="padding: 0.5rem;">
                                <div class="calc-line">
                                    <span>Costo del producto:</span>
                                    <span>$10,000.00</span>
                                </div>
                                <div class="calc-line">
                                    <span>Costo de caja:</span>
                                    <span>$<?php echo number_format($config['costo_caja']['valor'] ?? 800, 2); ?></span>
                                </div>
                                <div class="calc-line">
                                    <span>Costo de envío:</span>
                                    <span>$<?php echo number_format($config['costo_envio']['valor'] ?? 1500, 2); ?></span>
                                </div>
                                <div class="calc-line">
                                    <span>Subtotal:</span>
                                    <span>$<?php 
                                        $subtotal = 10000 + ($config['costo_caja']['valor'] ?? 800) + ($config['costo_envio']['valor'] ?? 1500);
                                        echo number_format($subtotal, 2); 
                                    ?></span>
                                </div>
                                <div class="calc-line">
                                    <span>Ganancia (<?php echo $config['porcentaje_ganancia']['valor'] ?? 50; ?>%):</span>
                                    <span>$<?php 
                                        $ganancia = $subtotal * (($config['porcentaje_ganancia']['valor'] ?? 50) / 100);
                                        echo number_format($ganancia, 2); 
                                    ?></span>
                                </div>
                                <div class="calc-line total">
                                    <span>PRECIO FINAL:</span>
                                    <span>$<?php echo number_format($subtotal + $ganancia, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>