<?php
/**
 * Emisión de Nota de Crédito C (anula una factura completa) desde Devoluciones.
 *
 * Reglas (incidente 2026-09-23: 20 NC repetidas y por el punto de venta equivocado):
 *  - El punto de venta es el de la FACTURA que se anula (su PDF, o la sucursal
 *    de la factura), nunca el global de afip_config.
 *  - Antes de pedir el CAE se verifica todo lo que puede hacer fallar el PDF
 *    (pre-vuelo) y se RESERVA la factura (factura_id UNIQUE en nota_de_credito):
 *    una factura no puede tener dos notas de crédito, ni por doble clic.
 *  - Si la llamada a AFIP falla se confirma en AFIP si igual salió; una reserva
 *    de resultado desconocido NO se libera.
 *  - Si el PDF falla DESPUÉS del CAE no responde 500: devuelve emitida_sin_pdf.
 * Helpers compartidos con nota_de_debito.php en legacy_comprobantes.php.
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

define('NC_TABLA', 'nota_de_credito');
define('NC_ASOCIADO', 'factura_id');

function nc_salir($error, $mensaje = null) {
	$devolucion = array("error" => $error);
	if ($mensaje !== null) $devolucion["mensaje"] = $mensaje;
	echo json_encode($devolucion);
	exit();
}

// Operación fiscal irreversible: solo POST vía AJAX (sin GET ni formularios cruzados).
if ($_SERVER["REQUEST_METHOD"] !== "POST" || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") !== "xmlhttprequest") {
	http_response_code(405);
	nc_salir("Método no permitido");
}
if (!isset($_COOKIE["sucursal"])) exit();
$sucursal_usuario = getSucursal($_COOKIE["sucursal"]); // cookie inválida => exit()
$rol = getRol();
if ($rol < 4 && $rol != 1) nc_salir("No autorizado", "Su usuario no puede emitir notas de crédito.");

$factura_id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
if ($factura_id <= 0) nc_salir("Factura inválida", "Seleccione la factura a anular.");
$observaciones = legacy_texto_form_latin1(isset($_POST["observaciones"]) ? $_POST["observaciones"] : "");

$cuit = afip_valor('cuit');

// Factura y sus ventas (sin imagen_producto: multiplicaba las filas y el total).
$stmt = $conn->prepare("SELECT v.precio as precio_unidad, v.cantidad, f.*
        FROM ventas v INNER JOIN factura f ON v.factura_id = f.id
        WHERE f.id = ?");
$stmt->bind_param("i", $factura_id);
$stmt->execute();
$resultado = $stmt->get_result();
$items = array();
while ($row = $resultado->fetch_assoc()) $items[] = $row;
$stmt->close();
if (!$items) nc_salir("Factura inexistente", "No se encontró la factura seleccionada o no tiene productos.");
$factura = $items[0];
$total = 0;
foreach ($items as $row) $total += $row["precio_unidad"] * $row["cantidad"];
if ($factura["cae"] == "") nc_salir("Factura sin CAE", "La factura seleccionada no fue autorizada por AFIP (presupuesto); no se puede anular con nota de crédito.");
// Un vendedor (rol 1) solo anula facturas de su sucursal; los administradores, de cualquiera.
if ($rol < 4 && intval($factura["sucursal_id"]) !== intval($sucursal_usuario)) {
	nc_salir("No autorizado", "La factura pertenece a otra sucursal.");
}

$documento 		= $factura["documento"];
$nombre 		= $factura["nombre"];
$tipoDocumento 	= $factura["tipo_documento"];
$iva 			= $factura["iva"];
$direccion		= $factura["direccion"];
$numero_factura = intval($factura["numero"]);

// Sucursal de la FACTURA (no la del usuario logueado): define el punto de venta.
$arrSucursal = array();
$stmt = $conn->prepare("SELECT * FROM sucursales WHERE id = ?");
$stmt->bind_param("i", $factura["sucursal_id"]);
$stmt->execute();
$res_sucursal = $stmt->get_result();
if ($res_sucursal && $res_sucursal->num_rows > 0) $arrSucursal = $res_sucursal->fetch_assoc();
$stmt->close();
if (!$arrSucursal) nc_salir("Sucursal inexistente", "La sucursal de la factura ya no existe; avise al administrador.");

$ptovta = legacy_ptovta_comprobante($factura["pdf"], $arrSucursal);
if ($ptovta === null) {
	nc_salir("No se emitió la nota de crédito",
		"La sucursal \"".$arrSucursal["nombre"]."\" no tiene punto de venta configurado. No se emitió nada; avise al administrador.");
}

$logo = legacy_logo_pdf(__DIR__, null);
$resultado_perfil = $conn->query("SELECT logo FROM perfil");
if ($resultado_perfil && ($row_perfil = $resultado_perfil->fetch_assoc())) {
	// Ruta local: con el sitio en HTTPS la URL http:// redirige (308) y Html2Pdf falla.
	$logo = legacy_logo_pdf(__DIR__, $row_perfil["logo"]);
}

// --- Pre-vuelo: lo que puede hacer fallar el PDF se verifica ANTES de tocar AFIP.
$error_previo = legacy_prevuelo_pdf(dirname(__FILE__)."/notas_credito", $logo);
if ($error_previo !== null) {
	error_log("nota_de_credito.php: pre-vuelo fallido, no se emitio NC para factura ".$factura_id.": ".$error_previo);
	nc_salir("No se emitió la nota de crédito", $error_previo." No se emitió ningún comprobante; avise al administrador.");
}

// --- Reserva atómica de la factura (factura_id UNIQUE): el segundo intento
// (otro clic, otro operador) falla acá, antes de pedir el CAE.
legacy_reserva_limpiar($conn, NC_TABLA, NC_ASOCIADO, 30);
$fila = array(
	"sucursal_id"    => $factura["sucursal_id"],
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
$reserva = legacy_reserva_tomar($conn, NC_TABLA, NC_ASOCIADO, $factura_id, $fila);
if (isset($reserva["duplicada"])) {
	$previa = $reserva["duplicada"];
	if ($previa && $previa["cae"] != "") {
		nc_salir("La factura ya tiene una nota de crédito",
			"La factura Nro. ".$numero_factura." ya fue anulada con la nota de crédito Nro. ".$previa["numero"]." (CAE ".$previa["cae"]."). No se emitió otra.");
	}
	nc_salir("Emisión en curso", "Ya hay una nota de crédito en emisión para esta factura. Espere unos minutos y revise el reporte de notas de crédito antes de reintentar.");
}
if (!isset($reserva["id"])) {
	error_log("nota_de_credito.php: fallo la reserva de la factura ".$factura_id.": ".$reserva["errno"]." ".$reserva["error"]);
	nc_salir("No se emitió la nota de crédito", ($reserva["errno"] == 1054)
		? "La base de datos no está preparada para registrar la nota (falta la migración factura_id). Avise al administrador."
		: "No se pudo registrar la nota (código 502). No se emitió ningún comprobante; avise al administrador.");
}
$nc_id = $reserva["id"];

$fecha = explode("-",date("Y-m-d"));
try {
	$afip = afip_instance();
	$data = array(
		'CantReg' 		=> 1,  // Cantidad de comprobantes a registrar
		'PtoVta' 		=> $ptovta,  // Punto de venta DE LA FACTURA
		'CbteTipo' 		=> 13,  // Nota de Crédito C
		'Concepto' 		=> 1,  // Concepto del Comprobante: (1)Productos, (2)Servicios, (3)Productos y Servicios
		'DocTipo' 		=> 99, // Tipo de documento del comprador (99 consumidor final, ver tipos disponibles)
		'DocNro' 		=> 0,  // Número de documento del comprador (0 consumidor final)
		'CbteFch' 		=> $fecha[0].$fecha[1].$fecha[2], // Fecha del comprobante (yyyymmdd)
		'ImpTotal' 		=> $total, // Importe total del comprobante
		'ImpTotConc' 	=> 0,   // Importe neto no gravado
		'ImpNeto' 		=> $total, // Importe neto gravado
		'ImpOpEx' 		=> 0,   // Importe exento de IVA
		'ImpIVA' 		=> 0,  //Importe total de IVA
		'ImpTrib' 		=> 0,   //Importe total de tributos
		'MonId' 		=> 'PES', //Tipo de moneda usada en el comprobante
		'MonCotiz' 		=> 1,     // Cotización de la moneda usada (1 para pesos argentinos)
		'CondicionIVAReceptorId' => afip_cond_iva_receptor($iva), // RG 5616
		'CbtesAsoc' 	=> array( // ASOCIO LA FACTURA
			array(
				'Tipo' 		=> 11, // Factura C
				'PtoVta' 	=> $ptovta, // Punto de venta de la factura
				'Nro' 		=> $numero_factura,
				'Cuit' 		=> floatval($cuit)
				)
			),
	);
	if ($tipoDocumento != "" && $documento != ""){
		$data['DocTipo'] 	= $tipoDocumento;
		$data['DocNro'] 	= $documento;
	}
} catch (\Throwable $e) {
	// Todavía no se habló con AFIP: liberar y avisar.
	legacy_reserva_liberar($conn, NC_TABLA, $nc_id);
	error_log("nota_de_credito.php: no se pudo preparar la emision: ".get_class($e).": ".$e->getMessage());
	nc_salir("No se emitió la nota de crédito", "No se pudo preparar la conexión con AFIP (".$e->getMessage()."). No se emitió nada; avise al administrador.");
}

$emision = legacy_afip_emitir($afip, $data);
if ($emision["estado"] === "rechazada") {
	legacy_reserva_liberar($conn, NC_TABLA, $nc_id);
	nc_salir("Error al generar el comprobante", $emision["mensaje"]);
}
if ($emision["estado"] !== "emitida") {
	// Resultado desconocido: la reserva queda (se limpia sola a los 30 min si nadie la confirma).
	error_log("nota_de_credito.php: resultado desconocido en AFIP para factura ".$factura_id.", reserva ".$nc_id." retenida: ".$emision["mensaje"]);
	nc_salir("No se pudo confirmar la emisión", $emision["mensaje"]);
}
$res = $emision["res"];
$resCAEFchVto = explode("-",$res["CAEFchVto"]);
$res["CAEFchVto"] = count($resCAEFchVto) == 3 ? $resCAEFchVto[2]."-".$resCAEFchVto[1]."-".$resCAEFchVto[0] : $res["CAEFchVto"];

// A esta altura la NC YA fue autorizada por AFIP: pase lo que pase se graba.
$numero = substr("00000".$res["voucher_number"],-6);
$nombre_factura = "/notas_credito/".$ptovta."_".$res["CAE"]."_".$numero.".pdf";
$grabada = legacy_reserva_confirmar($conn, NC_TABLA, $nc_id,
	array("numero" => $numero, "cae" => $res["CAE"], "fechacae" => $res["CAEFchVto"], "pdf" => $nombre_factura),
	NC_ASOCIADO, $factura_id, $fila);
if (!$grabada) {
	error_log("nota_de_credito.php: NC ".$numero." (CAE ".$res["CAE"].") emitida pero NO se pudo grabar en la base. REVISAR.");
}

$devolucion = array(
	"nota_credito_id" => $nc_id,
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
$detalle = "Nota de cr&eacute;dito s/ Factura C ".substr("00000".$ptovta,-5)."-".substr("00000000".$numero_factura,-8);
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
									<b>NOTA DE CREDITO NRO.</b>&nbsp;".substr("00000".$ptovta,-6)."&nbsp;-&nbsp;".substr("000000".$res["voucher_number"],-6)."<br />
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
							<td style='border-bottom: 1px solid #000;'>".number_format(floatval($total),2,",",".")."</td>
						</tr>
						<tr>
							<td></td>
							<td style='border-bottom: 1px solid #000;'>Total</td>
							<td style='border-bottom: 1px solid #000;'>".number_format(floatval($total),2,",",".")."</td>
						</tr></table>
						<p style='text-align:right'><b>CAE Nro.:</b> ".$res["CAE"]."<br />
						<b>Fecha de Vto. CAE: </b>".$res["CAEFchVto"]."<br /></p>");

// Si el PDF falla NO puede responder 500: el front lo tomaría como "no se emitió"
// y el operador volvería a intentar (incidente 2026-09-23).
try {
	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
	$html2pdf->setDefaultFont('Arial');
	$html2pdf->writeHTML("<page>".$html."<br><br><hr style='border-style: dotted;' /><br><br></page>");
	$html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
	$devolucion["factura"] = $nombre_factura;
} catch (\Throwable $e) {
	error_log("nota_de_credito.php: NC ".$numero." (CAE ".$res["CAE"].") emitida pero fallo el PDF: ".get_class($e).": ".$e->getMessage());
	$devolucion["emitida_sin_pdf"] = true;
	$devolucion["mensaje"] = "La nota de crédito Nro. ".$res["voucher_number"]." fue emitida (CAE ".$res["CAE"].") pero no se pudo generar el PDF. NO vuelva a emitirla; puede regenerar el PDF desde el reporte de notas de crédito.";
}
echo json_encode($devolucion);
exit();
