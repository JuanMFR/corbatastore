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
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Corbata_Logo.png">
    <link rel="shortcut icon" type="image/png" href="/Corbata_Logo.png">
    <link rel="apple-touch-icon" href="/Corbata_Logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6B4423;
            --primary-dark: #4A2F18;
            --primary-light: #8B5A3C;
            --accent: #C9A961;
            --accent-dark: #A08449;
            --background: #FAFAFA;
            --surface: #FFFFFF;
            --text-primary: #1A1A1A;
            --text-secondary: #666666;
            --text-light: #999999;
            --border: #E5E5E5;
            --border-brown: #D4A574;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            position: relative;
        }
        
        /* Patrón de fondo sutil - puntos */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.025;
            z-index: -1;
            background-image: radial-gradient(circle, var(--primary) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        
        /* Gradiente de fondo muy sutil */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: 
                radial-gradient(ellipse at top left, rgba(201, 169, 97, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(139, 69, 19, 0.05) 0%, transparent 50%),
                linear-gradient(180deg, var(--background) 0%, #f5f2ef 100%);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #5A2D0C 0%, #3D1F08 100%);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25), 0 2px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(212, 165, 116, 0.15);
            transition: all 0.3s ease;
        }
        
        /* Efecto glassmorphism sutil cuando se hace scroll */
        .header.scrolled {
            background: linear-gradient(135deg, rgba(90, 45, 12, 0.98) 0%, rgba(61, 31, 8, 0.98) 100%);
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.35), 0 4px 20px rgba(0, 0, 0, 0.25);
            border-bottom-color: rgba(212, 165, 116, 0.25);
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 75px;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }

        .logo-img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 4px;
        }

        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
        }

        .nav {
            display: flex;
            gap: 35px;
            align-items: center;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: white;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }
        
        /* Styles específicos para producto detalle */
        .producto-detalle {
            padding: 3rem 0;
        }
        
        .detalle-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
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
            border-radius: 12px;
            cursor: zoom-in;
            box-shadow: var(--shadow-md);
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
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        
        .miniatura:hover,
        .miniatura.active {
            border-color: var(--primary);
            transform: scale(1.05);
        }
        
        .info-producto {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .breadcrumb a:hover {
            color: var(--primary);
        }
        
        .marca {
            display: inline-block;
            background: var(--background);
            color: var(--text-secondary);
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            border: 1px solid #e8d4c1;
        }
        
        .info-producto h1 {
            color: var(--text-primary);
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .precio-grande {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 300;
            margin: 0.5rem 0;
        }
        
        .descripcion {
            background: var(--background);
            padding: 1.5rem;
            border-radius: 10px;
            line-height: 1.8;
            border: 1px solid var(--border);
        }
        
        .descripcion h3 {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }
        
        .talles-disponibles {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .talles-disponibles h3 {
            width: 100%;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .talle-badge {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #e8d4c1;
            border-radius: 6px;
            font-weight: 500;
            color: var(--text-primary);
            transition: var(--transition);
        }
        
        .talle-badge:hover {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        
        .productos-relacionados {
            padding: 4rem 0;
            background: white;
        }
        
        .productos-relacionados h2 {
            text-align: center;
            margin-bottom: 3rem;
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1.8rem, 3vw, 2.2rem);
            font-weight: 700;
            color: var(--primary);
        }
        
        .relacionados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        /* Product Card para relacionados */
        .producto-card {
            background: var(--surface);
            border: none;
            height: 380px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
            border-radius: 0;
        }

        .producto-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08);
        }
        
        .producto-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .producto-card:hover img {
            transform: scale(1.05);
        }
        
        .no-image {
            width: 100%;
            height: 220px;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--text-light);
        }
        
        .producto-info {
            padding: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .producto-info h3 {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
            flex-grow: 1;
        }
        
        .precio {
            font-size: 1.3rem;
            font-weight: 300;
            color: var(--primary);
            margin-bottom: 12px;
        }
        
        .btn-ver {
            display: block;
            width: 100%;
            padding: 10px;
            background: var(--primary);
            color: white;
            border: none;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: var(--transition);
        }
        
        .btn-ver:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 68, 35, 0.3);
        }
        
        .contacto-cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .contacto-cta h3 {
            margin-bottom: 0.5rem;
            font-family: 'Montserrat', sans-serif;
        }
        
        .contacto-cta p {
            margin: 0.5rem 0;
            color: rgba(255, 255, 255, 0.95);
        }
        
        /* Botón flotante de WhatsApp */
        .whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3), 0 2px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            z-index: 1000;
            transition: all 0.3s ease;
            animation: whatsappPulse 2s infinite;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(37, 211, 102, 0.4), 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .whatsapp-float svg {
            width: 32px;
            height: 32px;
            fill: white;
        }
        
        @keyframes whatsappPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Footer */
        .footer {
            background: var(--text-primary);
            color: white;
            padding: 48px 0 24px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 32px;
        }

        .footer-brand {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .footer-description {
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .footer-title {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
            color: var(--accent);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }
        
        @media (max-width: 968px) {
            .detalle-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .nav {
                display: none;
            }
            
            .imagen-principal {
                height: 300px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .whatsapp-float {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
            }
            
            .whatsapp-float svg {
                width: 28px;
                height: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="index.php" class="logo">
                    <?php if (file_exists('Corbata_Logo.png')): ?>
                        <img src="Corbata_Logo.png" alt="Corbata Store" class="logo-img">
                    <?php else: ?>
                        <div class="logo-img" style="width: 45px; height: 45px; background: white; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700;">CS</div>
                    <?php endif; ?>
                    <span class="logo-text">Corbata Store</span>
                </a>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Inicio</a>
                    <a href="index.php#destacados" class="nav-link">Destacados</a>
                    <a href="index.php#catalogo" class="nav-link">Catálogo</a>
                    <a href="#contacto" class="nav-link">Contacto</a>
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
                            📧 storecorbata@gmail.com<br>
                            📱 +54 9 266 503 0600
                        </p>
                        <a href="<?php echo getWhatsAppProductUrl($producto['nombre'], $producto['precio'], $producto['marca_nombre']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           style="display: inline-block; margin-top: 1rem; padding: 10px 20px; background: white; color: var(--primary); text-decoration: none; border-radius: 6px; font-weight: 600; transition: transform 0.3s;">
                            💬 Consultar por WhatsApp
                        </a>
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

    <!-- Botón flotante de WhatsApp -->
    <?php
    require_once 'config/whatsapp.php';
    ?>
    <a href="<?php echo getWhatsAppGeneralUrl(); ?>" 
       class="whatsapp-float" 
       target="_blank" 
       rel="noopener noreferrer"
       title="Contactanos por WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.89 3.594z"/>
        </svg>
    </a>

    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-content">
                <div>
                    <h3 class="footer-brand">Corbata Store</h3>
                    <p class="footer-description">
                        Premium footwear para quienes valoran la calidad y el estilo. 
                        Cada paso cuenta una historia.
                    </p>
                </div>
                <div>
                    <h4 class="footer-title">Navegación</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="index.php#destacados">Destacados</a></li>
                        <li><a href="index.php#catalogo">Catálogo</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">Contacto</h4>
                    <ul class="footer-links">
                        <li>storecorbata@gmail.com</li>
                        <li>+54 9 266 503 0600</li>
                        <li>San Luis, Argentina</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p style="margin-bottom: 12px; padding: 12px 20px; background: rgba(201, 169, 97, 0.15); border-radius: 6px; max-width: 800px; margin-left: auto; margin-right: auto;">
                    <strong>📋 Importante:</strong> El stock y talles mostrados son referenciales. 
                    Te recomendamos consultarnos por WhatsApp para confirmar disponibilidad.
                </p>
                <p>&copy; 2024 Corbata Store. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Efecto de scroll en el header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
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