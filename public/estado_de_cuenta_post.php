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
$fecha_pago = $_GET["fecha_pago"];
$medio_pago = $_GET["medio_pago"];
$estado_nuevo = intval($_GET["estado_nuevo"]);
$sql = "UPDATE estados_contables SET estado = $estado_nuevo, fecha_pago = '$fecha_pago', medio_pago = '$medio_pago' WHERE id = ".$estado;
if ($conn->query($sql) === TRUE) {
   $id = $conn->insert_id;
   $mensaje = "Estado Actualizado";
}else{
    $mensaje = "error al insertar estado ".$sql;
}
$conn->close();
echo json_encode(array("mensaje" => $mensaje));
exit();
?>