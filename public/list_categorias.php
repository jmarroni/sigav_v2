<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
$sql = "SELECT cat.*
FROM `categorias` cat
	INNER JOIN relacion_categoria_proveedor rcp
	ON cat.id = rcp.categoria_id and rcp.proveedor_id = ".intval($_GET["proveedor"])."
	Order by cat.nombre";
$resultado_caja = $conn->query($sql);
$html = '';
if ($resultado_caja->num_rows > 0) {

	while($row_caja = $resultado_caja->fetch_assoc()) {
		?><option value="<?php echo $row_caja["id"]."_".$row_caja["abreviatura"]; ?>"><?php echo $row_caja["nombre"] ?></option><?php
	 }
}
$conn->close();

exit();
?>