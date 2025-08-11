# Guía de Navegación - Corbata Store

## 🏠 Páginas Públicas

### 1. **Página Principal** (`/index.php`)
- Catálogo completo de productos
- Carrusel de productos destacados
- Filtros por marca, precio y talle
- Sistema de descuentos activos

### 2. **Detalle de Producto** (`/producto.php?id=X`)
- Información completa del producto
- Galería de imágenes
- Precio con descuento (si aplica)
- Talles disponibles

## 🔐 Panel de Administración

### Acceso
- **URL:** `/admin/`
- **Usuario:** admin
- **Contraseña:** corbata2024

> ⚠️ **IMPORTANTE:** Cambiar estas credenciales en producción editando el archivo `.env`

### Secciones del Admin

#### 1. **Dashboard** (`/admin/index.php`)
- Resumen de estadísticas
- Total de productos, marcas y descuentos
- Accesos rápidos a las secciones principales

#### 2. **Productos** (`/admin/productos/`)
- **Listado:** `/admin/productos/index.php`
  - Ver todos los productos
  - Buscar y filtrar
  - Activar/desactivar productos
  
- **Agregar:** `/admin/productos/agregar_nuevo.php`
  - Crear nuevo producto
  - Cálculo automático de precio
  - Subir múltiples imágenes
  - Marcar como destacado
  
- **Editar:** `/admin/productos/editar.php?id=X`
  - Modificar información del producto
  - Gestionar imágenes
  - Actualizar precios y talles

#### 3. **Marcas** (`/admin/marcas/`)
- **Listado:** `/admin/marcas/index.php`
  - Ver todas las marcas
  - Cantidad de productos por marca
  
- **Crear:** `/admin/marcas/crear.php`
  - Agregar nueva marca
  - Subir logo (opcional)
  
- **Editar:** `/admin/marcas/editar.php?id=X`
  - Modificar información de la marca

#### 4. **Descuentos** (`/admin/descuentos/`)
- **Listado:** `/admin/descuentos/index.php`
  - Ver todos los descuentos
  - Estado activo/inactivo
  - Fechas de vigencia
  
- **Crear:** `/admin/descuentos/crear.php`
  - Crear descuento por marca
  - Crear descuento por productos específicos
  - Configurar fechas de vigencia
  
- **Configuración:** `/admin/descuentos/configuracion.php`
  - Ajustar porcentaje de ganancia
  - Configurar costos fijos (caja y envío)
  - Ver fórmula de cálculo de precios

## 📊 Fórmula de Cálculo de Precios

```
Precio Final = (Costo + $800 + $1500) × 1.5
```

Donde:
- **Costo:** Precio de compra del producto
- **$800:** Costo de caja/empaque
- **$1500:** Costo promedio de envío
- **1.5:** Factor de ganancia (50%)

Estos valores son configurables desde `/admin/descuentos/configuracion.php`

## 🗄️ Base de Datos

### Tablas Principales
- `productos`: Catálogo de productos
- `marcas`: Marcas disponibles
- `producto_imagenes`: Imágenes de productos
- `descuentos`: Descuentos configurados
- `descuento_productos`: Relación descuento-producto
- `configuracion`: Parámetros del sistema
- `admins`: Usuarios administradores

## 🔧 Configuración

### Archivo `.env`
Contiene las credenciales y configuraciones sensibles:
- Credenciales de base de datos (local y producción)
- Credenciales de administrador
- Configuración de sesiones

### Detección de Entorno
El sistema detecta automáticamente si está en:
- **Local:** localhost o 127.0.0.1
- **Producción:** Cualquier otro dominio

## 📱 Características Responsive
- Diseño adaptativo para móviles, tablets y desktop
- Menú lateral colapsable en admin
- Carrusel táctil en móviles
- Filtros optimizados para pantallas pequeñas

## 🎨 Paleta de Colores
- **Principal:** Marrón (#8B4513, #6B3410)
- **Fondo:** Crema claro (#FFF8F0)
- **Acentos:** Dorado (#D4A574)
- **Texto:** Gris oscuro (#333333)

## 🚀 Funcionalidades Destacadas
1. **Cálculo automático de precios** basado en costos
2. **Sistema de descuentos** por fecha y tipo
3. **Productos destacados** en carrusel principal
4. **Filtros instantáneos** sin recarga de página
5. **Gestión de múltiples imágenes** por producto
6. **Configuración dual** para desarrollo y producción

## 📝 Notas Importantes
- Las imágenes se suben a `/uploads/productos/`
- Los logos de marcas van a `/uploads/marcas/`
- El sistema redimensiona automáticamente las imágenes grandes
- Los descuentos se activan/desactivan automáticamente por fecha
- Los productos inactivos no se muestran en el catálogo público