<?php
require_once ("../conection.php");
	$sql = "SELECT
	 c.razon_social,
	 c.cuit,
	 '2019-05-15' as fecha,
	 s.costo,
	 s.nombre as plan,
	 CONCAT(c.id,'002019051500',s.id,'00',rsc.id) AS codigo
	FROM servicios s
		INNER JOIN `relacion_servicio_cliente` rsc
		ON s.id = rsc.servicios_id
			INNER JOIN clientes c
			ON rsc.cliente_id = c.id";
//echo $sql;exit();
$resultado = $conn->query($sql);
$cobros = "";
$fp = fopen(date("YmdHis")."cobros.txt", "w");
if ($resultado->num_rows > 0) {
    while($row = $resultado->fetch_assoc()) {
    	fputs($fp, $row["razon_social"].",".$row["cuit"].",".$row["codigo"].",".$row["fecha"].",".$row["costo"].",".$row["plan"].",Periodo Mayo,Juan Azcarate,250\n");
    }
}
fclose($fp);
?>