<?php
/**
 * Emisión de Nota de Débito C a partir de una Nota de Crédito emitida.
 *
 * Mismas reglas que nota_de_credito.php: punto de venta de la NOTA DE CRÉDITO
 * que se asocia (su PDF, o su sucursal); pre-vuelo del PDF; reserva atómica
 * (nota_credito_id UNIQUE en nota_de_debito: una ND por NC); confirmación en
 * AFIP si la llamada falla; emitida_sin_pdf en vez de 500.
 */
ini_set('display_errors','0');
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
require 'vendor/autoload.php';
require_once __DIR__.'/afip_bridge.php';
require_once __DIR__.'/legacy_comprobantes.php';
use Spipu\Html2Pdf\Html2Pdf;

define('ND_TABLA', 'nota_de_debito');
define('ND_ASOCIADO', 'nota_credito_id');

function nd_salir($error, $mensaje = null) {
	$devolucion = array("error" => $error);
	if ($mensaje !== null) $devolucion["mensaje"] = $mensaje;
	echo json_encode($devolucion);
	exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") !== "xmlhttprequest") {
	http_response_code(405);
	nd_salir("Método no permitido");
}
if (!isset($_COOKIE["sucursal"])) exit();
$sucursal_usuario = getSucursal($_COOKIE["sucursal"]); // cookie inválida => exit()
$rol = getRol();
if ($rol < 4 && $rol != 1) nd_salir("No autorizado", "Su usuario no puede emitir notas de débito.");

$nc_id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
if ($nc_id <= 0) nd_salir("Nota de crédito inválida", "Seleccione la nota de crédito.");
$observaciones = legacy_texto_form_latin1(isset($_POST["observaciones"]) ? $_POST["observaciones"] : "");

$cuit = afip_valor('cuit');

// Nota de crédito que se asocia
$stmt = $conn->prepare("SELECT * FROM nota_de_credito WHERE id = ?");
$stmt->bind_param("i", $nc_id);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$datos) nd_salir("Nota de crédito inexistente", "No se encontró la nota de crédito seleccionada.");
if ($datos["cae"] == "") nd_salir("Nota de crédito sin CAE", "La nota de crédito seleccionada no fue autorizada por AFIP.");
if ($rol < 4 && intval($datos["sucursal_id"]) !== intval($sucursal_usuario)) {
	nd_salir("No autorizado", "La nota de crédito pertenece a otra sucursal.");
}

$documento 		= $datos["documento"];
$nombre 		= $datos["nombre"];
$tipoDocumento 	= $datos["tipo_documento"];
$iva 			= $datos["iva"];
$direccion		= $datos["direccion"];
$total 			= floatval($datos["total"]);
$numero_nc		= intval($datos["numero"]);

// Sucursal de la NOTA DE CRÉDITO: define el punto de venta.
$arrSucursal = array();
$stmt = $conn->prepare("SELECT * FROM sucursales WHERE id = ?");
$stmt->bind_param("i", $datos["sucursal_id"]);
$stmt->execute();
$res_sucursal = $stmt->get_result();
if ($res_sucursal && $res_sucursal->num_rows > 0) $arrSucursal = $res_sucursal->fetch_assoc();
$stmt->close();
if (!$arrSucursal) nd_salir("Sucursal inexistente", "La sucursal de la nota de crédito ya no existe; avise al administrador.");

$ptovta = legacy_ptovta_comprobante($datos["pdf"], $arrSucursal);
if ($ptovta === null) {
	nd_salir("No se emitió la nota de débito",
		"La sucursal \"".$arrSucursal["nombre"]."\" no tiene punto de venta configurado. No se emitió nada; avise al administrador.");
}

$logo = legacy_logo_pdf(__DIR__, null);
$resultado_perfil = $conn->query("SELECT logo FROM perfil");
if ($resultado_perfil && ($row_perfil = $resultado_perfil->fetch_assoc())) {
	$logo = legacy_logo_pdf(__DIR__, $row_perfil["logo"]);
}

// --- Pre-vuelo del PDF antes de tocar AFIP.
$error_previo = legacy_prevuelo_pdf(dirname(__FILE__)."/notas_credito", $logo);
if ($error_previo !== null) {
	error_log("nota_de_debito.php: pre-vuelo fallido, no se emitio ND para NC ".$nc_id.": ".$error_previo);
	nd_salir("No se emitió la nota de débito", $error_previo." No se emitió ningún comprobante; avise al administrador.");
}

// --- Reserva atómica de la NC (nota_credito_id UNIQUE).
legacy_reserva_limpiar($conn, ND_TABLA, ND_ASOCIADO, 30);
$fila = array(
	"sucursal_id"    => $datos["sucursal_id"],
	"fecha"          => date("Y-m-d H:i:s"),
	"usuario"        => $_COOKIE["kiosco"],
	"total"          => (string) $total,
	"presupuesto"    => 0,
	"nro_presupuesto"=> 0,
	"nombre"         => $nombre,
	"direccion"      => $direccion,
	"documento"      => $documento,
	"tipo_documento" => $tipoDocumento,
	"iva"            => $iva,
);
$reserva = legacy_reserva_tomar($conn, ND_TABLA, ND_ASOCIADO, $nc_id, $fila);
if (isset($reserva["duplicada"])) {
	$previa = $reserva["duplicada"];
	if ($previa && $previa["cae"] != "") {
		nd_salir("La nota de crédito ya tiene una nota de débito",
			"La nota de crédito Nro. ".$numero_nc." ya tiene la nota de débito Nro. ".$previa["numero"]." (CAE ".$previa["cae"]."). No se emitió otra.");
	}
	nd_salir("Emisión en curso", "Ya hay una nota de débito en emisión para esta nota de crédito. Espere unos minutos antes de reintentar.");
}
if (!isset($reserva["id"])) {
	error_log("nota_de_debito.php: fallo la reserva de la NC ".$nc_id.": ".$reserva["errno"]." ".$reserva["error"]);
	nd_salir("No se emitió la nota de débito", ($reserva["errno"] == 1054)
		? "La base de datos no está preparada para registrar la nota (falta la migración nota_credito_id). Avise al administrador."
		: "No se pudo registrar la nota (código 502). No se emitió ningún comprobante; avise al administrador.");
}
$nd_id = $reserva["id"];

$fecha = explode("-",date("Y-m-d"));
try {
	$afip = afip_instance();
	$data = array(
		'CantReg' 		=> 1,
		'PtoVta' 		=> $ptovta,  // Punto de venta DE LA NOTA DE CRÉDITO
		'CbteTipo' 		=> 12,  // Nota de Débito C
		'Concepto' 		=> 1,
		'DocTipo' 		=> 99,
		'DocNro' 		=> 0,
		'CbteFch' 		=> $fecha[0].$fecha[1].$fecha[2],
		'ImpTotal' 		=> $total,
		'ImpTotConc' 	=> 0,
		'ImpNeto' 		=> $total,
		'ImpOpEx' 		=> 0,
		'ImpIVA' 		=> 0,
		'ImpTrib' 		=> 0,
		'MonId' 		=> 'PES',
		'MonCotiz' 		=> 1,
		'CondicionIVAReceptorId' => afip_cond_iva_receptor($iva), // RG 5616
		'CbtesAsoc' 	=> array( // ASOCIO LA NOTA DE CRÉDITO
			array(
				'Tipo' 		=> 13, // Nota de Crédito C
				'PtoVta' 	=> $ptovta,
				'Nro' 		=> $numero_nc,
				'Cuit' 		=> floatval($cuit)
				)
			),
	);
	if ($tipoDocumento != "" && $documento != ""){
		$data['DocTipo'] 	= $tipoDocumento;
		$data['DocNro'] 	= $documento;
	}
} catch (\Throwable $e) {
	legacy_reserva_liberar($conn, ND_TABLA, $nd_id);
	error_log("nota_de_debito.php: no se pudo preparar la emision: ".get_class($e).": ".$e->getMessage());
	nd_salir("No se emitió la nota de débito", "No se pudo preparar la conexión con AFIP (".$e->getMessage()."). No se emitió nada; avise al administrador.");
}

$emision = legacy_afip_emitir($afip, $data);
if ($emision["estado"] === "rechazada") {
	legacy_reserva_liberar($conn, ND_TABLA, $nd_id);
	nd_salir("Error al generar el comprobante", $emision["mensaje"]);
}
if ($emision["estado"] !== "emitida") {
	error_log("nota_de_debito.php: resultado desconocido en AFIP para NC ".$nc_id.", reserva ".$nd_id." retenida: ".$emision["mensaje"]);
	nd_salir("No se pudo confirmar la emisión", $emision["mensaje"]);
}
$res = $emision["res"];
$resCAEFchVto = explode("-",$res["CAEFchVto"]);
$res["CAEFchVto"] = count($resCAEFchVto) == 3 ? $resCAEFchVto[2]."-".$resCAEFchVto[1]."-".$resCAEFchVto[0] : $res["CAEFchVto"];

// A esta altura la ND YA fue autorizada por AFIP: pase lo que pase se graba.
$numero = substr("00000".$res["voucher_number"],-6);
$nombre_factura = "/notas_credito/".$ptovta."_".$res["CAE"]."_".$numero.".pdf";
$grabada = legacy_reserva_confirmar($conn, ND_TABLA, $nd_id,
	array("numero" => $numero, "cae" => $res["CAE"], "fechacae" => $res["CAEFchVto"], "pdf" => $nombre_factura),
	ND_ASOCIADO, $nc_id, $fila);
if (!$grabada) {
	error_log("nota_de_debito.php: ND ".$numero." (CAE ".$res["CAE"].") emitida pero NO se pudo grabar en la base. REVISAR.");
}

$devolucion = array(
	"nota_debito_id" => $nd_id,
	"numero" => $res["voucher_number"],
	"cae" => $res["CAE"],
);

switch ($iva) {
	case '1': $texto_iva = "Resp. Inscripto";break;
	case '2': $texto_iva = "Monotributista";break;
	case '3': $texto_iva = "Excento";break;
	case '4': $texto_iva = "Cons. Final";break;
	default:  $texto_iva = "Consumidor Final";break;
}
$html_datos_cliente = "<tr>
							<td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> ".legacy_html_latin1($nombre)."</td>
						</tr><tr>
							<td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b> ".legacy_html_latin1($direccion)."</td>
						</tr><tr>
							<td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b> ".legacy_html_latin1($documento)."</td>
						</tr>
						<tr>
							<td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> $texto_iva</td>
						</tr>
						<tr>
							<td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> Efectivo</td>
						</tr>
						<tr>
							<td colspan='3' style='height:30px;'>&nbsp;</td>
						</tr>";

$direccion_sucursal = legacy_html_latin1($arrSucursal["direccion"]);
if (strlen($arrSucursal["direccion"]) > 25 && strpos($arrSucursal["direccion"]," ",20) !== false) {
	$corte = strpos($arrSucursal["direccion"]," ",20);
	$direccion_sucursal = legacy_html_latin1(substr($arrSucursal["direccion"],0,$corte))."<br />".legacy_html_latin1(substr($arrSucursal["direccion"],$corte));
}
$detalle = "Nota de d&eacute;bito s/ Nota de Cr&eacute;dito C ".substr("00000".$ptovta,-5)."-".substr("00000000".$numero_nc,-8);
if ($observaciones !== "") $detalle .= " - ".legacy_html_latin1($observaciones);
$html = utf8_encode("
						<style>
							h3{
								font-size:1em;
							}
						</style>
						<table>
							<tr>
								<td style='padding-left:10px;border: 2px solid #000;height: 100px;font-size: 14px;width: 320px;text-align: left;'>
									<table>
										<tr>
											<td>
												<img = src='$logo' style='height:80px;width:120px;'/>
											</td>
											<td>	
												<br />".legacy_html_latin1($arrSucursal["nombre"])."
												<br />$direccion_sucursal<br />
												".legacy_html_latin1($arrSucursal["codigo_postal"])." - ".legacy_html_latin1($arrSucursal["provincia"])."<br />
											</td>
										</tr>
									</table>
								</td>
								<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>C</td>
								<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
									<b>NOTA DE DEBITO NRO.</b>&nbsp;".substr("00000".$ptovta,-6)."&nbsp;-&nbsp;".substr("000000".$res["voucher_number"],-6)."<br />
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
							<tr>
							<td style='border-bottom: 1px solid #000;'><i>".$detalle."</i></td>
							<td style='border-bottom: 1px solid #000;'>1</td>
							<td style='border-bottom: 1px solid #000;'>".number_format($total,2,",",".")."</td>
						</tr>
						<tr>
							<td></td>
							<td style='border-bottom: 1px solid #000;'>Total</td>
							<td style='border-bottom: 1px solid #000;'>".number_format($total,2,",",".")."</td>
						</tr></table>
						<p style='text-align:right'><b>CAE Nro.:</b> ".$res["CAE"]."<br />
						<b>Fecha de Vto. CAE: </b>".$res["CAEFchVto"]."<br /></p>");

try {
	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
	$html2pdf->setDefaultFont('Arial');
	$html2pdf->writeHTML("<page>".$html."<br><br><hr style='border-style: dotted;' /><br><br></page>");
	$html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
	$devolucion["factura"] = $nombre_factura;
} catch (\Throwable $e) {
	error_log("nota_de_debito.php: ND ".$numero." (CAE ".$res["CAE"].") emitida pero fallo el PDF: ".get_class($e).": ".$e->getMessage());
	$devolucion["emitida_sin_pdf"] = true;
	$devolucion["mensaje"] = "La nota de débito Nro. ".$res["voucher_number"]." fue emitida (CAE ".$res["CAE"].") pero no se pudo generar el PDF. NO vuelva a emitirla; avise al administrador.";
}
echo json_encode($devolucion);
exit();
