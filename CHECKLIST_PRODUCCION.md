# ✅ Checklist de Despliegue a Producción - Corbata Store

## 📋 Antes de Subir

### 1. Configuración de Archivos
- [ ] **Editar `.env`** con las credenciales de producción
  - [ ] Cambiar `APP_ENV=production`
  - [ ] Cambiar `APP_DEBUG=false`
  - [ ] Actualizar credenciales de base de datos
  - [ ] Cambiar usuario y contraseña de admin
  
- [ ] **Eliminar `.env` del repositorio** (nunca subir a git)

### 2. Base de Datos
- [ ] Ejecutar `database.sql` en el servidor de producción
- [ ] Ejecutar `updates_db.sql` para las actualizaciones
- [ ] Verificar que todas las tablas se crearon correctamente
- [ ] Insertar usuario administrador con contraseña segura

### 3. Archivos a NO Subir
- [ ] `.env` (contiene credenciales)
- [ ] `test_*.php` (archivos de prueba)
- [ ] `/uploads/productos/*` (imágenes de prueba local)
- [ ] Archivos `.sql` después de ejecutarlos

## 🚀 Durante el Despliegue

### 1. Estructura de Carpetas
```
public_html/
├── admin/
│   ├── css/
│   ├── productos/
│   ├── marcas/
│   ├── descuentos/
│   └── includes/
├── config/
├── css/
├── js/
├── images/
├── uploads/
│   ├── productos/
│   └── marcas/
├── .htaccess
├── .env (crear manualmente)
└── index.php
```

### 2. Permisos de Carpetas
```bash
# Dar permisos de escritura a carpetas de uploads
chmod 755 uploads/
chmod 755 uploads/productos/
chmod 755 uploads/marcas/

# Proteger archivos sensibles
chmod 644 .htaccess
chmod 600 .env
chmod 644 config/*.php
```

### 3. Configuración del Servidor

#### Apache `.htaccess`
- [ ] Verificar que mod_rewrite está habilitado
- [ ] Verificar que mod_headers está habilitado
- [ ] Descomentar líneas de HTTPS cuando SSL esté configurado

#### PHP
- [ ] PHP versión 7.4 o superior
- [ ] Extensiones requeridas:
  - [ ] mysqli
  - [ ] gd (para imágenes)
  - [ ] fileinfo
  - [ ] session

#### SSL/HTTPS
- [ ] Instalar certificado SSL
- [ ] Configurar redirección HTTP → HTTPS
- [ ] Actualizar `.htaccess` para forzar HTTPS

## 🔒 Seguridad Post-Despliegue

### 1. Credenciales
- [ ] Cambiar contraseña de admin por defecto
- [ ] Crear usuario admin adicional de respaldo
- [ ] Eliminar usuario de prueba si existe

### 2. Protección de Admin
- [ ] Configurar `.htpasswd` para doble autenticación
- [ ] Limitar acceso por IP si es posible
- [ ] Configurar logs de acceso

### 3. Backups
- [ ] Configurar backup automático de base de datos
- [ ] Configurar backup de carpeta uploads/
- [ ] Documentar proceso de restauración

## ✔️ Verificación Final

### 1. Pruebas Funcionales
- [ ] **Catálogo Público**
  - [ ] Página principal carga correctamente
  - [ ] Productos se muestran con imágenes
  - [ ] Filtros funcionan
  - [ ] Carrusel de destacados funciona

- [ ] **Panel Admin**
  - [ ] Login funciona
  - [ ] Crear producto con imágenes
  - [ ] Editar producto existente
  - [ ] Crear/editar marca
  - [ ] Configurar descuento
  - [ ] Cerrar sesión funciona

### 2. Verificación de Seguridad
- [ ] No se muestran errores de PHP en producción
- [ ] Archivos `.env` no son accesibles vía web
- [ ] Directorio `/config` no es accesible
- [ ] No hay archivos de prueba accesibles

### 3. Performance
- [ ] Imágenes se cargan correctamente
- [ ] CSS y JS están minimizados
- [ ] Caché del navegador configurado
- [ ] Compresión GZIP activa

## 📞 Información de Contacto

### Soporte Técnico
- **Base de datos:** Hostinger MySQL
- **Usuario DB:** u884501120_corbata
- **Nombre DB:** u884501120_corbatadb

### URLs Importantes
- **Sitio público:** https://tudominio.com
- **Panel admin:** https://tudominio.com/admin
- **phpMyAdmin:** [URL de Hostinger]

## 🔄 Mantenimiento Regular

### Semanal
- [ ] Revisar logs de errores
- [ ] Verificar espacio en disco
- [ ] Revisar intentos de acceso fallidos

### Mensual
- [ ] Actualizar productos y precios
- [ ] Limpiar imágenes no utilizadas
- [ ] Revisar y actualizar descuentos
- [ ] Backup completo del sitio

### Trimestral
- [ ] Actualizar contraseñas
- [ ] Revisar y optimizar base de datos
- [ ] Actualizar documentación

## 📝 Notas Adicionales

1. **Nunca editar archivos directamente en producción**
   - Hacer cambios en local
   - Probar exhaustivamente
   - Subir vía FTP/Git

2. **Antes de grandes cambios**
   - Hacer backup completo
   - Probar en entorno de staging si es posible
   - Planificar ventana de mantenimiento

3. **En caso de problemas**
   - Revisar logs de error de PHP
   - Verificar conexión a base de datos
   - Confirmar permisos de archivos
   - Restaurar backup si es necesario

---
*Última actualización: [Fecha]*
*Versión: 1.0*