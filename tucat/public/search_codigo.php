<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");

$sql = "SELECT * FROM productos WHERE codigo_barras = '".$_GET["term"]."'";
$resultado = $conn->query($sql);
$i = 0;
if ($resultado->num_rows > 0) {
    // output data of each row
    while($row = $resultado->fetch_assoc()) {
        $datos[$i]["value"] = $row["nombre"];
        $datos[$i]["label"] = $row["nombre"];
        $datos[$i]["id"] = $row["id"];
        $datos[$i]["precio"] = $row["precio_unidad"];
        $i ++;
    }
    echo json_encode($datos);
} else {
    $datos = array("data" => "no data");
    echo "[]";
}
$conn->close();
exit();
?>