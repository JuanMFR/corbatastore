-- Actualizaciones para sistema de descuentos y costos
USE corbatastore;

-- Agregar campo de costo a productos (si no existe)
ALTER TABLE productos 
ADD COLUMN IF NOT EXISTS costo DECIMAL(10,2) DEFAULT 0 AFTER precio,
ADD COLUMN IF NOT EXISTS precio_original DECIMAL(10,2) DEFAULT NULL AFTER precio;

-- Tabla de configuración global
CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor VARCHAR(255) NOT NULL,
    descripcion TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuraciones por defecto
INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('porcentaje_ganancia', '50', 'Porcentaje de ganancia sobre el costo total'),
('costo_caja', '800', 'Costo fijo de caja por producto'),
('costo_envio', '1500', 'Costo promedio de envío por producto')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Tabla de descuentos
CREATE TABLE IF NOT EXISTS descuentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    tipo ENUM('marca', 'producto', 'categoria') NOT NULL,
    valor_descuento DECIMAL(5,2) NOT NULL, -- Porcentaje de descuento
    marca_id INT DEFAULT NULL,
    producto_id INT DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla para productos en descuento (relación muchos a muchos)
CREATE TABLE IF NOT EXISTS descuento_productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    descuento_id INT NOT NULL,
    producto_id INT NOT NULL,
    FOREIGN KEY (descuento_id) REFERENCES descuentos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_descuento_producto (descuento_id, producto_id)
);

-- Función para calcular precio final (para referencia, se usará en PHP)
-- Precio Final = (Costo + Costo_Caja + Costo_Envío) * (1 + Porcentaje_Ganancia/100)