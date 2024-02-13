<?php
$menu["ventas"] = "";
$menu["cargas"] = "";
$menu["reportes"] = "active";
$proveedor_id = 0;
require_once ("conection.php");
$proveedor = "";
if(isset($_POST["reporte_desde"])) $reporte_desde = $_POST["reporte_desde"];
if(isset($_POST["reporte_hasta"])) $reporte_hasta = $_POST["reporte_hasta"];
if(isset($_POST["proveedor"]) && $_POST["proveedor"] != 0){ $proveedor = " and pr.proveedores_id = ".$_POST["proveedor"]; $proveedor_id =$_POST["proveedor"];}
//Productos vendidos hoy
$sql = "SELECT * FROM `ventas` v inner join productos pr ON pr.id= v.productos_id  WHERE `fecha` > '".date("Y-m-d")."'".$proveedor;
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas = $resultado->num_rows; 
}else{
    $cantidad_de_ventas = 0;
}
//Productos vendidos hoy por el usuario
//Productos vendidos hoy por el usuario
if (isset($_POST["reporte_desde"]) && isset($_POST["reporte_hasta"]) ){
    $sql = "SELECT v.*,pr.*, v.fecha as vfecha FROM `ventas` v inner join productos pr ON pr.id= v.productos_id  where v.fecha between '".$_POST["reporte_desde"]."' AND v.sucursal_id = 3 AND '".$_POST["reporte_hasta"]."'".$proveedor;
} else $sql = "SELECT v.*,pr.*, v.fecha as vfecha FROM `ventas` v inner join productos pr ON pr.id= v.productos_id where v.fecha > '".date("Y-m-d")."' AND v.sucursal_id = 3 ".$proveedor;
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
$caja = 540;
//Registro de horas
$datos_horas = array();
$ganancia_total = 0;
$total_facturado = 0;
for ($i=0; $i < 24; $i++) {
    $datos_horas[$i]["precio"] = 0;
    $datos_horas[$i]["ganancia"] = 0;
}
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas_usuario = $resultado->num_rows; 
    while($row = $resultado->fetch_assoc()) {
            $hora = explode(':',explode(" ", $row["vfecha"])[1])[0];
            $datos_horas[intval($hora)]["precio"]   += $row["precio"] * $row["cantidad"];
            $datos_horas[intval($hora)]["ganancia"] += ($row["precio"] * $row["cantidad"] - $row["costo"] * $row["cantidad"]);
            $ganancia_total += ($row["precio"] * $row["cantidad"] - $row["costo"] * $row["cantidad"]);
            $total_facturado += $row["precio"] * $row["cantidad"];
    }
}


$tabla_facturacion_diaria = "<html><head></head><body>";
$tabla_facturacion_diaria .= "<h2>Ganancia: $ganancia_total Total: $total_facturado</h2>";
$tabla_facturacion_diaria .= "<table border='1'><tr><th style='width:50px;'>Hora</th><th style='width:100px;'>Venta</th><th style='width:100px;'>Ganancia</th></tr>";
for ($i=0; $i < 24; $i++) { 
    $tabla_facturacion_diaria .= "<tr><td>".$i."-".($i + 1)."</td><td style='text-align:right;'>".$datos_horas[$i]["precio"]."</td><td style='text-align:right;'>".$datos_horas[$i]["ganancia"]."</td></tr>";
}
$tabla_facturacion_diaria .= "</table></body></html>";
$to = "jmarroni@gmail.com";
$subject = "Facturacion hasta la hora ".date("H");
// Always set content-type when sending HTML email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
$headers .= 'From: <jmarroni@fidegroup.com.ar>' . "\r\n";

mail($to,$subject,$tabla_facturacion_diaria,$headers);
?>