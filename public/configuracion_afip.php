<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
ini_set('display_errors','1');

require_once ("conection.php");
if (getRol() < 2) {
    exit();
}
require 'vendor/autoload.php';
//Productos vendidos hoy por el usuario
$sql = "SELECT * FROM `productos`";
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
$caja = 540;
if ($resultado->num_rows > 0) {
    $total = $resultado->num_rows; 
}

$menu["ventas"] = "";
$menu["cargas"] = "active";
$menu["reportes"] = "";
$cuit = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cuit");

$comprobante = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/comprobante");
$emitir = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir");
$inicio_actividad = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/inicio_actividades");
$condicion_iva = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/condicion_iva");
$ingresos_brutos = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/ingresos_brutos");
$solicitar_datos = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/solicitar_datos");


$sql = "Select * FROM sucursales WHERE id = '".getSucursal($_COOKIE["sucursal"])."'";

    $resultado_sucursal = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
    if ($resultado_sucursal->num_rows > 0) {
        if ($row_sucursal = $resultado_sucursal->fetch_assoc()) {
            $ptovta = $row_sucursal["pto_vta"];
        }
    }else{
        echo "Error 100010";
        exit();
    }
require ('header.php'); 
?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op" id="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Carga</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se cargaron <?php echo $total; ?> productos</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Section -->
    <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
        <div class="alert alert-success alert-dismissable" style="display:none;" id="add_success" >
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h3 class="font-w300 push-15">Mensaje</h3>
            <p id="nombre-devuelto"></p>
        </div>
    </div>

    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/carga_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Clave Privada (key)</label>
                        <?php
                            if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/key")){
                                $key = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/key");
                            }
                        ?>                        
                        <textarea class="form-control" name="key" id="key"><?php echo $key; ?></textarea>
                    </div>
                    <div class="col-xs-6">
                        <label>Certificado (crt)</label>
                        <?php
                            if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cert")){
                                $cert = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/cert");
                            }
                        ?>
                        <textarea class="form-control" name="certificado" id="certificado"><?php echo $cert; ?></textarea>
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>Punto de Venta</label>
                        <input type="text" class="form-control" value="<?php echo $ptovta; ?>" name="ptovta" id="ptovta" />
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>Tipo Comprobante</label>
                        <?php
                        try{
                            $afip = new Afip(array("CUIT" => $cuit, "production" => TRUE));
                            $voucher_types = $afip->ElectronicBilling->GetVoucherTypes();
                        }catch(Exception $e){
                            // echo $e;
                            $voucher_types = array();
                        }
                        // exit();
                        ?>
                        <select id="comprobante"  class="form-control" name="comprobante">
                        <?php 
                        foreach ($voucher_types as $key => $value) {
                        ?>
                        <option value="<?php echo $value->Id ?>" <?php echo ($value->Id == $comprobante)?"selected='selected'":""; ?> ><?php echo $value->Desc ?></option>
                        <?php
                        }
                        ?>
                        </select>
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>CUIT</label>
                        <input type="text" class="form-control" value="<?php echo $cuit; ?>" name="cuit" id="cuit" />
                    </div>
                    <div class="col-xs-3" style="margin-top:5%;">
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="emision_online" <?php echo ($emitir == 1)?"checked=''":""; ?> ><span></span> Emitir siempre Factura Electronica
                        </label>
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>Ing. Brutos</label>
                        <input type="text" class="form-control" value="<?php echo $ingresos_brutos; ?>" name="ingresos_brutos" id="ingresos_brutos" />
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>Inicio de Actividades</label>
                        <input type="date" class="form-control" value="<?php echo $inicio_actividad; ?>" name="inicio_actividades" id="inicio_actividades" />
                    </div>
                    <div class="col-xs-3" style="margin-top:3%;">
                        <label>Condici&oacute;n frente al IVA</label>
                        <input type="text" class="form-control" value="<?php echo $condicion_iva; ?>" name="condicion_iva" id="condicion_iva" />
                    </div>
                    <div class="col-xs-3" style="margin-top:5%;">
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="solicitar_datos" <?php echo ($solicitar_datos == 1)?"checked=''":""; ?> ><span></span> Solicitar Datos al comprador
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-8 col-xs-offset-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" id="test_certificado" type="button">
                            <i class="fa fa-check push-5-r"></i>Guardar y Probar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
</div>
<div class="modal in" id="modal-large" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; padding-right: 16px;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="block block-themed block-transparent remove-margin-b">
                        <div class="block-header bg-primary-dark">
                            <ul class="block-options">
                                <li>
                                    <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                                </li>
                            </ul>
                            <h3 class="block-title">Terms &amp; Conditions</h3>
                        </div>
                        <div class="block-content">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="/assets/js/pages/afip.js?v=1.02"></script>
<?php require ("footer.php"); ?>
