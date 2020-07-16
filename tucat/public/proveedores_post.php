<?php
if (!isset($_COOKIE["kiosco"])) {
	header('Location: /');
}
require_once ("conection.php");
$fecha = (isset($_POST["fecha"]) && $_POST["fecha"] != "")?$_POST["fecha"]:date("Y-m-d H:i:s");
$sql = "INSERT INTO pagos_a_proveedores VALUES (NULL, '{$_POST["proveedor"]}',
'{$_POST["monto"]}',
'".$fecha."',
'{$_COOKIE["kiosco"]}',
'{$_POST["operacion"]}',
'{$_POST["detalle"]}')";
if ($conn->query($sql) === TRUE) {
	header('Location: /proveedores.php?mensaje='.base64_encode("Se ingreso el pago {$_POST["proveedor"]} ok"));
} else {
	echo "Error: " . $sql . "<br>" . $conn->error;
}
$conn->close();
exit();
?>