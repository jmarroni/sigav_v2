<?php
header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
require_once ("conection.php");

$sql = "SELECT
	  SUM(precio) AS precios,
	  COUNT(id) AS cantidad,
	  usuario
	  FROM
	  ventas
	  where fecha > '".date("Y-m-d")."' AND sucursal_id = ".$_GET["sucursal"]." 
	  GROUP BY usuario";
	  
$resultado_3d= $conn->query($sql);
if ($resultado_3d->num_rows > 0) {
  echo "[{'Usuario', 'Monto'},";
  $i =0;
    while($row_3d = $resultado_3d->fetch_assoc()) {
        if ($i > 0) echo ",";
        echo "{'".$row_3d["usuario"]."','".$row_3d["precios"]."'}";
        
        $i ++;
    }
echo "]";
}