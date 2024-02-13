<?php
if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
    header('Location: /');
}
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once ("conection.php");
$sql = "SELECT 
            *
            FROM proveedor prov
            order by nombre ";
$resultado = $conn->query($sql) or die($conn->error);
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
// output data of each row
while ($row = $resultado->fetch_assoc()) {
    $proveedores[] = $row;
}
echo json_encode($proveedores);
}else{ echo "{}"; }
$conn->close();

exit();
?>