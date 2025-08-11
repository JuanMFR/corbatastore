<?php
require_once 'config/database.php';

$conn = getConnection();

echo "<h2>Test de Productos y Filtros</h2>";

// 1. Verificar productos activos
$query = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();
echo "<p><strong>Productos activos:</strong> " . $row['total'] . "</p>";

// 2. Verificar productos por marca
$query = "SELECT m.nombre, COUNT(p.id) as total 
          FROM marcas m 
          LEFT JOIN productos p ON m.id = p.marca_id AND p.activo = 1
          GROUP BY m.id";
$result = $conn->query($query);
echo "<p><strong>Productos por marca:</strong></p>";
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . $row['nombre'] . ": " . $row['total'] . " productos</li>";
}
echo "</ul>";

// 3. Verificar rango de precios
$query = "SELECT MIN(precio) as min_precio, MAX(precio) as max_precio FROM productos WHERE activo = 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();
echo "<p><strong>Rango de precios:</strong> $" . number_format($row['min_precio'], 0) . " - $" . number_format($row['max_precio'], 0) . "</p>";

// 4. Listar algunos productos de ejemplo
$query = "SELECT p.*, m.nombre as marca_nombre 
          FROM productos p
          LEFT JOIN marcas m ON p.marca_id = m.id
          WHERE p.activo = 1
          LIMIT 5";
$result = $conn->query($query);
echo "<p><strong>Primeros 5 productos:</strong></p>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Precio</th><th>Activo</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['nombre'] . "</td>";
    echo "<td>" . ($row['marca_nombre'] ?: 'Sin marca') . "</td>";
    echo "<td>$" . number_format($row['precio'], 0) . "</td>";
    echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Test de filtro por marca
if (isset($_GET['test_marca'])) {
    $marca_id = intval($_GET['test_marca']);
    $query = "SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND marca_id = $marca_id";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    echo "<p><strong>Productos con marca_id = $marca_id:</strong> " . $row['total'] . "</p>";
}

$conn->close();
?>

<br><br>
<p><a href="index.php">Volver al catálogo</a></p>