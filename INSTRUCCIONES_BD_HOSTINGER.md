# 📋 INSTRUCCIONES PARA CONFIGURAR LA BASE DE DATOS EN HOSTINGER

## 🎯 Resumen Rápido
Tu base de datos en Hostinger está vacía. Usa el archivo **`database_completa_hostinger.sql`** para crear todas las tablas necesarias de una sola vez.

## 📝 Paso a Paso

### 1️⃣ Acceder a phpMyAdmin
1. Entra a tu panel de Hostinger (hPanel)
2. Ve a **Databases** → **phpMyAdmin**
3. Ingresa con tus credenciales de MySQL

### 2️⃣ Seleccionar tu Base de Datos
1. En el panel izquierdo, haz clic en tu base de datos: **u884501120_corbatadb**
2. Verás que está vacía (sin tablas)

### 3️⃣ Ejecutar el Script SQL
#### Opción A: Copiar y Pegar
1. Haz clic en la pestaña **"SQL"** (arriba)
2. Abre el archivo `database_completa_hostinger.sql` en un editor de texto
3. Copia TODO el contenido (Ctrl+A, Ctrl+C)
4. Pégalo en el campo de texto de phpMyAdmin
5. Haz clic en **"Go"** o **"Ejecutar"**

#### Opción B: Importar Archivo
1. Haz clic en la pestaña **"Import"** (arriba)
2. Clic en **"Choose File"** o **"Examinar"**
3. Selecciona `database_completa_hostinger.sql`
4. Asegúrate que el formato sea **SQL**
5. Haz clic en **"Go"** o **"Ejecutar"**

### 4️⃣ Verificar la Creación
Después de ejecutar, deberías ver:
- ✅ **14 tablas creadas** en el panel izquierdo
- ✅ Mensaje de éxito en verde
- ✅ Las tablas: admin, marcas, productos, producto_imagenes, configuracion, descuentos, noticias, etc.

### 5️⃣ Crear Usuario Administrador
Como no incluimos datos de ejemplo, necesitas crear el usuario admin:

#### Opción A: Usando SQL en phpMyAdmin
```sql
INSERT INTO admin (username, password) VALUES 
('admin', '$2y$10$YbO3z.xGxWXL1lKvfLfYaOLnMxLj6P0Ht8JXs3jK9w8CZdmhHnlVa');
```
**Credenciales:** admin / admin123

#### Opción B: Usando setup_admin.php (más fácil)
1. Sube temporalmente el archivo `setup_admin.php` a tu hosting
2. Accede a: `https://tudominio.com/setup_admin.php`
3. Se creará el usuario automáticamente
4. **ELIMINA setup_admin.php inmediatamente**

### 6️⃣ Configuración Inicial (Opcional)
Si necesitas valores de configuración iniciales, ejecuta:
```sql
INSERT INTO configuracion (clave, valor, descripcion, categoria) VALUES 
('porcentaje_ganancia', '50', 'Porcentaje de ganancia sobre el costo total', 'precios'),
('costo_caja', '800', 'Costo fijo de caja por producto', 'precios'),
('costo_envio', '1500', 'Costo promedio de envío por producto', 'precios');
```

## ✅ Checklist de Verificación

- [ ] Base de datos seleccionada correctamente
- [ ] Script SQL ejecutado sin errores
- [ ] 14 tablas creadas
- [ ] Usuario admin creado
- [ ] Puedo acceder a `/admin` con las credenciales

## 🚨 Solución de Problemas

### Error: "Table already exists"
- No es un problema, las tablas ya están creadas
- El script usa `IF NOT EXISTS` para evitar duplicados

### Error: "Access denied"
- Verifica que estés usando las credenciales correctas
- Confirma el nombre de la base de datos

### Error al ejecutar el script
- Intenta ejecutar por partes si es muy grande
- Primero las tablas principales (admin, marcas, productos)
- Luego las secundarias

### No puedo entrar al admin
1. Verifica que la tabla `admin` tenga al menos un usuario
2. Usa `setup_admin.php` para crear el usuario
3. La contraseña por defecto es `admin123`

## 📌 Notas Importantes

- **NO hay datos de ejemplo** - La BD estará vacía (solo estructura)
- **Agrega todo desde el admin** - Marcas, productos, noticias, etc.
- **Cambia la contraseña** del admin inmediatamente
- **Elimina archivos de setup** después de usarlos

## 🎯 Siguiente Paso

Una vez creadas las tablas:
1. Accede a `https://tudominio.com/admin`
2. Inicia sesión con admin/admin123
3. Cambia la contraseña
4. Comienza a agregar marcas y productos

---
**¡Tu base de datos está lista para usar! 🚀**