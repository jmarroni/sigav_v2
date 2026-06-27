<?php

 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);
$menu["ventas"] = "active";
$menu["cargas"] = "";
$menu["reportes"] = "";
if (!isset($_COOKIE["descontarstock"])) $_COOKIE["descontarstock"]=0;
require_once ("conection.php");
require 'vendor/autoload.php';
require_once __DIR__.'/afip_bridge.php';
$afip_modo_actual = afip_modo();
require ('header.php');

// if (getRol() < 4 && getRol() != 1) {
//     exit();
// }
//Productos vendidos hoy
$sql = "SELECT * FROM `ventas` v WHERE `fecha` > '".date("Y-m-d")."'";
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas = $resultado->num_rows;
}else{
    $cantidad_de_ventas = 0;
}
$caja = 0;
$total =0;
//Calculo la caja
$sql_caja = "SELECT * FROM `caja` WHERE `fecha` > '".date("Y-m-d")." 00:00:00' and usuario ='".$_COOKIE["kiosco"]."'";
$resultado_caja = $conn->query($sql_caja);
if ($resultado_caja->num_rows > 0) {
	$caja = 0;
	while($row_caja = $resultado_caja->fetch_assoc()) {
		switch ($row_caja["operacion"]) {
			case 1:
				$caja += $row_caja["cien"] * 100 +
						$row_caja["cincuenta"] * 50 +
						$row_caja["veinte"] * 20 +
						$row_caja["diez"] * 10 +
						$row_caja["cinco"] * 5;
			break;
			case 2:
				$caja -= $row_caja["cien"] * 100 +
						$row_caja["cincuenta"] * 50 +
						$row_caja["veinte"] * 20 +
						$row_caja["diez"] * 10 +
						$row_caja["cinco"] * 5;
				break;
		}
	}
}

//Productos vendidos hoy por el usuario
$sql = "SELECT * FROM `ventas` v WHERE `fecha` > '".date("Y-m-d")."' and usuario = '".$_COOKIE["kiosco"]."' AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas_usuario = $resultado->num_rows;
    while($row = $resultado->fetch_assoc()) {
        $total += $row["precio"] * $row["cantidad"];
    }
}

// Elimino cualquier resto de venta que no se realizo para que no se vuelva a facturar, todas quedan en estado 5.
$sql_update = "DELETE FROM productos_en_carrito WHERE estado = 0 AND sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND usuario = '".$_COOKIE["kiosco"]."'";
$conn->query($sql_update);
?>
    <style>
        .ui-autocomplete-loading {
            background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
        }
    </style>
    <!-- Page Content -->
    <div class="content content-boxed sigav-app">

        <!-- Hero -->
        <div class="sg-hero">
            <p class="sg-hero__eyebrow">Punto de venta</p>
            <h1>Área de Ventas</h1>
            <p>Se vendieron <b><?php echo $cantidad_de_ventas; ?></b> productos hoy</p>
            <span class="label <?php echo $afip_modo_actual === 'prod' ? 'label-success' : 'label-warning'; ?>" style="font-size:14px;">
                AFIP: <?php echo $afip_modo_actual === 'prod' ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN'; ?>
            </span>
        </div>

        <!-- Stats -->
        <div class="sg-stats">
            <div class="sg-stat">
                <div class="sg-stat__label"><i class="fa fa-shopping-bag"></i> Productos Vendidos</div>
                <div class="sg-stat__value"><?php echo $cantidad_de_ventas_usuario; ?></div>
            </div>
            <div class="sg-stat">
                <div class="sg-stat__label"><i class="fa fa-money"></i> Caja</div>
                <div class="sg-stat__value">$<?php echo $caja; ?></div>
            </div>
            <div class="sg-stat">
                <div class="sg-stat__label"><i class="fa fa-file-text-o"></i> Facturado</div>
                <div class="sg-stat__value">$<?php echo $total; ?></div>
            </div>
            <div class="sg-stat sg-stat--accent">
                <div class="sg-stat__label"><i class="fa fa-line-chart"></i> Total</div>
                <div class="sg-stat__value">$<?php echo $total + $caja ?></div>
            </div>
        </div>

        <!-- Alertas (mismos IDs que usa ventas.js) -->
        <div class="sg-alert sg-alert--ok" id="add_success" style="display:none;">
            <i class="fa fa-check-circle"></i><span id="nombre-devuelto"></span>
        </div>
        <div class="sg-alert sg-alert--err" id="add_success_error" style="display:none;">
            <i class="fa fa-exclamation-circle"></i><span id="nombre-devuelto-error"></span>
        </div>

        <!-- Cargar producto -->
        <section class="sg-card">
            <header class="sg-card__head">
                <div class="sg-card__title"><span class="sg-dot"></span><h3>Cargar producto</h3></div>
                <p class="sg-card__hint">Escaneá el código de barras o buscá por nombre</p>
            </header>
            <div class="sg-card__body">
                <form class="sg-pos-form" action="bd_dashboard.html" method="post" onsubmit="return false;">
                    <div class="sg-pos-grid">
                        <div class="sg-field">
                            <label for="codigo-barras">Código de Barras</label>
                            <input class="form-control" type="text" id="codigo-barras" name="codigo-barras" placeholder="Lea o ingrese el código" value="">
                        </div>
                        <div class="sg-field">
                            <label for="nombre-producto">Nombre</label>
                            <input class="form-control" type="text" id="nombre-producto" name="nombre-producto" placeholder="Buscar producto…" value="">
                            <input type="hidden" id="producto_id" name="producto_id" value="">
                        </div>
                        <div class="sg-field">
                            <label for="stockactual">Stock</label>
                            <input class="form-control sg-mono-input" type="text" id="stockactual" name="stockactual" disabled value="">
                        </div>
                        <div class="sg-field">
                            <label for="cantidad">Cant.</label>
                            <input class="form-control numbers sg-mono-input" type="text" id="cantidad" name="cantidad" placeholder="1" value="1">
                        </div>
                        <div class="sg-field">
                            <label for="precio">Monto</label>
                            <input class="form-control numbers sg-mono-input" type="text" id="precio" name="precio" placeholder="0.00" value="1">
                        </div>
                        <div class="sg-field">
                            <label aria-hidden="true">&nbsp;</label>
                            <button class="sg-btn sg-btn--primary" type="button" id="anadir_venta">
                                <i class="fa fa-plus"></i> Añadir
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Venta en curso -->
        <section class="sg-card">
            <header class="sg-card__head">
                <div class="sg-card__title"><span class="sg-dot sg-dot--edit"></span><h3>Venta en curso</h3></div>
                <div class="sg-discount">
                    <label for="descuento_total_input">Descuento total (%)</label>
                    <input type="text" class="form-control numbers sg-mono-input" id="descuento_total_input" name="descuento_total_input" value="0" placeholder="0">
                </div>
                <span class="sg-total-badge">Total&nbsp; $ <span id="total_ventas">0.00</span></span>
            </header>
            <div class="sg-card__body">
                <div class="sg-table-wrap">
                    <table class="sg-sale-table">
                        <tbody id="tablaProductos"></tbody>
                    </table>
                </div>

                <!-- Forma de pago / IVA -->
                <div class="sg-subgrid">
                    <div>
                        <h4>Forma de pago</h4>
                        <div class="sg-options">
                            <label class="sg-radio" for="efectivo"><input type="radio" name="example-inline-pago" id="efectivo" checked value="1"><span class="sg-radio__dot"></span> Efectivo</label>
                            <label class="sg-radio" for="debito"><input type="radio" name="example-inline-pago" id="debito" value="2"><span class="sg-radio__dot"></span> Débito</label>
                            <label class="sg-radio" for="credito"><input type="radio" name="example-inline-pago" id="credito" value="3"><span class="sg-radio__dot"></span> Crédito</label>
                            <label class="sg-radio" for="transferencia"><input type="radio" name="example-inline-pago" id="transferencia" value="4"><span class="sg-radio__dot"></span> Transferencia</label>
                        </div>
                    </div>
                    <div>
                        <h4>Condición IVA</h4>
                        <div class="sg-options">
                            <label class="sg-radio" for="resp_i"><input type="radio" name="example-inline-iva" id="resp_i" value="1"><span class="sg-radio__dot"></span> Resp. Inscripto</label>
                            <label class="sg-radio" for="mono"><input type="radio" name="example-inline-iva" id="mono" value="2"><span class="sg-radio__dot"></span> Monotributista</label>
                            <label class="sg-radio" for="excento"><input type="radio" name="example-inline-iva" id="excento" value="3"><span class="sg-radio__dot"></span> Exento</label>
                            <label class="sg-radio" for="final"><input type="radio" name="example-inline-iva" id="final" checked value="4"><span class="sg-radio__dot"></span> Cons. Final</label>
                        </div>
                    </div>
                </div>

                <!-- Toggles ocultos (los controla ventas.js) -->
                <div style="display:none;">
                    <label><input type="checkbox" id="emision_online" checked> Emitir Factura</label>
                    <label><input type="checkbox" id="descontar_stock" checked> Descontar stock</label>
                </div>

                <?php
                $solicitar = afip_valor('solicitar_datos');
                if (intval($solicitar) == 1){ ?>
                <!-- Datos del cliente (cuando AFIP los solicita) -->
                <div class="sg-fieldset" style="margin-top:24px;">
                    <span class="sg-fieldset__label">Datos del cliente · Facturación</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="nombre-cliente">Nombre y apellido</label>
                            <input class="form-control" type="hidden" id="clientes_id" name="clientes_id" value="">
                            <input class="form-control" type="text" id="nombre-cliente" name="nombre-cliente" autocomplete="off" placeholder="Ingrese el nombre y apellido" value="">
                        </div>
                        <div class="sg-field">
                            <label for="direccion-cliente">Dirección</label>
                            <input class="form-control" type="text" id="direccion-cliente" name="direccion-cliente" placeholder="Dirección 324, Viedma, Río Negro" value="">
                        </div>
                        <div class="sg-field">
                            <label for="tipo">Tipo de Documento</label>
                            <select id="tipo" class="form-control" name="tipo">
                            <?php
                            try{
                                $afip = afip_instance();
                                $document_types = $afip->ElectronicBilling->GetDocumentTypes();
                                foreach ($document_types as $key => $value) {
                                    if ($value->Desc == "CUIT" || $value->Desc == "CUIL" || $value->Desc == "CDI"){
                            ?>
                                    <option value="<?php echo $value->Id ?>" ><?php echo $value->Desc ?></option>
                            <?php
                                    }
                                }
                            ?>
                            <?php
                            }catch(Exception $e){
                                $document_types = array();
                                ?>
                                    <option value="0" >Sin documentos cargados</option>
                                <?php
                            }
                            ?>
                            </select>
                        </div>
                        <div class="sg-field">
                            <label for="documento-cliente">CUIT, CUIL ó CDI</label>
                            <input class="form-control" type="text" maxlength="11" id="documento-cliente" name="documento-cliente" placeholder="Sin guiones, XXXXXXXXXXX" value="">
                        </div>
                        <div class="sg-field">
                            <label for="fecha">Fecha de Facturación</label>
                            <input class="form-control" type="date" id="fecha" name="fecha" value="<?php echo date("Y-m-d") ?>">
                        </div>
                    </div>
                </div>
                <?php
                }
                $emitir = afip_valor('emitir'); ?>

                <!-- Acciones de la venta -->
                <div class="sg-cta-row">
                    <button class="sg-btn sg-btn--primary sg-btn--lg" type="button" id="concretar_venta">
                        <i class="fa fa-check"></i> Concretar venta y facturar
                    </button>
                    <button class="sg-btn sg-btn--ghost sg-btn--lg" type="button" id="presupuesto" name="presupuesto" style="display:none;">
                        <i class="fa fa-check"></i> Concretar venta
                    </button>
                    <span id="espere_venta_activa" class="sg-note" style="display:none;">(En proceso de emisión, por favor aguarde unos segundos)</span>
                    <span id="emitir_online" class="sg-note sg-note--accent">Emisión online de Factura Electrónica habilitada</span>
                </div>
            </div>
        </section>

        <!-- Factura emitida -->
        <section class="sg-card" id="factura_iframe" style="display:none;">
            <header class="sg-card__head">
                <div class="sg-card__title"><span class="sg-dot"></span><h3>Factura</h3></div>
            </header>
            <div class="sg-card__body">
                <div class="sg-grid sg-grid--2" style="align-items:end; margin-bottom:18px;">
                    <div class="sg-field">
                        <label for="mail_factura">Mail donde enviar la factura</label>
                        <input class="form-control" type="text" id="mail_factura" name="mail_factura" placeholder="ingrese el mail" value="">
                    </div>
                    <div class="sg-field">
                        <label aria-hidden="true">&nbsp;</label>
                        <button class="sg-btn sg-btn--ghost" type="button" id="enviar_mail" name="enviar_mail">
                            <i class="fa fa-paper-plane"></i> Enviar factura
                        </button>
                    </div>
                </div>
                <p id="mensaje_enviado" class="sg-note sg-note--accent" style="display:none;">El mail fue enviado correctamente</p>
                <iframe id="iframe"></iframe>
            </div>
        </section>

        <?php if (intval($emitir) != 1){ ?>
        <!-- Ventas del día -->
        <section class="sg-card">
            <header class="sg-card__head">
                <div class="sg-card__title"><span class="sg-dot"></span><h3>Ventas de hoy</h3></div>
            </header>
            <div class="sg-card__body sg-table-wrap">
                <table class="sg-sale-table">
                    <tbody>
                    <?php
                    $sql = "SELECT v.*,v.fecha as fecha_vta, v.usuario as usuario_vta,pr.*,st.stock as stock_sucursal FROM `ventas` v inner join productos pr on pr.id = v.productos_id left join stock st ON (st.productos_id = pr.id AND st.sucursal_id = ".getSucursal($_COOKIE["sucursal"]).") WHERE v.`fecha` > '".date("Y-m-d")."' ORDER BY v.id DESC";
                    // echo $sql;exit();
                    $resultado = $conn->query($sql);
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { ?>
                            <tr>
                                <td style="width:72px;">
                                    <?php
                                    $sql_img = "SELECT * FROM imagen_producto WHERE productos_id =".$row["id"];
                                    // echo $sql;exit();
                                    $resultado_img = $conn->query($sql_img);
                                    if ($resultado_img->num_rows > 0) {
                                        if ($row_img = $resultado_img->fetch_assoc()) {?>
                                        <img class="img-responsive" src="<?php echo (isset($row_img["imagen_url"]))?str_replace('/'.$row["id"].'/','/'.$row["id"].'/thumb_300x300_',$row_img["imagen_url"]):"assets/img/photos/no-image-featured-image.png"; ?>" alt="">
                                    <?php } }else{ ?>
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png" alt="">
                                    <?php } ?>
                                </td>
                                <td>
                                    <h4><?php echo $row["nombre"] ?></h4>
                                    <p>Vendido a las <?php echo $row["fecha_vta"] ?> · por <?php echo $row["usuario_vta"] ?></p>
                                </td>
                                <td>
                                    <p>Precio: <span class="sg-strong">$ <?php echo  $row["precio"] ?></span></p>
                                    <p>Quedan en stock: <?php echo (isset($row["stock_sucursal"]))?$row["stock_sucursal"]:0; ?></p>
                                </td>
                                <td class="sg-num">
                                    <span class="h1">$ <?php echo $row["precio"] * $row["cantidad"]; ?></span>
                                </td>
                            </tr>
                        <?php }
                    } else {?>
                        <tr>
                            <td colspan="4"><div class="sg-sale-empty">No hay ventas en el día de hoy</div></td>
                        </tr>
                    <?php }
                    $conn->close();
                    ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php  } ?>
    </div>
    <!-- END Page Content -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/pages/ventas.js?v=<?php echo rand(); ?>"></script>
<?php require ("footer.php"); ?>
