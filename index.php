<?php
require_once 'config/database.php';
require_once 'config/whatsapp.php';

$conn = getConnection();

// Obtener noticias/banners activos
$query_noticias = "SELECT * FROM noticias 
                   WHERE activo = 1 
                   AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                   AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                   ORDER BY orden, created_at DESC
                   LIMIT 5";
$result_noticias = @$conn->query($query_noticias);

// Obtener productos destacados
$query_destacados = "SELECT p.*, m.nombre as marca_nombre, 
                     (SELECT imagen_path FROM producto_imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen
                     FROM productos p
                     LEFT JOIN marcas m ON p.marca_id = m.id
                     WHERE p.activo = 1 AND p.destacado = 1
                     ORDER BY p.created_at DESC
                     LIMIT 12";
$result_destacados = $conn->query($query_destacados);

// Configuración de paginación
$productos_por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Obtener todos los productos activos con filtros
$marca_filter = isset($_GET['marca']) ? intval($_GET['marca']) : 0;
$search = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'recientes';
$precio_min = isset($_GET['precio_min']) ? floatval($_GET['precio_min']) : 0;
$precio_max = isset($_GET['precio_max']) ? floatval($_GET['precio_max']) : 999999999;

$query = "SELECT p.*, m.nombre as marca_nombre, 
          (SELECT imagen_path FROM producto_imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen
          FROM productos p
          LEFT JOIN marcas m ON p.marca_id = m.id
          WHERE p.activo = 1";

if ($marca_filter > 0) {
    $query .= " AND p.marca_id = $marca_filter";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (p.nombre LIKE '%$search%' OR p.descripcion LIKE '%$search%' OR m.nombre LIKE '%$search%')";
}

if ($precio_min > 0) {
    $query .= " AND p.precio >= $precio_min";
}

if ($precio_max > 0 && $precio_max < 999999999) {
    $query .= " AND p.precio <= $precio_max";
}

// Primero obtener el total de productos con los filtros (sin LIMIT)
$query_count = str_replace("SELECT p.*, m.nombre as marca_nombre, 
          (SELECT imagen_path FROM producto_imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen", 
          "SELECT COUNT(*) as total", $query);
$result_count = $conn->query($query_count);
$total_productos = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Asegurar que la página actual no exceda el total
$pagina_actual = min($pagina_actual, max(1, $total_paginas));

// Calcular el offset para la consulta
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Agregar ORDER BY y LIMIT a la consulta principal
switch ($orden) {
    case 'precio_asc':
        $query .= " ORDER BY p.precio ASC";
        break;
    case 'precio_desc':
        $query .= " ORDER BY p.precio DESC";
        break;
    case 'nombre':
        $query .= " ORDER BY p.nombre ASC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

// Agregar LIMIT y OFFSET para paginación
$query .= " LIMIT $productos_por_pagina OFFSET $offset";

$result_productos = $conn->query($query);

// Manejo de errores en producción
if ($result_productos === false) {
    // En caso de error, mostrar productos vacíos en lugar de romper la página
    $result_productos = $conn->query("SELECT * FROM productos WHERE 1=0");
}

// Obtener marcas para el filtro
$query_marcas = "SELECT m.*, COUNT(p.id) as total 
                 FROM marcas m 
                 LEFT JOIN productos p ON m.id = p.marca_id AND p.activo = 1
                 GROUP BY m.id 
                 HAVING total > 0
                 ORDER BY m.nombre";
$result_marcas = $conn->query($query_marcas);

// Obtener rango de precios
$query_precios = "SELECT MIN(precio) as min_precio, MAX(precio) as max_precio FROM productos WHERE activo = 1";
$result_precios = $conn->query($query_precios);
$precios = $result_precios->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbata Store - Premium Footwear</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/Corbata_Logo.png">
    <link rel="shortcut icon" type="image/png" href="/Corbata_Logo.png">
    <link rel="apple-touch-icon" href="/Corbata_Logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
    
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

        /* Hero Banner - Más compacto */
        .hero-banner {
            margin: 0 0 32px 0;
            padding-top: 20px;
            background: linear-gradient(180deg, rgba(139, 69, 19, 0.08) 0%, transparent 100%);
        }

        .swiper-hero {
            height: 320px;
            background: var(--surface);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), var(--shadow-lg);
            overflow: hidden;
            border-radius: 12px;
        }

        .swiper-hero .swiper-slide {
            display: flex;
            align-items: center;
            padding: 0 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }

        .swiper-hero .swiper-slide::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 50%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
        }
        
        /* Estilo limpio para slides del carrusel de productos */
        .swiper-products .swiper-slide {
            background: transparent;
            padding: 0;
        }

        .banner-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
        }

        .banner-tag {
            display: inline-block;
            padding: 6px 14px;
            background: var(--accent);
            color: var(--primary-dark);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }

        .banner-title {
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 12px;
        }

        .banner-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.05rem;
            font-weight: 300;
            line-height: 1.4;
            margin-bottom: 24px;
        }

        .banner-btn {
            display: inline-block;
            padding: 10px 28px;
            background: white;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .banner-btn:hover {
            background: transparent;
            color: white;
            border-color: white;
        }

        /* Featured Products Carousel */
        .featured-section {
            padding: 48px 0 32px;
            background: var(--background);
        }

        .section-header {
            margin-bottom: 32px;
        }

        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1.8rem, 3vw, 2.2rem);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .swiper-products {
            padding: 20px 0 40px;
        }

        .product-card {
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
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            z-index: 1;
        }

        .product-image {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #f8f8f8;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-brand {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .product-name {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
            flex-grow: 1;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 300;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .product-btn {
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

        .product-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 68, 35, 0.3);
        }
        
        .product-whatsapp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 8px;
            background: white;
            color: var(--primary);
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--primary);
            transition: all 0.3s ease;
            margin-top: 6px;
        }
        
        .product-whatsapp:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(107, 68, 35, 0.3);
        }
        
        .product-whatsapp svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        /* Catalog with Sidebar */
        .catalog-section {
            padding: 48px 0 80px;
            background: white;
        }

        .catalog-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .catalog-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 40px;
            align-items: start;
        }

        /* Sidebar Filters */
        .filters-sidebar {
            background: rgba(255, 255, 255, 0.98);
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px;
            position: sticky;
            top: 90px;
            max-height: calc(100vh - 110px);
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }

        .filter-section {
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .filter-section:last-child {
            border-bottom: none;
        }

        .filter-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .search-box {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e8d4c1;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 6px 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-option:hover {
            color: var(--primary);
        }

        .filter-option input[type="radio"],
        .filter-option input[type="checkbox"] {
            margin-right: 8px;
            accent-color: var(--primary);
        }

        .filter-option label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            cursor: pointer;
            flex-grow: 1;
        }

        .filter-count {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .price-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #e8d4c1;
            font-size: 0.85rem;
        }

        .btn-apply-filters {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 20px;
        }

        .btn-apply-filters:hover {
            background: var(--primary-dark);
        }

        .btn-clear-filters {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 8px;
        }

        .btn-clear-filters:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 48px;
            padding: 24px 0;
            flex-wrap: wrap;
        }
        
        .pagination-btn,
        .pagination-number {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            background: white;
            border: 1px solid var(--border);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
            border-radius: 8px;
        }
        
        .pagination-btn:hover,
        .pagination-number:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 68, 35, 0.2);
        }
        
        .pagination-number.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            font-weight: 600;
            cursor: default;
            box-shadow: 0 2px 8px rgba(107, 68, 35, 0.15);
        }
        
        .pagination-dots {
            color: var(--text-secondary);
            padding: 0 8px;
            font-weight: 600;
        }
        
        .pagination-btn {
            font-weight: 500;
            padding: 0 16px;
        }
        
        @media (max-width: 640px) {
            .pagination {
                gap: 4px;
            }
            
            .pagination-btn,
            .pagination-number {
                min-width: 36px;
                height: 36px;
                font-size: 0.85rem;
            }
            
            .pagination-btn {
                padding: 0 12px;
            }
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
        
        /* Tooltip del botón WhatsApp */
        .whatsapp-float::before {
            content: 'Contactanos por WhatsApp';
            position: absolute;
            right: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .whatsapp-float:hover::before {
            opacity: 1;
        }
        
        /* Responsive del botón flotante */
        @media (max-width: 768px) {
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
            
            .whatsapp-float::before {
                display: none;
            }
        }

        /* Products Grid */
        .products-container {
            min-height: 400px;
        }

        .sort-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .results-count {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .sort-select {
            padding: 8px 16px;
            border: 2px solid #e8d4c1;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
        }
        
        /* Cards del catálogo sin bordes, solo sombra */
        .products-grid .product-card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .products-grid .product-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
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

        /* Swiper Custom Styles */
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.9);
            width: 36px;
            height: 60px;
            border-radius: 0;
            box-shadow: none;
            transition: var(--transition);
            opacity: 0.6;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 1);
            color: var(--primary);
        }

        .swiper-button-next {
            right: 0;
        }

        .swiper-button-prev {
            left: 0;
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Específico para carrusel de productos */
        .swiper-products .swiper-button-next,
        .swiper-products .swiper-button-prev {
            background: transparent;
            color: var(--primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .swiper-products:hover .swiper-button-next,
        .swiper-products:hover .swiper-button-prev {
            opacity: 0.7;
        }
        
        .swiper-products .swiper-button-next:hover,
        .swiper-products .swiper-button-prev:hover {
            opacity: 1;
        }

        .swiper-pagination-bullet {
            background: var(--primary-light);
            opacity: 0.5;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
            background: var(--primary);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .catalog-container {
                grid-template-columns: 1fr;
            }

            .filters-sidebar {
                position: relative;
                top: 0;
                max-height: none;
                margin-bottom: 32px;
            }
        }

        @media (max-width: 768px) {
            .nav {
                display: none;
            }

            .swiper-hero {
                height: 280px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 16px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 24px;
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
                    <a href="#home" class="nav-link">Inicio</a>
                    <a href="#destacados" class="nav-link">Destacados</a>
                    <a href="#catalogo" class="nav-link">Catálogo</a>
                    <a href="#contacto" class="nav-link">Contacto</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Banner Compacto -->
    <?php if ($result_noticias && $result_noticias->num_rows > 0): ?>
    <section class="hero-banner" id="home">
        <div class="container">
            <div class="swiper swiper-hero">
                <div class="swiper-wrapper">
                    <?php while ($noticia = $result_noticias->fetch_assoc()): ?>
                    <div class="swiper-slide">
                        <div class="banner-content">
                            <?php if ($noticia['tipo']): ?>
                            <span class="banner-tag">
                                <?php echo $noticia['tipo'] == 'descuento' ? 'Oferta' : ucfirst($noticia['tipo']); ?>
                            </span>
                            <?php endif; ?>
                            <h1 class="banner-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h1>
                            <?php if ($noticia['subtitulo']): ?>
                            <p class="banner-subtitle"><?php echo htmlspecialchars($noticia['subtitulo']); ?></p>
                            <?php endif; ?>
                            <a href="#catalogo" class="banner-btn">Ver Más</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products Carousel -->
    <?php if ($result_destacados->num_rows > 0): ?>
    <section class="featured-section" id="destacados">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Productos Destacados</h2>
                <p class="section-subtitle">Nuestra selección especial</p>
            </div>
            
            <div class="swiper swiper-products">
                <div class="swiper-wrapper">
                    <?php while ($producto = $result_destacados->fetch_assoc()): ?>
                    <div class="swiper-slide">
                        <article class="product-card">
                            <span class="product-badge">Destacado</span>
                            <div class="product-image">
                                <?php if ($producto['imagen']): ?>
                                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light); font-size: 3rem;">
                                        👟
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <?php if ($producto['marca_nombre']): ?>
                                <p class="product-brand"><?php echo htmlspecialchars($producto['marca_nombre']); ?></p>
                                <?php endif; ?>
                                <h3 class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p class="product-price">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
                                <a href="producto.php?id=<?php echo $producto['id']; ?>" class="product-btn">Ver Detalle</a>
                                <a href="<?php echo getWhatsAppProductUrl($producto['nombre'], $producto['precio'], $producto['marca_nombre']); ?>" 
                                   class="product-whatsapp" 
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.89 3.594z"/>
                                    </svg>
                                    Consultar
                                </a>
                            </div>
                        </article>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Catalog with Sidebar -->
    <section class="catalog-section" id="catalogo">
        <div class="container">
            <div class="catalog-header">
                <h2 class="section-title">Catálogo Completo</h2>
                <p class="section-subtitle">Encuentra tu zapatilla ideal</p>
            </div>

            <div class="catalog-container">
                <!-- Sidebar Filters -->
                <aside class="filters-sidebar">
                    <form method="GET" action="index.php#catalogo" id="filters-form">
                        <!-- Búsqueda -->
                        <div class="filter-section">
                            <h3 class="filter-title">Buscar</h3>
                            <input type="text" 
                                   name="buscar" 
                                   class="search-box" 
                                   placeholder="Buscar productos..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <!-- Marcas -->
                        <div class="filter-section">
                            <h3 class="filter-title">Marcas</h3>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="marca_todas" 
                                           name="marca" 
                                           value="0" 
                                           <?php echo $marca_filter == 0 ? 'checked' : ''; ?>>
                                    <label for="marca_todas">Todas las marcas</label>
                                </div>
                                <?php 
                                mysqli_data_seek($result_marcas, 0);
                                while ($marca = $result_marcas->fetch_assoc()): 
                                ?>
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="marca_<?php echo $marca['id']; ?>" 
                                           name="marca" 
                                           value="<?php echo $marca['id']; ?>"
                                           <?php echo $marca_filter == $marca['id'] ? 'checked' : ''; ?>>
                                    <label for="marca_<?php echo $marca['id']; ?>">
                                        <?php echo htmlspecialchars($marca['nombre']); ?>
                                    </label>
                                    <span class="filter-count">(<?php echo $marca['total']; ?>)</span>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- Precio -->
                        <div class="filter-section">
                            <h3 class="filter-title">Rango de Precio</h3>
                            <div class="price-range">
                                <input type="number" 
                                       name="precio_min" 
                                       class="price-input" 
                                       placeholder="Mín"
                                       value="<?php echo $precio_min > 0 ? $precio_min : ''; ?>">
                                <input type="number" 
                                       name="precio_max" 
                                       class="price-input" 
                                       placeholder="Máx"
                                       value="<?php echo $precio_max < 999999 ? $precio_max : ''; ?>">
                            </div>
                        </div>

                        <!-- Ordenar -->
                        <div class="filter-section">
                            <h3 class="filter-title">Ordenar por</h3>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="orden_recientes" 
                                           name="orden" 
                                           value="recientes"
                                           <?php echo $orden == 'recientes' ? 'checked' : ''; ?>>
                                    <label for="orden_recientes">Más recientes</label>
                                </div>
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="orden_precio_asc" 
                                           name="orden" 
                                           value="precio_asc"
                                           <?php echo $orden == 'precio_asc' ? 'checked' : ''; ?>>
                                    <label for="orden_precio_asc">Menor precio</label>
                                </div>
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="orden_precio_desc" 
                                           name="orden" 
                                           value="precio_desc"
                                           <?php echo $orden == 'precio_desc' ? 'checked' : ''; ?>>
                                    <label for="orden_precio_desc">Mayor precio</label>
                                </div>
                                <div class="filter-option">
                                    <input type="radio" 
                                           id="orden_nombre" 
                                           name="orden" 
                                           value="nombre"
                                           <?php echo $orden == 'nombre' ? 'checked' : ''; ?>>
                                    <label for="orden_nombre">Nombre A-Z</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-apply-filters">Aplicar Filtros</button>
                        <?php if ($marca_filter > 0 || !empty($search) || $precio_min > 0 || ($precio_max > 0 && $precio_max < 999999999)): ?>
                        <a href="index.php#catalogo" class="btn-clear-filters">Limpiar Filtros</a>
                        <?php endif; ?>
                    </form>
                </aside>

                <!-- Products Grid -->
                <div class="products-container">
                    <div class="sort-bar">
                        <span class="results-count">
                            <?php 
                            $inicio = $offset + 1;
                            $fin = min($offset + $productos_por_pagina, $total_productos);
                            if ($total_productos > 0) {
                                echo "Mostrando $inicio-$fin de $total_productos productos";
                            } else {
                                echo "No se encontraron productos";
                            }
                            ?>
                        </span>
                    </div>

                    <div class="products-grid">
                        <?php if ($result_productos->num_rows > 0): ?>
                            <?php while ($producto = $result_productos->fetch_assoc()): ?>
                            <article class="product-card">
                                <div class="product-image">
                                    <?php if ($producto['imagen']): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light); font-size: 3rem;">
                                            👟
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <?php if ($producto['marca_nombre']): ?>
                                    <p class="product-brand"><?php echo htmlspecialchars($producto['marca_nombre']); ?></p>
                                    <?php endif; ?>
                                    <h3 class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="product-price">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="product-btn">Ver Detalle</a>
                                    <a href="<?php echo getWhatsAppProductUrl($producto['nombre'], $producto['precio'], $producto['marca_nombre']); ?>" 
                                       class="product-whatsapp" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.89 3.594z"/>
                                        </svg>
                                        Consultar
                                    </a>
                                </div>
                            </article>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                                <p style="color: var(--text-secondary); font-size: 1.1rem;">
                                    No se encontraron productos con los filtros seleccionados.
                                </p>
                                <a href="index.php#catalogo" style="color: var(--primary); text-decoration: underline; margin-top: 16px; display: inline-block;">
                                    Ver todos los productos
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($total_paginas > 1): ?>
                    <!-- Controles de Paginación -->
                    <div class="pagination">
                        <?php
                        // Construir URL base con todos los parámetros actuales excepto 'pagina'
                        $params = $_GET;
                        unset($params['pagina']);
                        $base_url = 'index.php?' . http_build_query($params);
                        if (!empty($params)) {
                            $base_url .= '&';
                        } else {
                            $base_url = 'index.php?';
                        }
                        ?>
                        
                        <?php if ($pagina_actual > 1): ?>
                            <a href="<?php echo $base_url; ?>pagina=1#catalogo" class="pagination-btn">
                                <span style="font-size: 1.2rem;">«</span>
                            </a>
                            <a href="<?php echo $base_url; ?>pagina=<?php echo $pagina_actual - 1; ?>#catalogo" class="pagination-btn">
                                ‹ Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Mostrar números de página
                        $rango = 2; // Páginas a mostrar a cada lado de la actual
                        $inicio_pag = max(1, $pagina_actual - $rango);
                        $fin_pag = min($total_paginas, $pagina_actual + $rango);
                        
                        if ($inicio_pag > 1): ?>
                            <a href="<?php echo $base_url; ?>pagina=1#catalogo" class="pagination-number">1</a>
                            <?php if ($inicio_pag > 2): ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="pagination-number active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $base_url; ?>pagina=<?php echo $i; ?>#catalogo" class="pagination-number">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($fin_pag < $total_paginas): ?>
                            <?php if ($fin_pag < $total_paginas - 1): ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                            <a href="<?php echo $base_url; ?>pagina=<?php echo $total_paginas; ?>#catalogo" class="pagination-number">
                                <?php echo $total_paginas; ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo $base_url; ?>pagina=<?php echo $pagina_actual + 1; ?>#catalogo" class="pagination-btn">
                                Siguiente ›
                            </a>
                            <a href="<?php echo $base_url; ?>pagina=<?php echo $total_paginas; ?>#catalogo" class="pagination-btn">
                                <span style="font-size: 1.2rem;">»</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Botón flotante de WhatsApp -->
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
                        <li><a href="#home">Inicio</a></li>
                        <li><a href="#destacados">Destacados</a></li>
                        <li><a href="#catalogo">Catálogo</a></li>
                    </ul>
                </div>
                <!-- Sección comentada para futuro desarrollo
                <div>
                    <h4 class="footer-title">Información</h4>
                    <ul class="footer-links">
                        <li><a href="#">Sobre Nosotros</a></li>
                        <li><a href="#">Términos</a></li>
                        <li><a href="#">Privacidad</a></li>
                    </ul>
                </div>
                -->
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
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
        
        // Hero Banner Swiper
        const heroSwiper = new Swiper('.swiper-hero', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-hero .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-hero .swiper-button-next',
                prevEl: '.swiper-hero .swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
        });

        // Products Carousel
        const productsSwiper = new Swiper('.swiper-products', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: {
                el: '.swiper-products .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-products .swiper-button-next',
                prevEl: '.swiper-products .swiper-button-prev',
            },
            breakpoints: {
                480: {
                    slidesPerView: 2,
                    spaceBetween: 15,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 20,
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 24,
                }
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight - 20;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Auto-submit filters on change
        const filterInputs = document.querySelectorAll('.filter-option input');
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Pequeño delay para mejor UX
                setTimeout(() => {
                    document.getElementById('filters-form').submit();
                }, 300);
            });
        });
    </script>
</body>
</html>