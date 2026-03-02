<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
ini_set('display_errors','0');

require_once ("conection.php");
require_once ("afip_config.php");
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

// DATOS PARA EL COMPROBANTE (desde directorio seguro)
$cuit = getAfipValue('cuit');
$comprobante = getAfipValue('comprobante');
$emitir = getAfipValue('emitir');
$inicio_actividad = getAfipValue('inicio_actividades');
$condicion_iva = getAfipValue('condicion_iva');
$ingresos_brutos = getAfipValue('ingresos_brutos');
$solicitar_datos = getAfipValue('solicitar_datos');
$access_token = getAfipValue('access_token');
$modo_afip = getAfipMode(); // 'produccion' o 'homologacion'

// Obtener credenciales de ambos modos
$creds_homologacion = getAfipCredentials(false);
$creds_produccion = getAfipCredentials(true);
$has_creds_homologacion = hasAfipCredentials(false);
$has_creds_produccion = hasAfipCredentials(true);


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

    <!-- MODO AFIP: Produccion / Homologacion -->
    <div class="block block-rounded" style="background-color: <?php echo ($modo_afip == 'produccion') ? '#5cb85c' : '#f0ad4e'; ?>; margin-bottom: 20px;">
        <div class="block-content" style="padding: 15px;">
            <div class="row">
                <div class="col-xs-6">
                    <h4 style="color: white; margin: 0;">
                        <i class="fa <?php echo ($modo_afip == 'produccion') ? 'fa-check-circle' : 'fa-flask'; ?>"></i>
                        Modo Actual: <strong><?php echo ($modo_afip == 'produccion') ? 'PRODUCCION' : 'HOMOLOGACION (Pruebas)'; ?></strong>
                    </h4>
                    <p style="color: white; margin: 5px 0 0 0; font-size: 12px;">
                        <?php echo ($modo_afip == 'produccion')
                            ? 'Las facturas se emiten contra AFIP real y son validas fiscalmente.'
                            : 'Las facturas son de prueba y NO tienen validez fiscal. Use este modo para testear.'; ?>
                    </p>
                </div>
                <div class="col-xs-6 text-right">
                    <label class="css-input switch switch-lg switch-warning" style="margin-top: 5px;">
                        <input type="checkbox" id="modo_produccion" <?php echo ($modo_afip == 'produccion')?"checked=''":""; ?> >
                        <span></span>
                        <span style="color: white; font-weight: bold; margin-left: 10px;">
                            <?php echo ($modo_afip == 'produccion') ? 'Produccion Activa' : 'Activar Produccion'; ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuracion General -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title"><i class="fa fa-cog"></i> Configuracion General</h3>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-xs-3">
                    <label>CUIT</label>
                    <input type="text" class="form-control" value="<?php echo $cuit; ?>" name="cuit" id="cuit" />
                </div>
                <div class="col-xs-3">
                    <label>Punto de Venta</label>
                    <input type="text" class="form-control" value="<?php echo $ptovta; ?>" name="ptovta" id="ptovta" />
                </div>
                <div class="col-xs-3">
                    <label>Tipo Comprobante</label>
                    <?php
                    try{
                        $afip = new Afip(getAfipConfig(floatval($cuit)));
                        $voucher_types = $afip->ElectronicBilling->GetVoucherTypes();
                    }catch(Exception $e){
                        $voucher_types = array();
                    }
                    ?>
                    <select id="comprobante" class="form-control" name="comprobante">
                    <?php
                    foreach ($voucher_types as $k => $value) {
                    ?>
                    <option value="<?php echo $value->Id ?>" <?php echo ($value->Id == $comprobante)?"selected='selected'":""; ?> ><?php echo $value->Desc ?></option>
                    <?php
                    }
                    ?>
                    </select>
                </div>
                <div class="col-xs-3">
                    <label>Ing. Brutos</label>
                    <input type="text" class="form-control" value="<?php echo $ingresos_brutos; ?>" name="ingresos_brutos" id="ingresos_brutos" />
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-3">
                    <label>Inicio de Actividades</label>
                    <input type="date" class="form-control" value="<?php echo $inicio_actividad; ?>" name="inicio_actividades" id="inicio_actividades" />
                </div>
                <div class="col-xs-3">
                    <label>Condici&oacute;n frente al IVA</label>
                    <input type="text" class="form-control" value="<?php echo $condicion_iva; ?>" name="condicion_iva" id="condicion_iva" />
                </div>
                <div class="col-xs-3" style="padding-top: 25px;">
                    <label class="css-input switch switch-success">
                        <input type="checkbox" id="emision_online" <?php echo ($emitir == 1)?"checked=''":""; ?> ><span></span> Emitir siempre Factura Electronica
                    </label>
                </div>
                <div class="col-xs-3" style="padding-top: 25px;">
                    <label class="css-input switch switch-success">
                        <input type="checkbox" id="solicitar_datos" <?php echo ($solicitar_datos == 1)?"checked=''":""; ?> ><span></span> Solicitar Datos al comprador
                    </label>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-12 text-center">
                    <button class="btn btn-primary" id="guardar_config" type="button">
                        <i class="fa fa-save"></i> Guardar Configuracion General
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Access Token AFIP SDK -->
    <div class="block block-rounded" style="border: 2px solid #337ab7;">
        <div class="block-header" style="background-color: #337ab7; color: white;">
            <h3 class="block-title">
                <i class="fa fa-key"></i> Access Token AFIP SDK
                <?php if (!empty($access_token)): ?>
                    <span class="label label-success" style="margin-left: 10px;"><i class="fa fa-check"></i> Configurado</span>
                <?php else: ?>
                    <span class="label label-danger" style="margin-left: 10px;"><i class="fa fa-times"></i> Sin configurar</span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="block-content">
            <p style="color: #31708f; background: #d9edf7; padding: 10px; border-radius: 4px;">
                <i class="fa fa-info-circle"></i> <strong>REQUERIDO:</strong> El SDK v1.0+ requiere un Access Token.
                Obtenlo gratis en <a href="https://app.afipsdk.com" target="_blank">https://app.afipsdk.com</a>
            </p>
            <div class="row">
                <div class="col-xs-12">
                    <label>Access Token</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($access_token ?? ''); ?>" name="access_token" id="access_token" placeholder="Ingresa tu access token de AFIP SDK" />
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-12 text-center">
                    <button class="btn btn-info" id="guardar_access_token" type="button">
                        <i class="fa fa-save"></i> Guardar Access Token
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Credenciales HOMOLOGACION -->
    <div class="block block-rounded" style="border: 2px solid #f0ad4e;">
        <div class="block-header" style="background-color: #f0ad4e; color: white;">
            <h3 class="block-title">
                <i class="fa fa-flask"></i> Credenciales HOMOLOGACION (Pruebas)
                <?php if ($has_creds_homologacion): ?>
                    <span class="label label-success" style="margin-left: 10px;"><i class="fa fa-check"></i> Configurado</span>
                <?php else: ?>
                    <span class="label label-danger" style="margin-left: 10px;"><i class="fa fa-times"></i> Sin configurar</span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="block-content">
            <p style="color: #8a6d3b; background: #fcf8e3; padding: 10px; border-radius: 4px;">
                <i class="fa fa-info-circle"></i> Use estas credenciales para probar la facturacion sin generar comprobantes fiscales reales.
                Debe generar el certificado desde AFIP en modo <strong>Testing/Homologacion</strong>.
            </p>
            <div class="row">
                <div class="col-xs-6">
                    <label>Clave Privada (key) - Homologacion</label>
                    <textarea class="form-control" id="key_homologacion" rows="6"><?php echo htmlspecialchars($creds_homologacion['key']); ?></textarea>
                </div>
                <div class="col-xs-6">
                    <label>Certificado (crt) - Homologacion</label>
                    <textarea class="form-control" id="cert_homologacion" rows="6"><?php echo htmlspecialchars($creds_homologacion['cert']); ?></textarea>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-12 text-center">
                    <button class="btn btn-warning" id="guardar_homologacion" type="button">
                        <i class="fa fa-save"></i> Guardar Credenciales Homologacion
                    </button>
                    <button class="btn btn-default" id="probar_homologacion" type="button">
                        <i class="fa fa-plug"></i> Probar Conexion
                    </button>
                </div>
            </div>
            <div id="resultado_homologacion" style="margin-top: 10px; display: none;"></div>
        </div>
    </div>

    <!-- Credenciales PRODUCCION -->
    <div class="block block-rounded" style="border: 2px solid #5cb85c;">
        <div class="block-header" style="background-color: #5cb85c; color: white;">
            <h3 class="block-title">
                <i class="fa fa-check-circle"></i> Credenciales PRODUCCION (Real)
                <?php if ($has_creds_produccion): ?>
                    <span class="label label-success" style="margin-left: 10px;"><i class="fa fa-check"></i> Configurado</span>
                <?php else: ?>
                    <span class="label label-danger" style="margin-left: 10px;"><i class="fa fa-times"></i> Sin configurar</span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="block-content">
            <p style="color: #3c763d; background: #dff0d8; padding: 10px; border-radius: 4px;">
                <i class="fa fa-exclamation-triangle"></i> <strong>ATENCION:</strong> Estas credenciales generan facturas con validez fiscal REAL ante AFIP.
                Debe generar el certificado desde AFIP en modo <strong>Produccion</strong>.
            </p>
            <div class="row">
                <div class="col-xs-6">
                    <label>Clave Privada (key) - Produccion</label>
                    <textarea class="form-control" id="key_produccion" rows="6"><?php echo htmlspecialchars($creds_produccion['key']); ?></textarea>
                </div>
                <div class="col-xs-6">
                    <label>Certificado (crt) - Produccion</label>
                    <textarea class="form-control" id="cert_produccion" rows="6"><?php echo htmlspecialchars($creds_produccion['cert']); ?></textarea>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-12 text-center">
                    <button class="btn btn-success" id="guardar_produccion" type="button">
                        <i class="fa fa-save"></i> Guardar Credenciales Produccion
                    </button>
                    <button class="btn btn-default" id="probar_produccion" type="button">
                        <i class="fa fa-plug"></i> Probar Conexion
                    </button>
                </div>
            </div>
            <div id="resultado_produccion" style="margin-top: 10px; display: none;"></div>
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
<script src="/assets/js/pages/afip.js?v=2.0"></script>
<?php require ("footer.php"); ?>
