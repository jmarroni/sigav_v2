<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
$sql = "SELECT
pr.id,
pap.`nombre` as nombre_proveedor,
pr.`nombre` as nombre_producto,
pr.`stock`,
pr.`stock_minimo`
FROM `productos` pr
	INNER JOIN `proveedores` pap
	ON pr.`proveedores_id` = pap.`id`
WHERE
	`stock_minimo` >= stock
ORDER BY pap.`id`,pr.id DESC";

$resultado_caja = $conn->query($sql);
$html = '';
if ($resultado_caja->num_rows > 0) {
	$html .= '<table border="1" style="font-size: 12px;font-family: sans-serif;">';
	$proveedor = '';
	while($row_caja = $resultado_caja->fetch_assoc()) {
		if ($proveedor != $row_caja["nombre_proveedor"]){
			$html .= '<tr>';
			$html .= '<td colspan="5" style="font-size: 20px;font-family: sans-serif;font-weight: bold;">Proveedor :'.$row_caja["nombre_proveedor"].'</td>';
			$html .= '</tr>';
			$proveedor = $row_caja["nombre_proveedor"];
		}
		$html .= '<tr>';
			$html .= '<td>'.$row_caja["id"].'</td>';
			$html .= '<td>'.$row_caja["nombre_proveedor"].'</td>';
			$html .= '<td>'.$row_caja["nombre_producto"].'</td>';
			$html .= '<td>'.$row_caja["stock"].'</td>';
			$html .= '<td>'.$row_caja["stock_minimo"].'</td>';
			$html .= '</tr>';
	 }
	$html .= '</table>';
}
$conn->close();
$to = "jmarroni@gmail.com";
$subject = "Faltantes de stock en el kiosco";

// Always set content-type when sending HTML email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
$headers .= 'From: <jmarroni@fidegroup.com.ar>' . "\r\n";

mail($to,$subject,$html,$headers);

exit();
?>