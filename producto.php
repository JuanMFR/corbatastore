<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

// Obtener información del producto
$stmt = $conn->prepare("SELECT p.*, m.nombre as marca_nombre 
                        FROM productos p 
                        LEFT JOIN marcas m ON p.marca_id = m.id 
                        WHERE p.id = ? AND p.activo = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    header('Location: index.php');
    exit();
}

// Obtener imágenes del producto
$stmt = $conn->prepare("SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY es_principal DESC, orden");
$stmt->bind_param("i", $id);
$stmt->execute();
$result_imagenes = $stmt->get_result();
$imagenes = [];
while ($row = $result_imagenes->fetch_assoc()) {
    $imagenes[] = $row;
}
$stmt->close();

// Obtener productos relacionados
$marca_id = $producto['marca_id'] ?? 0;
$query_relacionados = "SELECT p.*, 
                       (SELECT imagen_path FROM producto_imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen
                       FROM productos p
                       WHERE p.activo = 1 AND p.id != $id ";

if ($marca_id > 0) {
    $query_relacionados .= "AND p.marca_id = $marca_id ";
}

$query_relacionados .= "ORDER BY RAND() LIMIT 4";
$result_relacionados = $conn->query($query_relacionados);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Corbata Store</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .producto-detalle {
            padding: 3rem 0;
        }
        
        .detalle-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        
        .galeria {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .imagen-principal {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
            cursor: zoom-in;
        }
        
        .miniaturas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
        }
        
        .miniatura {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border 0.3s;
        }
        
        .miniatura:hover,
        .miniatura.active {
            border-color: #5e3bce;
        }
        
        .info-producto {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .breadcrumb a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            color: #5e3bce;
        }
        
        .precio-grande {
            font-size: 2.5rem;
            color: #5e3bce;
            font-weight: bold;
        }
        
        .descripcion {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            line-height: 1.8;
        }
        
        .talles-disponibles {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .talle-badge {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .productos-relacionados {
            padding: 3rem 0;
        }
        
        .productos-relacionados h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            color: #333;
        }
        
        .relacionados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .contacto-cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .contacto-cta h3 {
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .detalle-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .imagen-principal {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">👟 Corbata Store</h1>
                <nav class="main-nav">
                    <a href="index.php">Inicio</a>
                    <a href="index.php#catalogo">Catálogo</a>
                    <a href="#contacto">Contacto</a>
                </nav>
            </div>
        </div>
    </header>

    <section class="producto-detalle">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Inicio</a>
                <span>/</span>
                <a href="index.php#catalogo">Catálogo</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
            </div>

            <div class="detalle-container">
                <div class="galeria">
                    <?php if (count($imagenes) > 0): ?>
                        <img id="imagen-principal" 
                             src="<?php echo htmlspecialchars($imagenes[0]['imagen_path']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             class="imagen-principal">
                        
                        <?php if (count($imagenes) > 1): ?>
                            <div class="miniaturas">
                                <?php foreach ($imagenes as $index => $imagen): ?>
                                    <img src="<?php echo htmlspecialchars($imagen['imagen_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?> - Imagen <?php echo $index + 1; ?>"
                                         class="miniatura <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="cambiarImagen('<?php echo htmlspecialchars($imagen['imagen_path']); ?>', this)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-image" style="height: 500px; font-size: 6rem;">👟</div>
                    <?php endif; ?>
                </div>

                <div class="info-producto">
                    <?php if ($producto['marca_nombre']): ?>
                        <span class="marca" style="display: inline-block; width: fit-content;">
                            <?php echo htmlspecialchars($producto['marca_nombre']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                    
                    <div class="precio-grande">
                        $<?php echo number_format($producto['precio'], 2); ?>
                    </div>

                    <?php if ($producto['talles']): ?>
                        <div>
                            <h3>Talles Disponibles</h3>
                            <div class="talles-disponibles">
                                <?php 
                                $talles = explode(',', $producto['talles']);
                                foreach ($talles as $talle): 
                                ?>
                                    <span class="talle-badge"><?php echo trim($talle); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($producto['descripcion']): ?>
                        <div class="descripcion">
                            <h3>Descripción</h3>
                            <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="contacto-cta">
                        <h3>¿Interesado en este producto?</h3>
                        <p>Contáctanos para más información</p>
                        <p style="margin-top: 1rem;">
                            📧 info@corbatastore.com<br>
                            📱 +54 123 456 7890
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($result_relacionados->num_rows > 0): ?>
    <section class="productos-relacionados">
        <div class="container">
            <h2>Productos Relacionados</h2>
            <div class="relacionados-grid">
                <?php while ($relacionado = $result_relacionados->fetch_assoc()): ?>
                    <div class="producto-card">
                        <?php if ($relacionado['imagen']): ?>
                            <img src="<?php echo htmlspecialchars($relacionado['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($relacionado['nombre']); ?>">
                        <?php else: ?>
                            <div class="no-image">👟</div>
                        <?php endif; ?>
                        <div class="producto-info">
                            <h3><?php echo htmlspecialchars($relacionado['nombre']); ?></h3>
                            <p class="precio">$<?php echo number_format($relacionado['precio'], 2); ?></p>
                            <a href="producto.php?id=<?php echo $relacionado['id']; ?>" class="btn-ver">Ver Detalle</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer id="contacto" class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Corbata Store</h3>
                    <p>Tu tienda de confianza para encontrar las mejores zapatillas.</p>
                </div>
                <div class="footer-section">
                    <h4>Enlaces</h4>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="index.php#catalogo">Catálogo</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p>Email: info@corbatastore.com</p>
                    <p>Teléfono: +54 123 456 7890</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Corbata Store. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function cambiarImagen(src, elemento) {
            document.getElementById('imagen-principal').src = src;
            
            // Remover clase active de todas las miniaturas
            document.querySelectorAll('.miniatura').forEach(mini => {
                mini.classList.remove('active');
            });
            
            // Agregar clase active a la miniatura clickeada
            elemento.classList.add('active');
        }
    </script>
</body>
</html>