<?php
require_once '../check_session.php';
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;

if ($id == 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT imagen_path FROM producto_imagenes WHERE producto_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $file_path = '../../' . $row['imagen_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}
$stmt->close();

$stmt = $conn->prepare("DELETE FROM producto_imagenes WHERE producto_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$conn->close();

header('Location: index.php?mensaje=' . urlencode('Producto eliminado exitosamente'));
exit();
?>