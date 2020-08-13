<?php

 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

// Datos a completar

		$html_datos_cliente = "<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> </td>
								</tr><tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b></td>
								</tr><tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b></td>
								</tr>
								<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> Cons. Final</td>
								</tr>
								<tr>
									<td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> Efetivo</td>
								</tr>
								<tr>
									<td colspan='3' style='height:30px;'>&nbsp;</td>
								</tr>";

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
													<img = src='http://mercado-artesanal.com.ar/assets/perfil/20190524111724-54396137.jpeg' style='height:80px;width:120px;'/>
												</td>
												<td>	
													<br />Central
													<br />Artemidez Zatti 287<br />
													8500 - Rio Negro<br />
													<b>Comprobante Electronico</b>
												</td>
											</tr>
										</table>
									</td>
									<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>C</td>
									<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
										<b>@@FACTURANRO@@</b>&nbsp;".substr("0000020",-6)."&nbsp;-&nbsp;".substr("0000000056",-8)."<br />
										<b>CUIT</b>&nbsp;30715251988<br />
										<b>Fecha de Emisi&oacute;n</b>&nbsp;06-06-2020<br />
										<b>Ing.&nbsp;Bruto</b>&nbsp;30715251988<br />

									</td>
								</tr>
								$html_datos_cliente
								<tr>
									<td style='border-bottom: 1px solid #000;'><b>Descripcion</b></td>
									<td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
									<td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
								</tr>
								<tr>
								<td style='border-bottom: 1px solid #000;'><i>BOMBILLA CON DETALLE</i></td>
								<td style='border-bottom: 1px solid #000;'>10</td>
								<td style='border-bottom: 1px solid #000;'>756</td>
							</tr>
							<tr>
							<td></td>
							<td style='border-bottom: 1px solid #000;'>Total</td>
							<td style='border-bottom: 1px solid #000;'>7560</td>
						</tr></table><p style='text-align:right'><b>CAE Nro.:</b> 70238737165188<br />
						<b>Fecha de Vto. CAE: </b>16-06-2020<br /></p>");
	//echo $html;exit();
	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
	$html2pdf->setDefaultFont('Arial');
	$html = str_replace("@@FACTURANRO@@","",$html);
	$html2pdf->writeHTML("<page>".str_replace("@@COMPROBANTE@@","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("@@COMPROBANTE@@","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");
	$html2pdf->Output();
	$nombre_factura ="";
	//$html2pdf->Output(dirname(__FILE__).$nombre_factura, "F");
	$devolucion["factura"] = $nombre_factura;

	exit();
?>
