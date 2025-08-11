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

$query_marcas = "SELECT id, nombre FROM marcas ORDER BY nombre";
$result_marcas = $conn->query($query_marcas);
$marcas = [];
while ($row = $result_marcas->fetch_assoc()) {
    $marcas[] = $row;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $marca_id = $_POST['marca_id'] ?? null;
    $costo = $_POST['costo'] ?? 0;
    $precio = $_POST['precio'] ?? 0;
    $talles = trim($_POST['talles'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    if (empty($nombre) || empty($costo)) {
        $error = 'El nombre y costo son obligatorios';
    } else {
        // Calcular precio final si no se especificó manualmente
        if (empty($precio) || $precio == 0) {
            $precio = ($costo + $costo_caja + $costo_envio) * (1 + $porcentaje_ganancia / 100);
        }
        
        $stmt = $conn->prepare("INSERT INTO productos (nombre, marca_id, costo, precio, talles, descripcion, destacado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siddssi", $nombre, $marca_id, $costo, $precio, $talles, $descripcion, $destacado);
        
        if ($stmt->execute()) {
            $producto_id = $conn->insert_id;
            
            $upload_dir = '../../uploads/productos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $total_imagenes = count($_FILES['imagenes']['name']);
                
                for ($i = 0; $i < $total_imagenes; $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $nombre_temp = $_FILES['imagenes']['tmp_name'][$i];
                        $nombre_original = $_FILES['imagenes']['name'][$i];
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                        
                        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($extension, $extensiones_permitidas)) {
                            $nombre_nuevo = uniqid('img_') . '_' . time() . '.' . $extension;
                            $ruta_completa = $upload_dir . $nombre_nuevo;
                            $ruta_db = 'uploads/productos/' . $nombre_nuevo;
                            
                            if (move_uploaded_file($nombre_temp, $ruta_completa)) {
                                $es_principal = ($i === 0) ? 1 : 0;
                                
                                $stmt_img = $conn->prepare("INSERT INTO producto_imagenes (producto_id, imagen_path, es_principal, orden) VALUES (?, ?, ?, ?)");
                                $stmt_img->bind_param("isii", $producto_id, $ruta_db, $es_principal, $i);
                                $stmt_img->execute();
                                $stmt_img->close();
                            }
                        }
                    }
                }
            }
            
            $mensaje = 'Producto agregado exitosamente';
            header('Location: index.php?mensaje=' . urlencode($mensaje));
            exit();
        } else {
            $error = 'Error al agregar el producto';
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
    <title>Agregar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .cost-calculator {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .cost-calculator h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .cost-breakdown {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .cost-line {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
        }
        
        .cost-line.total {
            background: #e3f2fd;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            border: 2px solid #2196f3;
        }
        
        .cost-value {
            color: #2196f3;
        }
        
        .image-input-container {
            border: 2px dashed #ddd;
            padding: 1rem;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .image-input-container:hover {
            border-color: #3498db;
        }
        
        .image-input-container input[type="file"] {
            display: none;
        }
        
        .image-input-label {
            cursor: pointer;
            display: block;
            padding: 1rem;
        }
        
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .preview-image {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .preview-image img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .checkbox-destacado {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
        }
        
        .checkbox-destacado input[type="checkbox"] {
            margin-right: 0.5rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-destacado label {
            cursor: pointer;
            display: flex;
            align-items: center;
            font-weight: 500;
            color: #856404;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Agregar Nuevo Producto</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
                </div>
            </header>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="marca_id">Marca</label>
                        <select id="marca_id" name="marca_id">
                            <option value="">Seleccione una marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['id']; ?>">
                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="costo">Costo del Producto (sin adicionales) *</label>
                        <input type="number" id="costo" name="costo" step="0.01" min="0" required onchange="calcularPrecio()">
                        <small style="color: #666;">Ingrese el costo base del producto sin incluir caja ni envío</small>
                    </div>
                    
                    <div class="cost-calculator" id="calculadora" style="display: none;">
                        <h4>📊 Cálculo Automático del Precio</h4>
                        <div class="cost-breakdown">
                            <div class="cost-line">
                                <span>Costo del producto:</span>
                                <span class="cost-value">$<span id="show-costo">0</span></span>
                            </div>
                            <div class="cost-line">
                                <span>Costo de caja:</span>
                                <span class="cost-value">$<?php echo number_format($costo_caja, 2); ?></span>
                            </div>
                            <div class="cost-line">
                                <span>Costo de envío promedio:</span>
                                <span class="cost-value">$<?php echo number_format($costo_envio, 2); ?></span>
                            </div>
                            <div class="cost-line">
                                <span>Ganancia (<?php echo $porcentaje_ganancia; ?>%):</span>
                                <span class="cost-value">$<span id="show-ganancia">0</span></span>
                            </div>
                            <div class="cost-line total">
                                <span>PRECIO FINAL SUGERIDO:</span>
                                <span class="cost-value">$<span id="precio-calculado">0</span></span>
                            </div>
                        </div>
                        <div class="info-box">
                            💡 Este precio se calcula automáticamente. Puede modificarlo manualmente si lo desea.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio Final de Venta al Público</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" style="background: #f0f0f0; font-size: 1.2rem; font-weight: bold; color: #2196f3;">
                        <small style="color: #666;">Este es el precio que verán los clientes en el catálogo</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="precio_marketing" onchange="calcularPrecio()">
                            Aplicar precio marketing (terminar en 90, ej: $18.990)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="talles">Talles (separados por coma)</label>
                        <input type="text" id="talles" name="talles" placeholder="Ej: 38, 39, 40, 41, 42">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-destacado">
                            <input type="checkbox" id="destacado" name="destacado" value="1">
                            <label for="destacado">
                                ⭐ Marcar como Producto Destacado
                                <small style="margin-left: 0.5rem; font-weight: normal;">(Aparecerá en el carrusel principal)</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Imágenes del Producto</label>
                        <div class="image-input-container">
                            <label for="imagenes" class="image-input-label">
                                📷 Click para seleccionar imágenes
                                <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" onchange="previewImages(this)">
                            </label>
                        </div>
                        <div id="preview-container" class="preview-container"></div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary">Guardar Producto</button>
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const costoFijo = <?php echo $costo_caja; ?>;
        const costoEnvio = <?php echo $costo_envio; ?>;
        const porcentajeGanancia = <?php echo $porcentaje_ganancia; ?>;
        
        function calcularPrecio() {
            const costoInput = document.getElementById('costo');
            const costo = parseFloat(costoInput.value) || 0;
            const aplicarMarketing = document.getElementById('precio_marketing').checked;
            
            if (costo > 0) {
                document.getElementById('calculadora').style.display = 'block';
                
                const costoTotal = costo + costoFijo + costoEnvio;
                const ganancia = costoTotal * (porcentajeGanancia / 100);
                let precioFinal = costoTotal + ganancia;
                
                // Aplicar precio marketing si está seleccionado
                if (aplicarMarketing) {
                    // Redondear al millar más cercano y restar 10
                    precioFinal = Math.round(precioFinal / 1000) * 1000 - 10;
                }
                
                document.getElementById('show-costo').textContent = costo.toFixed(2);
                document.getElementById('show-ganancia').textContent = ganancia.toFixed(2);
                document.getElementById('precio-calculado').textContent = precioFinal.toFixed(2);
                document.getElementById('precio').value = precioFinal.toFixed(2);
            } else {
                document.getElementById('calculadora').style.display = 'none';
                document.getElementById('precio').value = '';
            }
        }
        
        function previewImages(input) {
            const previewContainer = document.getElementById('preview-container');
            previewContainer.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'preview-image';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}">
                        `;
                        previewContainer.appendChild(previewDiv);
                    }
                    
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</body>
</html>