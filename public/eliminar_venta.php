<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");

// Validar parámetros
$venta_id = intval($_POST["id"] ?? 0);
$producto_id = intval($_POST["producto_id"] ?? 0);

if ($venta_id <= 0 || $producto_id <= 0) {
    echo json_encode(["error" => "Parámetros inválidos"]);
    exit();
}

// Eliminar rows que tengan como venta_id la venta a eliminar
$stmt = $conn->prepare("DELETE FROM productos_en_carrito WHERE venta_id = ? AND producto_id = ?");
$stmt->bind_param("ii", $venta_id, $producto_id);

if ($stmt->execute()) {
    echo json_encode(["respuesta" => "OK"]);
} else {
    echo json_encode(["error" => "Error al eliminar"]);
}

$stmt->close();
$conn->close();
exit();
?>
