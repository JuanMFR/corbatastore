-- Base de datos para catálogo de zapatillas
CREATE DATABASE IF NOT EXISTS corbatastore;
USE corbatastore;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar un admin por defecto (contraseña: admin123)
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$YbO3z.xGxWXL1lKvfLfYaOLnMxLj6P0Ht8JXs3jK9w8CZdmhHnlVa');

-- Tabla de marcas
CREATE TABLE IF NOT EXISTS marcas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    marca_id INT,
    precio DECIMAL(10,2) NOT NULL,
    talles TEXT,
    descripcion TEXT,
    destacado BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL
);

-- Tabla de imágenes de productos
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    imagen_path VARCHAR(500) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    orden INT DEFAULT 0,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Insertar algunas marcas de ejemplo
INSERT INTO marcas (nombre) VALUES 
('Nike'),
('Adidas'),
('Puma'),
('Reebok'),
('New Balance'),
('Converse'),
('Vans'),
('Fila');