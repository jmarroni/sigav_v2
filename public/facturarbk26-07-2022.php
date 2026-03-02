<?php
$descontar_stock=0;
if (!isset($_COOKIE["kiosco"])) {
	exit();
}
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);
require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
$stockfinal=0;
$stockanterior=0;
$stockminimo=0;
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
$descontar_stock    = 1;


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
        //$logo = (file_exists($logo))?$logo:"http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";
		$nombre_fantasia = $row_perfil["nombre"];
		$datos_factura = $row_perfil;
	}
}else{
	$logo = "http://".$_SERVER['HTTP_HOST']."/assets/img/photos/no-image-featured-image.png";
	$nombre_fantasia = "SIGAV";
}
if (strpos($logo,"127.0.0.1") > 0) $logo = "http://sistema.mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
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
		if ($descontar_stock==1)
		{
			$consultar_stock="select stock, stock_minimo from stock WHERE productos_id = ".$producto_en_carrito["producto_id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);

			$resultadoquery=$conn->query($consultar_stock);
			$resultadoquery=($resultadoquery!=null && $resultadoquery!=false)?$resultadoquery->fetch_assoc():null;
			$stockanterior=$resultadoquery["stock"]!=null?$resultadoquery["stock"]:0;
			$stockminimo=$resultadoquery["stock_minimo"]!=null?$resultadoquery["stock_minimo"]:0;
			$stockfinal=$stockanterior-$producto_en_carrito["cantidad"];

			$sql_descontar_stock = "UPDATE stock SET stock = (stock - {$producto_en_carrito["cantidad"]}) WHERE productos_id = ".$producto_en_carrito["producto_id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);

			if ($conn->query($sql_descontar_stock) === FALSE) {
				echo "Error en UPDATE: " . $sql_descontar_stock . "<br>" . $conn->error;
			}
			else
			{
			$date=date("Y-m-d");
			
			$queryLog = "Insert into stock_logs
			 (stock_anterior, stock_minimo_anterior, stock, stock_minimo, sucursal_id, usuario,
			 productos_id, updated_at, created_at, tipo_operacion) 
			 VALUES (".$stockanterior.",".$stockminimo.",".$stockfinal.",".$stockminimo.",".getSucursal($_COOKIE["sucursal"]).",'".$_COOKIE["kiosco"]."',".$producto_en_carrito["producto_id"].",'".$date."','".$date."','VENTA')";
			 if ($conn->query($queryLog) === FALSE) {
				echo "Error guardando log: " . $queryLog . "<br>" . $conn->error;
			    }
			}

		}//End if de condición descontar stock

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
	'3', 
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
$sql = "SELECT v.*, p.nombre as nombre_producto FROM ventas v inner join productos p on p.id = v.productos_id WHERE v.estado = 3 AND v.fecha > '".date("Y-m-d 00:00:00")."' AND v.sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND v.usuario = '".$_COOKIE["kiosco"]."'";


$item = array();
$total = 0;
$datos = array();
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
	// output data of each row
	while($row = $resultado->fetch_assoc()) {
		$total += $row["precio"] * $row["cantidad"];
		$datos_productos[] = $row;
		$update_producto = "UPDATE ventas SET estado = 5 WHERE id = ".$row["id"];
		if ($conn->query($update_producto) === FALSE) {
			echo "Error en UPDATE estado venta: " . $update_producto . "<br>" . $conn->error;
		}

	}
	$eliminar_carrito = "DELETE FROM productos_en_carrito WHERE venta_id = '{$_GET['venta_id']}'";	
}else{
	$devolucion["error"] = "No existen productos para facturar";
	echo json_encode($devolucion);
	exit();
}
$fecha = explode("-",$_GET["fecha-facturacion"]);
if (count($fecha) < 3) { echo "error"; exit(); }
$afip = new Afip(array('CUIT' => floatval($cuit), "production" => TRUE));
if (intval($comprobante) != 11){
	$ImpNeto = round($total / 1.21,2);
	$impuestoIVA = round($ImpNeto * 0.21,2);
}else{
	$impuestoIVA = 0;
	$ImpNeto = $total;
}

// Me fijo si se coloco el cliente
if ($tipoDocumento != "" && $documento != ""){
	$tipoDocumento 	= $tipoDocumento;
	$documento	= $documento;
}
else
{
	$tipoDocumento 	= 99;
	$documento	= 0;
}

$data = array(
	'CantReg' 		=> 1,  // Cantidad de comprobantes a registrar
	'PtoVta' 		=> $ptovta,  // Punto de venta
	'CbteTipo' 		=> $comprobante,  // Tipo de comprobante (ver tipos disponibles) 
	'Concepto' 		=> 1,  // Concepto del Comprobante: (1)Productos, (2)Servicios, (3)Productos y Servicios
	'DocTipo' 		=> $tipoDocumento, // 99Tipo de documento del comprador (99 consumidor final, ver tipos disponibles)
	'DocNro' 		=> $documento,  //0 Número de documento del comprador (0 consumidor final)
	'CbteFch' 		=> $fecha[0].$fecha[1].$fecha[2], // (Opcional) Fecha del comprobante (yyyymmdd) o fecha actual si es nulo
	'ImpTotal' 		=> $total, // Importe total del comprobante
	'ImpTotConc' 	=> 0,   // Importe neto no gravado
	'ImpNeto' 		=> $ImpNeto, // Importe neto gravado
	'ImpOpEx' 		=> 0,   // Importe exento de IVA
	'ImpIVA' 		=> $impuestoIVA,  //Importe total de IVA
	'ImpTrib' 		=> 0,   //Importe total de tributos
	'MonId' 		=> 'PES', //Tipo de moneda usada en el comprobante (ver tipos disponibles)('PES' para pesos argentinos) 
	'MonCotiz' 		=> 1, // Cotización de la moneda usada (1 para pesos argentinos)  
	
);

if (intval($comprobante) != 11){
	$data['Iva'] = array( // (Opcional) Alícuotas asociadas al comprobante
						array(
							'Id' 		=> 5, // Id del tipo de IVA (5 para 21%)(ver tipos disponibles) 
							'BaseImp' 	=> $ImpNeto, // Base imponible
							'Importe' 	=> $impuestoIVA // Importe 
						)
					);
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
		case '3': $texto_iva = "Exento";break;
		case '4': $texto_iva = "Cons. Final";break;
		default:
		$texto_iva = "Consumidor Final";
		break;
	}
	switch ($tipo) {
		case '1': $texto_tipo = "Efectivo";break;
		case '2': $texto_tipo = "Debito";break;
		case '3': $texto_tipo = "Credito";break;
		case '4': $texto_tipo = "Transferencia";break;
		default:
		$texto_tipo = "Efectivo";
		break;
	}
	switch ($comprobante) {
		case '11': $tipo_comprobante = "C";break;
		case '1': $tipo_comprobante = "A";break;
		case '6': $tipo_comprobante = "B";break;
		default:
		$tipo_comprobante = "X";
		break;
	}
	$tipo_comprobante = (intval($_GET["presupuesto"]) == 0)?$tipo_comprobante:"X";
	$valido_o_no = (intval($_GET["presupuesto"]) == 0)?"Comprobante Electronico":"No valido como factura";
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
		echo "Error en la facturacion, por favor comuniquese con el administrador e indiquele el codigo 502";
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
		<b>$valido_o_no</b>
		</td>
		</tr>
		</table>
		</td>
		<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>$tipo_comprobante</td>
		<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
		<b>@@FACTURANRO@@</b>&nbsp;".substr("00000".$ptovta,-6)."&nbsp;-&nbsp;".substr("000000".$res["voucher_number"],-6)."<br />
		<b>CUIT</b>&nbsp;$cuit<br />
		<b>Fecha de Emisi&oacute;n</b>&nbsp;".$fecha[2]."-".$fecha[1]."-".$fecha[0]."<br />
		<b>Ing.&nbsp;Bruto</b>&nbsp;$ingresos_brutos<br />
		<b>IVA</b>&nbsp;$condicion_iva <br />

		</td>
		</tr>
		$html_datos_cliente
		<tr>
		<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px'><b>Descripcion</b></td>
		<td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
		<td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
		</tr>
		");
	foreach ($datos_productos as $key => $value) {
		$html .= utf8_encode("<tr>
			<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>".$value["nombre_producto"]."</i></td>
			<td style='border-bottom: 1px solid #000;'>".$value["cantidad"]."</td>
			<td style='border-bottom: 1px solid #000;'>".number_format(floatval($value["precio"]),2,",",".")."</td>
			</tr>
			");
	}

	$html .= utf8_encode("<tr>
		<td></td>
		<td style='border-bottom: 1px solid #000;'>Total</td>
		<td style='border-bottom: 1px solid #000;'>".number_format($total,2,",",".")."</td>
		</tr>");
	if ($comprobante == 1){
		$html .= utf8_encode("
			<tr>
			<td></td>
			<td style='border-bottom: 1px solid #000;'>Importe Neto</td>
			<td style='border-bottom: 1px solid #000;'>".number_format($ImpNeto,2,",",".")."</td>
			</tr>
			<tr>
			<td></td>
			<td style='border-bottom: 1px solid #000;'>IVA (21%)</td>
			<td style='border-bottom: 1px solid #000;'>".number_format($impuestoIVA,2,",",".")."</td>
			</tr>");
	}
	$html .= utf8_encode("</table>");
	if ($res["CAE"] != ""){
		$html .= utf8_encode("<p style='text-align:right'><b>CAE Nro.:</b> ".$res["CAE"]."<br />
			<b>Fecha de Vto. CAE: </b>".$res["CAEFchVto"]."<br /></p>");
	}

	//if ($tipo == 4){ // Si es transferencia coloco la leyenda
	//	$html .= utf8_encode("<p style='text-align:left'> *P&aacute;guese a la cuenta oficial Tesorer&iacute;a General Mercado Artesanal Provincial-Recaudadora. <br/><b>N° Cta Bco.</b> - 900001194 <br/><b>CBU</b> - 0340250600900001194004 <br/><b>CUIT</b> - Tesorer&iacute;a General Nro. 30-63945328-2 </p>");
	//}

	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
	$html2pdf->setDefaultFont('Arial');
	$html = str_replace("@@FACTURANRO@@",$facturanro,$html);

	

	$html2pdf->writeHTML("<page>".str_replace("@@COMPROBANTE@@","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("@@COMPROBANTE@@","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");

	
	$html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
	$devolucion["factura"] = $nombre_factura;
	echo json_encode($devolucion);
}

exit();
?>
