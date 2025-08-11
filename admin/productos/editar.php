<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$conn = getConnection();

$id = $_GET['id'] ?? 0;

if ($id == 0) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    header('Location: index.php');
    exit();
}

$query_marcas = "SELECT id, nombre FROM marcas ORDER BY nombre";
$result_marcas = $conn->query($query_marcas);
$marcas = [];
while ($row = $result_marcas->fetch_assoc()) {
    $marcas[] = $row;
}

$query_imagenes = "SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden";
$stmt_img = $conn->prepare($query_imagenes);
$stmt_img->bind_param("i", $id);
$stmt_img->execute();
$result_img = $stmt_img->get_result();
$imagenes = [];
while ($row = $result_img->fetch_assoc()) {
    $imagenes[] = $row;
}
$stmt_img->close();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $marca_id = $_POST['marca_id'] ?? null;
    $precio = $_POST['precio'] ?? 0;
    $talles = trim($_POST['talles'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($nombre) || empty($precio)) {
        $error = 'El nombre y precio son obligatorios';
    } else {
        $stmt = $conn->prepare("UPDATE productos SET nombre = ?, marca_id = ?, precio = ?, talles = ?, descripcion = ?, destacado = ?, activo = ? WHERE id = ?");
        $stmt->bind_param("sidssiii", $nombre, $marca_id, $precio, $talles, $descripcion, $destacado, $activo, $id);
        
        if ($stmt->execute()) {
            if (isset($_POST['eliminar_imagenes'])) {
                foreach ($_POST['eliminar_imagenes'] as $img_id) {
                    $stmt_del = $conn->prepare("SELECT imagen_path FROM producto_imagenes WHERE id = ?");
                    $stmt_del->bind_param("i", $img_id);
                    $stmt_del->execute();
                    $result_del = $stmt_del->get_result();
                    if ($row_del = $result_del->fetch_assoc()) {
                        $file_path = '../../' . $row_del['imagen_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    $stmt_del->close();
                    
                    $stmt_del = $conn->prepare("DELETE FROM producto_imagenes WHERE id = ?");
                    $stmt_del->bind_param("i", $img_id);
                    $stmt_del->execute();
                    $stmt_del->close();
                }
            }
            
            $upload_dir = '../../uploads/productos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (isset($_FILES['nuevas_imagenes']) && !empty($_FILES['nuevas_imagenes']['name'][0])) {
                $total_imagenes = count($_FILES['nuevas_imagenes']['name']);
                
                $check_principal = $conn->prepare("SELECT COUNT(*) as count FROM producto_imagenes WHERE producto_id = ? AND es_principal = 1");
                $check_principal->bind_param("i", $id);
                $check_principal->execute();
                $result_check = $check_principal->get_result();
                $tiene_principal = $result_check->fetch_assoc()['count'] > 0;
                $check_principal->close();
                
                for ($i = 0; $i < $total_imagenes; $i++) {
                    if ($_FILES['nuevas_imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $nombre_temp = $_FILES['nuevas_imagenes']['tmp_name'][$i];
                        $nombre_original = $_FILES['nuevas_imagenes']['name'][$i];
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                        
                        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($extension, $extensiones_permitidas)) {
                            $nombre_nuevo = uniqid('img_') . '_' . time() . '.' . $extension;
                            $ruta_completa = $upload_dir . $nombre_nuevo;
                            $ruta_db = 'uploads/productos/' . $nombre_nuevo;
                            
                            if (move_uploaded_file($nombre_temp, $ruta_completa)) {
                                $es_principal = (!$tiene_principal && $i === 0) ? 1 : 0;
                                if ($es_principal) $tiene_principal = true;
                                
                                $stmt_img = $conn->prepare("INSERT INTO producto_imagenes (producto_id, imagen_path, es_principal, orden) VALUES (?, ?, ?, ?)");
                                $orden = count($imagenes) + $i;
                                $stmt_img->bind_param("isii", $id, $ruta_db, $es_principal, $orden);
                                $stmt_img->execute();
                                $stmt_img->close();
                            }
                        }
                    }
                }
            }
            
            $mensaje = 'Producto actualizado exitosamente';
            header('Location: index.php?mensaje=' . urlencode($mensaje));
            exit();
        } else {
            $error = 'Error al actualizar el producto';
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
    <title>Editar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .current-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .current-image {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .current-image img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .current-image .image-options {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
        }
        
        .current-image .principal-badge {
            background: #27ae60;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        
        .checkbox-delete {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 5px;
            border-radius: 3px;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="admin-header">
                <h1>Editar Producto</h1>
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
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="marca_id">Marca</label>
                        <select id="marca_id" name="marca_id">
                            <option value="">Seleccione una marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['id']; ?>" <?php echo $producto['marca_id'] == $marca['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($marca['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo $producto['precio']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="talles">Talles (separados por coma)</label>
                        <input type="text" id="talles" name="talles" value="<?php echo htmlspecialchars($producto['talles']); ?>" placeholder="Ej: 38, 39, 40, 41, 42">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="destacado" value="1" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                            Producto Destacado
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="activo" value="1" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                            Producto Activo
                        </label>
                    </div>
                    
                    <?php if (count($imagenes) > 0): ?>
                        <div class="form-group">
                            <label>Imágenes Actuales</label>
                            <div class="current-images">
                                <?php foreach ($imagenes as $imagen): ?>
                                    <div class="current-image">
                                        <img src="/<?php echo htmlspecialchars($imagen['imagen_path']); ?>" alt="Imagen del producto">
                                        <div class="image-options">
                                            <?php if ($imagen['es_principal']): ?>
                                                <span class="principal-badge">Principal</span>
                                            <?php endif; ?>
                                            <label class="checkbox-delete">
                                                <input type="checkbox" name="eliminar_imagenes[]" value="<?php echo $imagen['id']; ?>">
                                                Eliminar
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Agregar Nuevas Imágenes</label>
                        <div class="image-input-container">
                            <label for="nuevas_imagenes" class="image-input-label">
                                📷 Click para seleccionar imágenes
                                <input type="file" id="nuevas_imagenes" name="nuevas_imagenes[]" multiple accept="image/*">
                            </label>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                        <a href="index.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>