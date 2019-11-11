<?php

if (!isset($_COOKIE["kiosco"])) {
    exit();
}

header('Content-Type: application/json');

require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

$arrSucursal = array();

if (isset($_COOKIE["sucursal"])){
	$datos_sucursal = "SELECT * FROM sucursales where id = '".getSucursal($_COOKIE["sucursal"])."'";
	// echo $datos_sucursal;exit();
	$resultado_perfil = $conn->query($datos_sucursal) or die("Error: " . $sql . "<br>" . $conn->error);
	if ($resultado_perfil->num_rows > 0) {
		$arrSucursal = $resultado_perfil->fetch_assoc();
	}else{exit();}
} else exit();


// DATOS PARA EL COMPROBANTE

$comprobante 		= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante");
// $ptovta 			= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ptovta");
$ptovta				= $arrSucursal["pto_vta"];
$cuit 				= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit");
$condicion_iva 		= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/condicion_iva");
$inicio_actividades = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/inicio_actividades");
$ingresos_brutos 	= file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ingresos_brutos");

$documento 			= ($_GET["documento"] != "")?$_GET["documento"]:"";
$nombre 			= ($_GET["nombre"] != "")?$_GET["nombre"]:"";
$tipoDocumento 		= ($_GET["tipo-documento"] != "")?$_GET["tipo-documento"]:"";
$tipo		 		= ($_GET["tipo"] != "")?$_GET["tipo"]:""; // forma de pago
$iva 				= ($_GET["iva"] != "")?$_GET["iva"]:"";
$direccion			= ($_GET["direccion"] != "")?$_GET["direccion"]:"";


// Me fijo si lo tengo que insertar como cliente o ya autocompleto
if ($_GET["clientes_id"] == ""){

	$insert_cliente = "INSERT INTO `clientes`
						(`id`,
						`razon_social`,
						`domicilio_legal`,
						`codigo_postal`,
						`telefono`,
						`provincia`,
						`localidad`,
						`cuit`,
						`condicion_iva`,
						`representante`,
						`email_representante`,
						`responsable_contratacion`,
						`email_constratacion`,
						`responsable_pagos`,
						`email_pagos`,
						`consulta_proveedores`,
						`entrega_retiros`,
						`fecha_alta`,
						`deshabilitado`)
					VALUES (NULL,
					'$nombre',
					'$direccion',
					'',
					'',
					'',
					'',
					'$cuit',
					'$iva',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'',
					'');";
	if ($conn->query($insert_cliente) === TRUE) {}else{}
}

$sql = "Select * FROM perfil";
$resultado_perfil = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
if ($resultado_perfil->num_rows > 0) {
    if ($row_perfil = $resultado_perfil->fetch_assoc()) {
        $logo = "http://".$_SERVER['HTTP_HOST'].$row_perfil["logo"];
		$nombre_fantasia = $row_perfil["nombre"];
		$datos_factura = $row_perfil;
    }
}else{
	$logo = "http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";
    $nombre_fantasia = "SIGAV";
}

// Agarro el numero de lista para la venta
if (isset($_COOKIE["lista_precio"])) $lista_precio = $_COOKIE["lista_precio"];
else $lista_precio = 1;

// Agarro el estado para la venta
$emitir = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir");
if (intval($emitir) === 1 ) {
    $estado = 1;
} else {
    $estado = 3;
}

// Agarro los productos del carrito para esta venta
$sql_productos_en_carrito = "SELECT * FROM productos_en_carrito WHERE venta_id = '{$_GET['venta_id']}'";
$resultados_productos_en_carrito = $conn->query($sql_productos_en_carrito);

// Ids ventas insertadas
$array_ids_ventas = [];

if ($resultados_productos_en_carrito->num_rows > 0) {
	while ($producto_en_carrito  = $resultados_productos_en_carrito->fetch_assoc()) {
		$sql_descontar_stock = "UPDATE stock SET stock = (stock - {$producto_en_carrito["cantidad"]}) WHERE productos_id = ".$producto_en_carrito["producto_id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);

		if ($conn->query($sql_descontar_stock) === FALSE) {
			echo "Error en UPDATE: " . $sql_descontar_stock . "<br>" . $conn->error;
		}

		$sql_insertar_venta = 
			"
			INSERT INTO ventas 
			VALUES(
				NULL,
				'{$producto_en_carrito['producto_id']}',
				'{$producto_en_carrito['cantidad']}', 
				'{$producto_en_carrito['precio']}', 
				'{$producto_en_carrito['costo']}', 
				'".date("Y-m-d H:i:s")."', 
				'{$_COOKIE["kiosco"]}', 
	    		'".getSucursal($_COOKIE["sucursal"])."', 
	    		'$estado', 
	    		NULL, 
	    		'1612', 
	    		'$lista_precio' 
			)";

		if ($conn->query($sql_insertar_venta) === FALSE) {
			echo "Error en UPDATE: " . $sql_insertar_venta . "<br>" . $conn->error;
		}

		array_push($array_ids_ventas, $conn->insert_id);
	}
}

if (intval($_GET["presupuesto"]) == 0) {
	$sql = "SELECT v.*,p.nombre as nombre_producto FROM ventas v inner join productos p on p.id = v.productos_id WHERE v.estado = 1 AND v.sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND v.usuario = '".$_COOKIE["kiosco"]."'";
} else {
	$sql = "SELECT v.*, p.nombre as nombre_producto FROM ventas v inner join productos p on p.id = v.productos_id WHERE v.estado = 3 AND v.fecha > '".date("Y-m-d 00:00:00")."' AND v.sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND v.usuario = '".$_COOKIE["kiosco"]."'";
}

$item = array();
$total = 0;
$datos = array();
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
	// output data of each row
	while($row = $resultado->fetch_assoc()) {
		$total += $row["precio"] * $row["cantidad"];
		$datos_productos[] = $row;
	}
}else{
	$devolucion["error"] = "No existen productos para facturar";
	echo json_encode($devolucion);
	exit();
}
$fecha = explode("-",$_GET["fecha-facturacion"]);
if (count($fecha) < 3) { echo "error"; exit(); }
$afip = new Afip(array('CUIT' => floatval($cuit), "production" => TRUE));
$data = array(
	'CantReg' 		=> 1,  // Cantidad de comprobantes a registrar
	'PtoVta' 		=> $ptovta,  // Punto de venta
	'CbteTipo' 		=> 11,  // Tipo de comprobante (ver tipos disponibles) 
	'Concepto' 		=> 1,  // Concepto del Comprobante: (1)Productos, (2)Servicios, (3)Productos y Servicios
	'DocTipo' 		=> 99, // Tipo de documento del comprador (99 consumidor final, ver tipos disponibles)
	'DocNro' 		=> 0,  // Número de documento del comprador (0 consumidor final)
	'CbteFch' 		=> $fecha[0].$fecha[1].$fecha[2], // (Opcional) Fecha del comprobante (yyyymmdd) o fecha actual si es nulo
	'ImpTotal' 		=> $total, // Importe total del comprobante
	'ImpTotConc' 	=> 0,   // Importe neto no gravado
	'ImpNeto' 		=> $total, // Importe neto gravado
	'ImpOpEx' 		=> 0,   // Importe exento de IVA
	'ImpIVA' 		=> 0,  //Importe total de IVA
	'ImpTrib' 		=> 0,   //Importe total de tributos
	'MonId' 		=> 'PES', //Tipo de moneda usada en el comprobante (ver tipos disponibles)('PES' para pesos argentinos) 
	'MonCotiz' 		=> 1,     // Cotización de la moneda usada (1 para pesos argentinos)  
);

// Me fijo si se coloco el cliente
if ($tipoDocumento != "" && $documento != ""){
	$data['DocTipo'] 	= $tipoDocumento;
	$data['DocNro'] 	= $documento;
}

//$res["CAE"] = "1111";
//$res["CAEFchVto"] = date("Y-m-d");
//$res["voucher_number"] = date("YmdHis");
$voucher_info = "";
$nro_presupuesto = 0;
if (intval($_GET["presupuesto"]) == 0){
	try {
		$res = $afip->ElectronicBilling->CreateNextVoucher($data);
		$resCAEFchVto = explode("-",$res["CAEFchVto"]);
		$res["CAEFchVto"] = $resCAEFchVto[2]."-".$resCAEFchVto[1]."-".$resCAEFchVto[0];
		$voucher_info = $afip->ElectronicBilling->GetVoucherInfo($afip->ElectronicBilling->GetLastVoucher($ptovta,$comprobante),$ptovta,$comprobante); //Devuelve la información del comprobante 1 para el punto de venta 1 y el tipo de comprobante 6 (Factura B)
	}catch(Exception $e) {
		$devolucion["error"] = "Error al generar el comprobante";
		$devolucion["mensaje"] = "AFIP respondio lo siguiente al intentar comunicarnos: ".$e->getMessage();
		file_put_contents("errores.dat",$e);
		$sql_update = "UPDATE ventas SET estado = 5  WHERE id = ".$datos_productos[0]["id"];
		$conn->query($sql_update);
		echo json_encode($devolucion);exit();
	}
	
}else{
	$select_presupuesto = "SELECT * FROM factura where presupuesto = 1 order by nro_presupuesto DESC LIMIT 1";
	$resultado_perfil = $conn->query($select_presupuesto) or die("Error: " . $sql . "<br>" . $conn->error);
	if ($resultado_perfil->num_rows > 0) {
		if ($arrPedido = $resultado_perfil->fetch_assoc()){
			if (isset($arrPedido["nro_presupuesto"])) $nro_presupuesto = $arrPedido["nro_presupuesto"] + 1;
			else $nro_presupuesto = 1;
		}else $nro_presupuesto = 1;
	}else{ $nro_presupuesto = 1;}
	$res["CAEFchVto"] = "";
	$res["voucher_number"] = $nro_presupuesto;
	$res["CAE"] = "";
}
if($voucher_info === NULL){
	$devolucion["error"] = "Error al generar el comprobante";
	echo json_encode($devolucion);exit();
}else{
	if (intval($_GET["presupuesto"]) == 0){
		$nombre_factura = "/facturas/".$ptovta."_".$res["CAE"]."_".substr("00000".$res["voucher_number"],-6).".pdf";
		$facturanro = "FACTURA NRO.";

	}else{
		$nombre_factura = "/presupuesto/".$ptovta."_".$res["CAE"]."_".$res["voucher_number"].".pdf";
		$facturanro = "PRESUPUESTO NRO.";
	}

	$solicitar = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/solicitar_datos"); 
		switch ($iva) {
			case '1': $texto_iva = "Resp. Inscripto";break;
			case '2': $texto_iva = "Monotributista";break;
			case '3': $texto_iva = "Excento";break;
			case '4': $texto_iva = "Cons. Final";break;
			default:
				$texto_iva = "Consumidor Final";
				break;
		}
		switch ($tipo) {
			case '1': $texto_tipo = "Efectivo";break;
			case '2': $texto_tipo = "Debito";break;
			case '3': $texto_tipo = "Credito";break;
			default:
				$texto_tipo = "Efectivo";
				break;
		}
		$html_datos_cliente = "<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> $nombre</td>
								</tr><tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b> $direccion</td>
								</tr><tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b> $documento</td>
								</tr>
								<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> $texto_iva</td>
								</tr>
								<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> $texto_tipo</td>
								</tr>
								<tr>
									<td colspan='3' style='height:30px;'>&nbsp;</td>
								</tr>";

	$sql_insert = "INSERT INTO `factura`
						(`id`,
						`sucursal_id`,
						`fecha`,
						`usuario`,
					 	`numero`,
						`cae`,
						`fechacae`,
						`total`,
						`pdf`,
						`presupuesto`,
						`nro_presupuesto`,
						`nombre`,
						`direccion`,
						`documento`,
						`tipo_documento`,
						`iva`
						)
					VALUES (NULL,
					'".getSucursal($_COOKIE["sucursal"])."',
					'".date("Y-m-d H:i:s")."',
					'".$_COOKIE["kiosco"]."',
					'".substr("00000".$res["voucher_number"],-6)."',
					'".$res["CAE"]."',
					'".$res["CAEFchVto"]."',
					'$total',
					'$nombre_factura',
					".intval($_GET["presupuesto"]).",
					$nro_presupuesto,
					'$nombre',
					'$direccion',
					'$documento',
					'$tipoDocumento',
					'$iva');";

	if ($conn->query($sql_insert) === TRUE) {
		$factura_id = $conn->insert_id;

		foreach($array_ids_ventas as $id) {
			$sql_update_venta_factura = "UPDATE ventas SET factura_id = '$factura_id' WHERE id = '{$id}'";
		
			if ($conn->query($sql_update_venta_factura) === FALSE) {
				echo json_encode("Error: " . $sql_update_venta_factura . "<br>" . $conn->error);
				exit();
			}
		}
	} else {
		echo "Error en la facturacion, por favor comuniquese con el administrador eh indiquele el codigo 502";
		echo $sql_insert;
	}

	if (strlen($arrSucursal["direccion"]) > 25)
	$arrDireccion = substr($arrSucursal["direccion"],0,strpos($arrSucursal["direccion"]," ",20))."<br />".substr($arrSucursal["direccion"],strpos($arrSucursal["direccion"]," ",20)); 
	else $arrDireccion = $arrSucursal["direccion"];
	
	$html = utf8_encode("
							<style>
								h3{
									font-size:1em;
								}
							</style>
							<table>
								<tr>
									<td colspan='3' style='border: 2px solid #000;text-align:center;'>@@COMPROBANTE@@</td>
								</tr>
								<tr>
									<td style='padding-left:10px;border: 2px solid #000;height: 100px;font-size: 14px;width: 320px;text-align: left;'>
										<table>
											<tr>
												<td>
													<img = src='$logo' style='height:80px;width:120px;'/>
												</td>
												<td>	
													<br />{$arrSucursal["nombre"]}
													<br />$arrDireccion<br />
													{$arrSucursal["codigo_postal"]} - {$arrSucursal["provincia"]}<br />
													<b>Comprobante Electronico</b>
												</td>
											</tr>
										</table>
									</td>
									<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>C</td>
									<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
										<b>@@FACTURANRO@@</b>&nbsp;".substr("00000".$ptovta,-6)."&nbsp;-&nbsp;".substr("000000".$res["voucher_number"],-6)."<br />
										<b>CUIT</b>&nbsp;$cuit<br />
										<b>Fecha de Emisi&oacute;n</b>&nbsp;".$fecha[2]."-".$fecha[1]."-".$fecha[0]."<br />
										<b>Ing.&nbsp;Bruto</b>&nbsp;$cuit<br />

									</td>
								</tr>
								$html_datos_cliente
								<tr>
									<td style='border-bottom: 1px solid #000;'><b>Descripcion</b></td>
									<td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
									<td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
								</tr>
								");
	foreach ($datos_productos as $key => $value) {
		$html .= utf8_encode("<tr>
								<td style='border-bottom: 1px solid #000;'><i>".$value["nombre_producto"]."</i></td>
								<td style='border-bottom: 1px solid #000;'>".$value["cantidad"]."</td>
								<td style='border-bottom: 1px solid #000;'>".number_format(floatval($value["precio"]),2,",",".")."</td>
							</tr>
							");
	}

	$html .= utf8_encode("<tr>
							<td></td>
							<td style='border-bottom: 1px solid #000;'>Total</td>
							<td style='border-bottom: 1px solid #000;'>".number_format($total,2,",",".")."</td>
						</tr></table>");

	if ($res["CAE"] != ""){
		$html .= utf8_encode("<p style='text-align:right'><b>CAE Nro.:</b> ".$res["CAE"]."<br />
						<b>Fecha de Vto. CAE: </b>".$res["CAEFchVto"]."<br /></p>");
	}

	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
	$html2pdf->setDefaultFont('Arial');
	$html = str_replace("@@FACTURANRO@@",$facturanro,$html);
	$html2pdf->writeHTML("<page>".str_replace("@@COMPROBANTE@@","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("@@COMPROBANTE@@","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");
	//$html2pdf->Output();
	$html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
	$devolucion["factura"] = $nombre_factura;
	echo json_encode($devolucion);
}

	exit();
?>
