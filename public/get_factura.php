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

$factura_id = isset($_GET["id"]) ? intval($_GET["id"]) : intval($_POST["id"] ?? 0);

$stmt = $conn->prepare("SELECT v.precio as precio_unidad,
            v.precio as precio_mayorista,
            v.cantidad,
            v.costo,
            v.tipo_pago,
            p.nombre as producto_nombre,
            i.imagen_url as imagen,
            f.*
        FROM ventas v
            INNER JOIN productos p ON p.`id` = v.`productos_id`
            INNER JOIN factura f ON v.`factura_id` = f.`id`
            LEFT JOIN imagen_producto i ON i.productos_id = p.id
        WHERE f.`id` = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$resultado = $stmt->get_result();

$datos = array("data" => "no-data");
if ($resultado->num_rows > 0) {
    $datos = array();
    $preciototal = 0;
    $lista_precio = $_COOKIE["lista_precio"] ?? 1;

    while($row = $resultado->fetch_assoc()) {
        $row["precio_unidad"] = ($lista_precio == 1) ? $row["precio_unidad"] : $row["precio_mayorista"];
        $costo = $row["costo"];
        $precio = $row["precio_unidad"];
        $row["stock_sucursal"] = "N/A";
        $row["imagen"] = isset($row["imagen"]) ? $row["imagen"] : "http://" . $_SERVER["HTTP_HOST"] . "/assets/img/photos/no-image-featured-image.png";
        $datos["items"][] = $row;
        $preciototal += $precio * $row["cantidad"];
    }
    $datos["total"] = $preciototal;
    $datos["fecha"] = date("Y-m-d H:i:s");
    $datos["usuario"] = $_COOKIE["kiosco"];
} else {
    echo json_encode(["data" => "0 results"]);
    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode($datos);
$stmt->close();
$conn->close();
exit();
?>
