<nav class="sidebar">
    <div class="sidebar-header">
        <h2>👟 Catálogo</h2>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="/corbatastore/web/admin/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && !strpos($_SERVER['REQUEST_URI'], 'productos') && !strpos($_SERVER['REQUEST_URI'], 'descuentos') ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="/corbatastore/web/admin/productos/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'productos') !== false ? 'active' : ''; ?>">📦 Productos</a></li>
        <li><a href="/corbatastore/web/admin/marcas/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'marcas') !== false ? 'active' : ''; ?>">🏷️ Marcas</a></li>
        <li><a href="/corbatastore/web/admin/descuentos/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'descuentos') !== false ? 'active' : ''; ?>">💰 Descuentos</a></li>
    </ul>
</nav>