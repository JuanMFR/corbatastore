# Configuración de URL sin /web

## Para Producción (Hostinger)

Simplemente sube todo el contenido de la carpeta `web/` directamente a `public_html/`:

```bash
# Estructura en Hostinger:
public_html/
├── admin/
├── config/
├── css/
├── js/
├── images/
├── uploads/
├── .htaccess
├── .env
├── index.php
├── producto.php
└── (resto de archivos)
```

**URL resultante:** `https://tudominio.com` (sin /web)

## Para Desarrollo Local (XAMPP)

### Opción A: Mover archivos (Más simple)
1. Corta todo el contenido de `C:\xampp\htdocs\corbatastore\web\`
2. Pégalo directamente en `C:\xampp\htdocs\corbatastore\`
3. Elimina la carpeta `web` vacía
4. **URL resultante:** `http://localhost/corbatastore`

### Opción B: Configurar Virtual Host
1. Editar `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Agregar:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/corbatastore/web"
    ServerName corbatastore.local
    <Directory "C:/xampp/htdocs/corbatastore/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Editar `C:\Windows\System32\drivers\etc\hosts`
4. Agregar: `127.0.0.1 corbatastore.local`
5. Reiniciar Apache
6. **URL resultante:** `http://corbatastore.local`

## Resumen

- **En producción:** Sube directamente a `public_html/` → URL: `tusitio.com`
- **En local (simple):** Mueve archivos un nivel arriba → URL: `localhost/corbatastore`
- **En local (avanzado):** Configura Virtual Host → URL: `corbatastore.local`

No es necesario tener `/web` en la URL en ningún caso.