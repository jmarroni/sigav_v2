<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: ./');
}
require_once ("./conection.php");
if (getRol() < 3) {
    exit();
}
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
require ('header.php'); ?>
<style>
    .ui-autocomplete-loading {
        background: white url("./assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="row">
        <div class="col-lg-12">
        <div class="alert alert-success alert-dismissable" id="ok" style="display:none;">

                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                        <h3 class="font-w300 push-15">Excelente</h3>
                                        <p>Transferencia realizada con exito!</p>
        </div>

        <!-- Danger Alert -->
        <div class="alert alert-danger alert-dismissable" id="error" style="display:none;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h3 class="font-w300 push-15">Error</h3>
            <p>Opps, ocurrio un error en la transferencia, consulte al Administrador de sistema!</p>
        </div>
        <!-- END Danger Alert -->

    </div>
        <div class="col-lg-12">
            <!-- Simple Progress Wizard (.js-wizard-simple class is initialized in js/pages/base_forms_wizard.js) -->
            <!-- For more examples you can check out https://github.com/VinceG/twitter-bootstrap-wizard -->
            <div class="js-wizard-simple block">
                <!-- Steps Progress -->
                <div class="block-content block-content-mini block-content-full border-b">
                    <div class="wizard-progress progress active remove-margin-b">
                        <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                    </div>
                </div>
                <!-- END Steps Progress -->

                <!-- Step Tabs -->
                <ul class="nav nav-tabs nav-tabs-alt nav-justified">
                    <li class="active">
                        <a href="#simple-progress-step1" data-toggle="tab" aria-expanded="false">1. Sucursal</a>
                    </li>
                    <li class="">
                        <a href="#simple-progress-step2" data-toggle="tab" aria-expanded="false">2. Productos</a>
                    </li>
                    <li class="">
                        <a href="#simple-progress-step3" data-toggle="tab" aria-expanded="true">3. Confirmaci&oacute;n</a>
                    </li>
                </ul>
                <!-- END Step Tabs -->

                <!-- Form -->
                <form class="form-horizontal" action="base_forms_wizard.html" method="post">
                    <!-- Steps Content -->
                    <div class="block-content tab-content">
                        <!-- Step 1 -->
                        <div class="tab-pane fade fade-right push-30-t push-50 active in" id="simple-progress-step1">
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <select class="form-control" id="sucursal_origen" name="sucursal_origen" size="1">
                                            <option value="">Selecciona la sucursal de Origen</option>
                                            <?php
                                                $sql = "SELECT *
                                                        FROM sucursales
                                                        ORDER BY nombre DESC ";
                                                $resultado = $conn->query($sql) or die($conn->error);
                                                $datos = '{"data":"no data"}';
                                                if ($resultado->num_rows > 0) {
                                                    // output data of each row
                                                    while($row = $resultado->fetch_assoc()) {
                                                        ?>
                                                        <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                                                    <?php }} ?>
                                        </select>
                                        <label for="sucursal_origen">Sucursal de Origen</label>

                                        <!-- Mensaje error sucursal_origen -->
                                        <div id="error_sucursal_origen" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>Ingrese una sucursal de origen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <select class="form-control" id="sucursal_destino" name="sucursal_destino" size="1">
                                                <option value="">Selecciona la sucursal de Destino</option>
                                                    <?php
                                                        $sql = "SELECT *
                                                                FROM sucursales
                                                                ORDER BY nombre DESC ";
                                                $resultado = $conn->query($sql) or die($conn->error);
                                                $datos = '{"data":"no data"}';
                                                if ($resultado->num_rows > 0) {
                                                    // output data of each row
                                                    while($row = $resultado->fetch_assoc()) {
                                                        ?>
                                                        <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                                                    <?php }} ?>
                                        </select>
                                        </select>
                                        <label for="sucursal_destino">Sucursal de Destino</label>

                                        <!-- Mensaje error sucursal_origen -->
                                        <div id="error_sucursal_destino" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>Ingrese una sucursal de destino</p>
                                        </div>

                                        <!-- Mensaje error sucursales iguales -->
                                        <div id="error_sucursales_iguales" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>La sucursal origen y destino no pueden ser las mismas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END Step 1 -->

                        <!-- Step 2 -->
                        <div class="tab-pane fade fade-right push-30-t push-50" id="simple-progress-step2">
                            <div class="form-group">
                                <div class="col-sm-6 col-sm-offset-1">
                                    <div class="form-material">
                                        <input class="form-control" type="text" id="producto" name="producto" placeholder="Ingrese el nombre o parte del mismo">
                                        <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="">
                                        <input class="form-control" type="hidden" id="producto_imagen" name="producto_imagen" placeholder="" value="">
                                        <label for="producto">Ingrese los productos a transferir</label>
                                    </div>

                                    <!-- Mensaje error stock a transferir -->
                                    <div id="error_producto" class="alert alert-danger hidden" style="margin: 5px;">
                                        <p>Seleccione un producto de la lista</p>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-material">
                                        <input class="form-control" type="text" id="stock_a_transferir" name="stock_a_transferir" placeholder="1,2,4">
                                        <label for="stock_a_transferir">Cantidad a Transferir</label>

                                    </div>
                                    <!-- Mensaje error stock a transferir -->
                                    <div id="error_stock_a_transferir" class="alert alert-danger hidden" style="margin: 5px;">
                                        <p>Seleccione un stock mayor a 0</p>
                                    </div>
                                </div>

                                <div class="col-sm-2">
                                    <div class="form-material">
                                        <button class="btn btn-success" id="add" type="button" >Agregar <i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-10 col-sm-offset-1">
                                <div class="table-responsive" id="tabla_responsive_productos">
                                    <table class="table table-striped table-vcenter">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 120px;"><i class="si si-picture"></i></th>
                                                <th>Producto</th>
                                                <th style="width: 30%;">Cantidad</th>
                                                <th class="text-center" style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla_add">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- END Step 2 -->

                        <!-- Step 3 -->
                        <div class="tab-pane fade fade-right push-30-t push-50" id="simple-progress-step3">
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <input class="form-control" disabled="disabled" type="text" id="sucursal_origen_label" name="simple-progress-city" placeholder="Where do you live?">
                                        <label for="simple-progress-city">Sucursal Origen</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <input class="form-control" disabled="disabled" type="text" id="sucursal_destino_label" name="simple-progress-city" placeholder="Where do you live?">
                                        <label for="simple-progress-city">Sucursal Destino</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-10 col-sm-offset-1" id="contenedor_confirmacion">
                                
                            </div>
                        </div>
                        <!-- END Step 3 -->
                    </div>
                    <!-- END Steps Content -->

                    <!-- Steps Navigation -->
                    <div class="block-content block-content-mini block-content-full border-t">
                        <div class="row">
                            <div class="col-xs-6">
                                <button class="wizard-prev btn btn-warning" type="button"><i class="fa fa-arrow-circle-o-left"></i> Anterior</button>
                            </div>
                            <div class="col-xs-6 text-right">
                                <button class="wizard-next btn btn-success disabled" type="button" style="display: none;">Siguiente <i class="fa fa-arrow-circle-o-right"></i></button>
                                <button class="wizard-finish btn btn-primary" id="btn_guardar" type="button" style="display: inline-block;"><i class="fa fa-check-circle-o"></i> Guardar</button>
                            </div>
                        </div>
                    </div>
                    <!-- END Steps Navigation -->
                </form>
                <!-- END Form -->
            </div>
            <!-- END Simple Progress Wizard -->
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="./assets/js/plugins/bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
<script src="./assets/js/pages/base_forms_wizard.js"></script>
<script src="./assets/js/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="./assets/js/pages/sucursales.js?v=1.01"></script>
<script>
var datos_a_transferir = new Array();
$(document).ready(function(){
    var columna = 0;
    jQuery( "#producto" ).autocomplete({
            source: function(request, response) {
                $.getJSON("search.php?", {term:  $("#producto").val(), sucursal: $("#sucursal_origen").val() }, 
                        response);
            },
            minLength: 2,
            select: function( event, ui ) {
                $("#producto").val(ui.item.value);
                $("#producto_id").val(ui.item.id);
                $("#producto_imagen").val(ui.item.imagen);
                $("#stock_a_transferir").val(ui.item.stock);
            }
        });

    jQuery("#sucursal_origen").change(function(){
        $("#sucursal_origen_label").val($("#sucursal_origen option:selected").text());

        if ( $("#sucursal_origen").val() === "") {
            $("#error_sucursal_origen").fadeIn();
            $("#error_sucursal_origen").removeClass("hidden");
        } else {
            $("#error_sucursal_origen").fadeOut();
        }

        if ( ($("#sucursal_origen").val() === $("#sucursal_destino").val()) && $("#sucursal_destino").val() !== "") {
            $("#error_sucursales_iguales").fadeIn();
            $("#error_sucursales_iguales").removeClass("hidden");
        } else {
            $("#error_sucursales_iguales").fadeOut();
        }
    });

    jQuery("#sucursal_destino").change(function(){
        $("#sucursal_destino_label").val($("#sucursal_destino option:selected").text());

        if ( $("#sucursal_destino").val() === "") {
            $("#error_sucursal_destino").fadeIn();
            $("#error_sucursal_destino").removeClass("hidden");
        } else {
            $("#error_sucursal_destino").fadeOut();
        }

        if ( $("#sucursal_origen").val() === $("#sucursal_destino").val() && $("#sucursal_origen").val() !== "") {
            $("#error_sucursales_iguales").fadeIn();
            $("#error_sucursales_iguales").removeClass("hidden");
        } else {
            $("#error_sucursales_iguales").fadeOut();
        }
    });

    jQuery("#add").click(function(){
        var agregarProducto = true;

        if ($("#producto_id").val() === "" || $("#producto").val() === "") {
            $("#error_producto").fadeIn();
            $("#error_producto").removeClass("hidden");
            agregarProducto = false;
        } else {
            $("#error_producto").fadeOut();
        }

        if ($("#stock_a_transferir").val() <= 0) {
            $("#error_stock_a_transferir").fadeIn();
            $("#error_stock_a_transferir").removeClass("hidden");
            agregarProducto = false;
        } else {
            $("#error_stock_a_transferir").fadeOut();
        }

        if (agregarProducto) {
            $("#tabla_add").html('<tr id="indice_' + columna + '_' + $("#producto_id").val() + '">' + 
                                '<td class="text-center">' +
                                '    <img class="img-avatar img-avatar48" src="' + $("#producto_imagen").val() + '" alt="">' +
                                '</td>' +
                                '<td class="font-w600">' + 
                                    $("#producto").val() + '</td>' +
                                '<td>' + $("#stock_a_transferir").val() + '</td>' +
                                '<td class="text-center">' +
                                '    <div class="btn-group">' +
                                '        <button class="btn btn-xs btn-default" onclick="eliminar(' + columna + ');" type="button" data-toggle="tooltip" title="" data-original-title="Eliminar"><i class="fa fa-times"></i></button>' +
                                '    </div>' +
                                '</td>' +
                            '</tr>' + $("#tabla_add").html());
        
            datos_a_transferir.push(new Array($("#producto_id").val(),$("#stock_a_transferir").val(),columna));
            columna ++;
            $("#producto").val('');
            $("#stock_a_transferir").val('');
            $("#contenedor_confirmacion").html($("#tabla_responsive_productos").html());
        }
    });

    $("#btn_guardar").click(function(){
        if (confirm('Seguro desea realizar la transferencia de mercaderia ?')){
            $.ajax({
                method: "GET",
                url: "transferencias_sucursales_post.php?productos=" + datos_a_transferir.join('||') + 
                        "&origen=" + $("#sucursal_origen").val() + 
                        '&destino='  + $("#sucursal_destino").val(),
                datatype: 'json'
            })
                .done(function (msg) {
                    if (msg == "OK"){
                        $("#ok").show();
                         setTimeout(function(){ $("#ok").hide(); location.reload();}, 3000);
                    }else{
                        $("#error").show();
                        setTimeout(function(){ $("#error").hide(); location.reload(); }, 3000);
                    }
                });
            
        }
    })

});

function eliminar(seleccion)
{
    for (let index = 0; index < datos_a_transferir.length; index++) {
        if (datos_a_transferir[index][2] == seleccion){
            $("#indice_" + seleccion + '_' + datos_a_transferir[index][0]).html('');
            datos_a_transferir.splice(index,1);
        }
    }
    $("#contenedor_confirmacion").html($("#tabla_responsive_productos").html());
}
</script>
<?php require ("footer.php"); ?>