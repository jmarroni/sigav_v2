<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");

// Validar y sanitizar datos
$fecha = (isset($_POST["fecha"]) && $_POST["fecha"] != "") ? $_POST["fecha"] : date("Y-m-d H:i:s");
$proveedor = intval($_POST["proveedor"] ?? 0);
$monto = floatval($_POST["monto"] ?? 0);
$usuario = $_COOKIE["kiosco"];
$operacion = $_POST["operacion"] ?? '';
$detalle = $_POST["detalle"] ?? '';

// Prepared statement para INSERT
$stmt = $conn->prepare("INSERT INTO pagos_a_proveedores VALUES (NULL, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("idssss", $proveedor, $monto, $fecha, $usuario, $operacion, $detalle);

if ($stmt->execute()) {
    header('Location: /proveedores.php?mensaje='.base64_encode("Se ingreso el pago correctamente"));
} else {
    echo json_encode(["error" => "Error al insertar pago"]);
}
$stmt->close();
$conn->close();
exit();
?>
