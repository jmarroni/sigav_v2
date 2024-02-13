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
$sql = "SELECT v.precio as precio_unidad,
            v.precio as precio_mayorista,
            v.cantidad,
            v.costo,
            v.tipo_pago,
            p.nombre as producto_nombre,
            i.imagen_url as imagen,
            f.*
        FROM ventas v
            INNER JOIN productos p
            ON p.`id`=v.`productos_id`
                INNER JOIN factura f
                ON v.`factura_id` = f.`id`
                    LEFT JOIN imagen_producto i
                    ON i.productos_id = p.id
        WHERE f.`id` = ".intval($_POST["id"]);

$resultado = $conn->query($sql);
$datos = array("data" => "no-data");
if ($resultado->num_rows > 0) {
    // output data of each row
    $datos = array();
    $preciototal = 0;
    while($row = $resultado->fetch_assoc()) {
        $row["precio_unidad"] = ($_COOKIE["lista_precio"] == 1)?$row["precio_unidad"]:$row["precio_mayorista"];
        $costo = $row["costo"];
        $precio = $row["precio_unidad"];
        $row["stock_sucursal"] =  "N/A";
        $row["imagen"] = (isset($row["imagen"]))?$row["imagen"]:"http://".$_SERVER["HTTP_HOST"]."/assets/img/photos/no-image-featured-image.png";
        $datos["items"][] = $row;
        $preciototal += $precio * $row["cantidad"];
    }
    $datos["total"] = $preciototal;
    $datos["fecha"] = date("Y-m-d H:i:s");
    $datos["usuario"] = $_COOKIE["kiosco"];
} else {
    echo "0 results";
}
echo json_encode($datos,true);
$conn->close();
exit();
?>
?>