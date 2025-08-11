# 🚀 Configuración para Producción - Corbata Store

## ⚠️ PASOS OBLIGATORIOS ANTES DE SUBIR

### 1. **Cambiar número de WhatsApp**
```php
// Archivo: config/whatsapp.php
define('WHATSAPP_NUMBER', 'TU_NUMERO_REAL'); // Ejemplo: 5491123456789
```

### 2. **Configurar .env para producción**
```env
# Cambiar esto:
APP_ENV=production
APP_DEBUG=false

# Cambiar credenciales admin:
ADMIN_USERNAME=tu_usuario_seguro
ADMIN_PASSWORD=tu_password_seguro_123
```

### 3. **En el servidor de producción:**
- Ejecutar `database.sql` para crear las tablas
- Ejecutar `updates_db.sql` para actualizaciones
- Crear usuario admin con la nueva contraseña

### 4. **Archivos a NO subir:**
- ❌ `test*.php`
- ❌ `setup*.php`  
- ❌ `CONFIGURAR_PRODUCCION.md` (este archivo)
- ❌ `ARCHIVOS_PARA_ELIMINAR.txt`
- ❌ `.env` (crear manualmente en servidor)

### 5. **Permisos en servidor:**
```bash
chmod 755 uploads/
chmod 755 uploads/productos/
chmod 755 uploads/marcas/
chmod 644 .htaccess
chmod 600 .env
```

### 6. **URLs después del deployment:**
- **Sitio público:** https://tudominio.com
- **Admin:** https://tudominio.com/admin
- **Credenciales:** Las que configures en .env

### 7. **Verificar que funcione:**
- [ ] Página principal carga
- [ ] Productos se muestran
- [ ] Filtros funcionan  
- [ ] Paginación funciona
- [ ] WhatsApp abre correctamente
- [ ] Admin login funciona
- [ ] Crear/editar productos funciona

## 🎯 Tu sistema está listo para producción!

### Características implementadas:
✅ Catálogo completo con filtros y paginación
✅ Panel admin con gestión de productos, marcas y descuentos
✅ Sistema de precios automático configurable  
✅ Integración WhatsApp completa
✅ Diseño responsive y elegante
✅ Sistema de seguridad implementado
✅ Configuración dual local/producción

### Soporte post-deployment:
- Revisar logs de error regularmente
- Hacer backups de la base de datos
- Mantener actualizada la configuración de precios
- Revisar y actualizar descuentos periódicamente