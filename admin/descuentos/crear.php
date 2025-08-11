<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

// Obtener marcas
$query_marcas = "SELECT id, nombre FROM marcas ORDER BY nombre";
$result_marcas = $conn->query($query_marcas);
$marcas = [];
while ($row = $result_marcas->fetch_assoc()) {
    $marcas[] = $row;
}

// Obtener productos
$query_productos = "SELECT p.id, p.nombre, m.nombre as marca_nombre 
                    FROM productos p 
                    LEFT JOIN marcas m ON p.marca_id = m.id 
                    WHERE p.activo = 1 
                    ORDER BY m.nombre, p.nombre";
$result_productos = $conn->query($query_productos);
$productos = [];
while ($row = $result_productos->fetch_assoc()) {
    $productos[] = $row;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $valor_descuento = $_POST['valor_descuento'] ?? 0;
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $marca_id = $_POST['marca_id'] ?? null;
    $productos_ids = $_POST['productos_ids'] ?? [];
    
    if (empty($nombre) || empty($tipo) || empty($valor_descuento) || empty($fecha_inicio) || empty($fecha_fin)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        // Insertar descuento
        $stmt = $conn->prepare("INSERT INTO descuentos (nombre, tipo, valor_descuento, marca_id, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssdiss", $nombre, $tipo, $valor_descuento, $marca_id, $fecha_inicio, $fecha_fin);
        
        if ($stmt->execute()) {
            $descuento_id = $conn->insert_id;
            
            // Si es descuento por productos específicos
            if ($tipo == 'producto' && !empty($productos_ids)) {
                $stmt_prod = $conn->prepare("INSERT INTO descuento_productos (descuento_id, producto_id) VALUES (?, ?)");
                foreach ($productos_ids as $producto_id) {
                    $stmt_prod->bind_param("ii", $descuento_id, $producto_id);
                    $stmt_prod->execute();
                }
                $stmt_prod->close();
            }
            
            $mensaje = 'Descuento creado exitosamente';
            header('Location: index.php?mensaje=' . urlencode($mensaje));
            exit();
        } else {
            $error = 'Error al crear el descuento';
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
    <title>Crear Descuento - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .tipo-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .tipo-option {
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .tipo-option:hover {
            border-color: #3498db;
            background: #f0f8ff;
        }
        
        .tipo-option.selected {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .tipo-option input[type="radio"] {
            display: none;
        }
        
        .tipo-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .conditional-section {
            display: none;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .conditional-section.active {
            display: block;
        }
        
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.5rem;
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .producto-checkbox {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .producto-checkbox:hover {
            background: #f0f0f0;
        }
        
        .producto-checkbox input {
            margin-right: 0.5rem;
        }
        
        .producto-checkbox label {
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .preview-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .preview-box h4 {
            color: #856404;
            margin-bottom: 0.5rem;
        }
        
        .select-all-btn {
            padding: 0.5rem 1rem;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .select-all-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Crear Nuevo Descuento</h1>
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
                        <label for="nombre">Nombre del Descuento *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Cyber Week, Descuento Nike, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="valor_descuento">Porcentaje de Descuento (%) *</label>
                        <input type="number" id="valor_descuento" name="valor_descuento" 
                               min="1" max="100" step="1" required
                               placeholder="Ej: 25">
                        <small>Ingrese solo el número sin el símbolo %</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Descuento *</label>
                        <div class="tipo-selector">
                            <label class="tipo-option">
                                <input type="radio" name="tipo" value="marca" required onchange="toggleTipo('marca')">
                                <div class="tipo-icon">🏷️</div>
                                <div>Descuento por Marca</div>
                                <small>Aplica a todos los productos de una marca</small>
                            </label>
                            <label class="tipo-option">
                                <input type="radio" name="tipo" value="producto" required onchange="toggleTipo('producto')">
                                <div class="tipo-icon">👟</div>
                                <div>Productos Específicos</div>
                                <small>Selecciona productos individuales</small>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Sección para descuento por marca -->
                    <div id="marca-section" class="conditional-section">
                        <div class="form-group">
                            <label for="marca_id">Seleccionar Marca</label>
                            <select id="marca_id" name="marca_id">
                                <option value="">-- Seleccione una marca --</option>
                                <?php foreach ($marcas as $marca): ?>
                                    <option value="<?php echo $marca['id']; ?>">
                                        <?php echo htmlspecialchars($marca['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Sección para descuento por productos -->
                    <div id="producto-section" class="conditional-section">
                        <div class="form-group">
                            <label>Seleccionar Productos</label>
                            <button type="button" class="select-all-btn" onclick="toggleAll()">
                                Seleccionar/Deseleccionar Todos
                            </button>
                            <div class="productos-grid">
                                <?php 
                                $current_marca = '';
                                foreach ($productos as $producto): 
                                    if ($current_marca != $producto['marca_nombre']):
                                        $current_marca = $producto['marca_nombre'];
                                ?>
                                    <div style="grid-column: 1/-1; font-weight: bold; color: #495057; margin-top: 0.5rem; padding: 0.5rem; background: #e9ecef;">
                                        <?php echo htmlspecialchars($current_marca ?: 'Sin marca'); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="producto-checkbox">
                                    <input type="checkbox" 
                                           id="prod_<?php echo $producto['id']; ?>" 
                                           name="productos_ids[]" 
                                           value="<?php echo $producto['id']; ?>">
                                    <label for="prod_<?php echo $producto['id']; ?>">
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio *</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Finalización *</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="preview-box">
                        <h4>💡 Nota Importante</h4>
                        <p>El descuento se activará automáticamente en la fecha de inicio y se desactivará en la fecha de finalización.</p>
                        <p>Los clientes verán el precio original tachado y el nuevo precio con descuento aplicado.</p>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Crear Descuento</button>
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function toggleTipo(tipo) {
            // Ocultar todas las secciones
            document.getElementById('marca-section').classList.remove('active');
            document.getElementById('producto-section').classList.remove('active');
            
            // Mostrar la sección correspondiente
            if (tipo === 'marca') {
                document.getElementById('marca-section').classList.add('active');
            } else if (tipo === 'producto') {
                document.getElementById('producto-section').classList.add('active');
            }
            
            // Actualizar estilos de los botones
            document.querySelectorAll('.tipo-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.tipo-option').classList.add('selected');
        }
        
        function toggleAll() {
            const checkboxes = document.querySelectorAll('input[name="productos_ids[]"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
        }
        
        // Validar que fecha fin sea mayor que fecha inicio
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            document.getElementById('fecha_fin').min = this.value;
        });
    </script>
</body>
</html>