<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

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
    $precio = $_POST['precio'] ?? 0;
    $talles = trim($_POST['talles'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    if (empty($nombre) || empty($precio)) {
        $error = 'El nombre y precio son obligatorios';
    } else {
        $stmt = $conn->prepare("INSERT INTO productos (nombre, marca_id, precio, talles, descripcion, destacado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidssi", $nombre, $marca_id, $precio, $talles, $descripcion, $destacado);
        
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
        
        .preview-image .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
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
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
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
                        <label>
                            <input type="checkbox" name="destacado" value="1">
                            Producto Destacado
                        </label>
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