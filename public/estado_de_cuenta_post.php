<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 5) {
    exit();
}
$estado = intval($_GET["estado"]);
$fecha_pago = $_GET["fecha_pago"] ?? null;
$medio_pago = intval($_GET["medio_pago"] ?? 0);
$estado_nuevo = intval($_GET["estado_nuevo"]);

// Usar prepared statement para evitar SQL injection
$stmt = $conn->prepare("UPDATE estados_contables SET estado = ?, fecha_pago = ?, medio_pago = ? WHERE id = ?");
$stmt->bind_param("isii", $estado_nuevo, $fecha_pago, $medio_pago, $estado);
if ($stmt->execute()) {
   $mensaje = "Estado Actualizado";
} else {
    $mensaje = "Error al actualizar estado";
}
$stmt->close();
$conn->close();
echo json_encode(array("mensaje" => $mensaje));
exit();
?>