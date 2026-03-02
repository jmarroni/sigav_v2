<?php
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
    exit();
}

if (isset($_GET["id"])) $_POST["id"] = intval($_GET["id"]);
$sql = "SELECT *
        FROM nota_de_credito nc
        WHERE `id` = ".intval($_POST["id"]);

$resultado = $conn->query($sql);
$datos = array("data" => "no-data");
if ($resultado->num_rows > 0) {
    // output data of each row
    $datos = array();
    $preciototal = 0;
    while($row = $resultado->fetch_assoc()) {
        $datos["total"] = $row["total"];
        $datos["fecha"] = substr($row["fecha"],0,10);
        $datos["usuario"] = $_COOKIE["kiosco"];
    }

} else {
    echo "0 results";
}
echo json_encode($datos);
$conn->close();
exit();

?>