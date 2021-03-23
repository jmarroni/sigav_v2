<?php

 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);
$menu["ventas"] = "active";
$menu["cargas"] = "";
$menu["reportes"] = "";
require_once ("conection.php");
require 'vendor/autoload.php';
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
}else{
	$cantidad_de_ventas = 0;
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
        <div class="content content-boxed">
            <!-- Section -->
            <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/_photo25@2x.jpg');background-position-y:-280px;">
                <div class="bg-black-op">
                    <div class="content">
                        <div class="block block-transparent block-themed text-center">
                            <div class="block-content">
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Ventas</h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se vendieron <?php echo $cantidad_de_ventas; ?> productos hoy</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Stats -->
            <div class="row text-uppercase">
                <div class="col-xs-6 col-sm-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div class="font-s12 font-w700">Productos Vendidos</div>
                            <a class="h2 font-w300 text-primary" href="javascript:void(0)"><?php echo $cantidad_de_ventas_usuario; ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div class="font-s12 font-w700">Caja</div>
                            <a class="h2 font-w300 text-primary" href="javascript:void(0)">$ <?php echo $caja; ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div class="font-s12 font-w700">Facturado</div>
                            <a class="h2 font-w300 text-primary" href="javascript:void(0)">$ <?php echo $total; ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div class="font-s12 font-w700">Total</div>
                            <a class="h2 font-w300 text-primary" href="javascript:void(0)">$ <?php echo $total + $caja ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Stats -->
            <div class="block block-rounded" id="add_success" style="display: none;background-color: #46c37b !important;color:white;">
                <div class="block-header">
                    <div class="col-xs-12 bg-success" id="nombre-devuelto"></div>
                </div>
            </div>
            <div class="block block-rounded" id="add_success_error" style="display: none;background-color: #d26a5c !important;color:white;">
                <div class="block-header">
                    <div class="col-xs-12 bg-danger" id="nombre-devuelto-error"></div>
                </div>
            </div>
            <div class="block block-rounded">
                <div class="block-content">
                    <form class="form-horizontal" action="bd_dashboard.html" method="post" onsubmit="return false;">
                        <div class="form-group">
                            <div class="col-xs-4">
                                <label for="bd-qsettings-name">Codigo de Barras</label>
                                <input class="form-control" type="text" id="codigo-barras" name="codigo-barras" placeholder="Lea o ingrese el codigo de barras" value="">
                            </div>
                            <div class="col-xs-4">
                                <label for="bd-qsettings-name">Nombre</label>
                                <input class="form-control" type="text" id="nombre-producto" name="nombre-producto" placeholder="Ingrese parte del nombre" value="">
                                <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="">
                            </div>
                            <div class="col-xs-1">
                                <label for="bd-qsettings-name">Cantidad</label>
                                <input class="form-control" type="text" id="cantidad" name="cantidad" placeholder="1,2,3..." value="1">
                            </div>
                            <div class="col-xs-1">
                                <label>Monto</label>
                                <input class="form-control" type="text" id="precio" name="precio" placeholder="utilizar el . como decimal" value="1">
                            </div>
                            <div class="col-xs-2">
                                <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="anadir_venta">
                                    <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Products -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Venta Actual Total: $ <label id="total_ventas" style="font-size:15px;">0.00</label></h3>
                </div>
                <div class="block-content" style="text-align: center">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <tbody id="tablaProductos">
                
                            </tbody>
                        </table>
                    </div>
                    <div class="col-xs-4">
                    <h3 class="block-title">Forma de Pago</h3>
                        <label class="radio-inline" for="example-inline-efectivo">
                            <input type="radio" name="example-inline-pago"  id="efectivo" checked='true' value="1"> Efectivo
                        </label>
                        <label class="radio-inline" for="example-inline-debito">
                            <input type="radio" name="example-inline-pago" id="debito" value="2"> Debito
                        </label>
                        <label class="radio-inline" for="example-inline-credito">
                            <input type="radio" name="example-inline-pago" id="credito" value="3"> Credito
                        </label>
                        <label class="radio-inline" for="example-inline-credito">
                            <input type="radio" name="example-inline-pago" id="transferencia" value="4"> Transferencia
                        </label>
                    </div>
                    <div class="col-xs-4">
                        <h3 class="block-title">IVA</h3>
                        <label class="radio-inline" for="example-inline-efectivo">
                            <input type="radio" name="example-inline-iva"  id="resp_i"  value="1"> Resp. Inscripto
                        </label>
                        <label class="radio-inline" for="example-inline-debito">
                            <input type="radio" name="example-inline-iva" id="mono" value="2"> Monotributista
                        </label>
                        <label class="radio-inline" for="example-inline-credito">
                            <input type="radio" name="example-inline-iva" id="excento" value="3"> Excento
                        </label>
                        <label class="radio-inline" for="example-inline-credito">
                            <input type="radio" name="example-inline-iva" checked='true' id="final" value="4"> Cons. Final
                        </label>
                    </div>
                    <div class="col-xs-2">
                    <?php $emitir = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir"); ?>
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="emision_online" <?php echo ($emitir == 1)?"checked=''":""; ?> ><span></span> Emitir Factura
                        </label>
                    </div>
                       <div class="col-xs-2">
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="descontar_stock"><span></span> Descontar stock
                        </label>
                    </div>
                    <?php
                    $solicitar = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/solicitar_datos"); 
                    if (intval($solicitar) == 1){?>
                    
                    <div class="col-xs-12" style="margin-top:4%;">
                        <div class="form-group">
                            <div class="col-xs-3">
                                <label for="bd-qsettings-name">Nombre y apellido</label>
                                <input class="form-control" type="hidden" id="clientes_id" name="clientes_id" placeholder="" value="">
                                <input class="form-control" type="text" id="nombre-cliente" name="nombre-cliente" autocomplete="false" placeholder="Ingrese el nombre y apellido" value="">
                            </div>
                            
                            <div class="col-xs-3">
                                <label>Direcci&oacute;n</label>
                                <input class="form-control" type="text" id="direccion-cliente" name="direccion-cliente" placeholder="Direccion 324, Viedma, Rio Negro" value="">
                            </div>
                            <div class="col-xs-2">
                                <label for="bd-qsettings-name">Tipo de Documento</label>
                                <select id="tipo"  class="form-control" name="tipo">
                                <?php
                                try{
                                    $cuit = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit");

                                    $afip = new Afip(array("CUIT" => $cuit, "production" => TRUE));
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
                            <div class="col-xs-2">
                                <label for="bd-qsettings-name">CUIT, CUIL &oacute; CDI</label>
                                <input class="form-control" type="text" maxlength="11" id="documento-cliente" name="documento-cliente" placeholder=" Sin guiones, XXXXXXXXXXX" value="">
                            </div>
                            
                            <div class="col-xs-2">
                                <label>Fecha de Facturaci&oacute;n</label>
                                <input class="form-control" type="date" id="fecha" name="fecha" placeholder="dd/mm/yyyy" value="<?php echo date("Y-m-d") ?>">
                            </div>
                        </div>
                    </div>
                    <?php 
                    }
                    $emitir = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir"); ?>
                    
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="concretar_venta" style="<?php if (intval($emitir) != 1) echo "display:none;"; ?>margin-bottom: 20px;margin-top: 20px;width: 30%;">
                            <i class="fa fa-check push-5-r"></i>Concretar Venta y facturar
                        </button>
                        <div id="espere_venta_activa" style="display:none;" ><i>(En proceso de emision, por favor aguarde unos segundos)</i></div>
                        <div id="emitir_online" style="<?php if (intval($emitir) != 1) echo "display:none;"; ?>"><i>(Se encuentra habilitada la emision online de Factura Electronica)</i></div>
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="presupuesto" name="presupuesto" style="<?php if (intval($emitir) == 1) echo "display:none;"; ?>margin-top: 20px;margin-bottom: 20px;width: 30%;">
                            <i class="fa fa-check push-5-r"></i>Concretar Venta
                        </button>                    
                </div>
            </div>
            <div class="block block-rounded" id="factura_iframe" >
                <div class="block-header">
                    <h3 class="block-title">Factura</h3>
                </div>
                <div class="col-xs-12" style="margin-top:4%;margin-bottom:4%;">
                    <div class="form-group">
                        <div class="col-xs-6">
                            <label for="bd-qsettings-name">Mail donde enviar factura:</label>
                            <input class="form-control" type="text" id="mail_factura" name="mail_factura" placeholder="ingrese el mail" value="">
                        </div><div class="col-xs-6">
                            <button class="btn btn-minw btn-rounded btn-primary" type="button" id="enviar_mail" name="enviar_mail" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                                <i class="fa fa-check push-5-r"></i>Enviar Factura
                            </button>
                        </div><div  style="display:none" id="mensaje_enviado" class="col-xs-12">
                            <p style="color:#2d62a5;font-weight: bold;font-size: 12pt;"><i>El mail fue enviado correctamente</i></p>
                        </div>
                    </div>
                </div>
                <div class="block-content" style="text-align: center">
                    <iframe id="iframe" style="width: 98%;height: 300px;margin-bottom: 30px;"></iframe>
                </div>
            </div>
            <!-- END Products -->
            <?php if (intval($emitir) != 1){?>
            <!-- Productos Vendidos -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Ventas</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <tbody>
                            <?php
                            $sql = "SELECT v.*,v.fecha as fecha_vta, v.usuario as usuario_vta,pr.*,st.stock as stock_sucursal FROM `ventas` v inner join productos pr on pr.id = v.productos_id left join stock st ON (st.productos_id = pr.id AND st.sucursal_id = ".getSucursal($_COOKIE["sucursal"]).") WHERE v.`fecha` > '".date("Y-m-d")."' ORDER BY v.id DESC";
                            // echo $sql;exit();
                            $resultado = $conn->query($sql);
                            if ($resultado->num_rows > 0) {
                                // output data of each row
                                while($row = $resultado->fetch_assoc()) { ?>
                                    <tr>
                                       <td class="text-center">
                                           <div style="width: 180px;">
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
                                            </div>
                                           </td>
                                       <td>
                                           <h4><?php echo $row["nombre"] ?></h4>
                                        <p class="remove-margin-b">Producto Vendido a las <?php echo $row["fecha_vta"] ?></p>
                                        <a class="font-w600" href="javascript:void(0)">Por <?php echo $row["usuario_vta"] ?></a>
                                            </td>
                                        <td>
                                            <p class="remove-margin-b">Precio: <span class="text-gray-dark">$ <?php echo  $row["precio"] ?></span></p>
                                            <p>Quedan en Stock: <span class="text-gray-dark"><?php echo (isset($row["stock_sucursal"]))?$row["stock_sucursal"]:0; ?></span></p>
                                        </td>
                                        <td class="text-center">
                                            <span class="h1 font-w700 text-success">$ <?php echo $row["precio"] * $row["cantidad"]; ?></span>
                                        </td>
                                    </tr>
                                <?php }
                            } else {?>
                                <tr>
                                    <td>
                                        <label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay ventas en el d&iacute;a de hoy</label>
                                    </td>
                                </tr>
                            <?php }
                            $conn->close();
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- END Products -->
            <?php  } ?>
        </div>
        <!-- END Page Content -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/pages/ventas.js?v=<?php echo(rand()); ?>"></script>
<?php require ("footer.php"); ?>