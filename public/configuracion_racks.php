<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
if (getRol() < 2) {
    exit();
}
require 'vendor/autoload.php';

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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Racks</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Configuracion de racks nuevos</h2>
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
    <div class="block-header bg-primary">
                            <h3 class="block-title" >Configuraci&oacute;n</h3>
                        </div>
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/carga_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    
                    <div class="col-xs-10" style="margin-top:3%;">
                        <label>Cantidad de Filas</label>
                        <input type="text" class="form-control" value="3" name="filas" id="filas" />
                        <button class="btn btn-primary" style="margin-top:15px;" type="button" id="btnFilas" >Generar Filas</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div style="display:none;" class="block block-rounded" id="contenedor_deposito">
        <div class="block-content" id="deposito">
        </div>
    </div>
</div>
<div class="modal in" id="Racks" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; padding-right: 16px;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="block block-themed block-transparent remove-margin-b">
                        <div class="block-header bg-primary-dark">
                            <ul class="block-options">
                                <li>
                                    <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                                </li>
                            </ul>
                            <h3 class="block-title" id="configuracion_rack_titulo"></h3>
                        </div>
                        <div class="block-content">
                        <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
                            <div class="alert alert-success alert-dismissable" style="display:none;" id="rack_success" >
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h3 class="font-w300 push-15">Mensaje</h3>
                                <p id="nombre-devuelto"></p>
                            </div>
                        </div>
                            <form class="form-horizontal" action="/carga_post.php" method="post" >
                                <input type="hidden" value="" name="rack_columna" id="rack_columna" />
                                <input type="hidden" value="" name="rack_fila" id="rack_fila" />
                                <div class="form-group">
                                    
                                    <div class="col-xs-4" style="margin-top:3%;">
                                        <label>Cantidad de cajas en profundidad</label>
                                        <input type="text" class="form-control" value="3" name="profundidad" id="profundidad" />
                                    </div>
                                    <div class="col-xs-4" style="margin-top:3%;">
                                        <label>Cantidad de cajas en altura</label>
                                        <input type="text" class="form-control" value="3" name="altura" id="altura" />
                                    </div>
                                    <div class="col-xs-4" style="margin-top:3%;">
                                        <label>Cantidad de cajas en ancho</label>
                                        <input type="text" class="form-control" value="3" name="ancho" id="ancho" />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Guardar Configuraci&oacute;n</button>
                        <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
$(document).ready(function(){
    $("#btnFilas").click(function(){
        var content = "<table style='margin-bottom:10px;'>";
        for(var i=0; i<$("#filas").val(); i++){
            content += '<tr><td id="contenedor_fila_' + i +'"><input class="form-control" title="Ingresa la cantidad de columnas a configurar para la fila ' + (i + 1) + '"  style="margin-top:5px;display:inline;width:50px;" placeholder = "Cantidad de columnas" type="text" id="columna_' + i + '" value="4"/><button  class="btn btn-sm btn-primary" onclick="generarColumnas(' + i + ')" style="margin-left:3px;height:34px;" >Generar</button></td></tr>';
        }
        content += "</table>";

        $('#deposito').html(content);
        $("#contenedor_deposito").show();

    });
    
  //  setTimeout(function(){$("#btnFilas").click();},500);
   // setTimeout(function(){generarColumnas(0);},2000);
});

function generarColumnas(id){
    for(var i = 0;i < $("#columna_" + id).val(); i ++){
        $("#contenedor_fila_" + id).append("<img src='/assets/img/photos/no-image-featured-image.png' onclick='configurarRack(" +  i  + "," + id + ")' title='Rack " + i + "_" + id + "' style='width:61px;margin-left:10px;cursor:pointer'; />");
    }
}

function configurarRack(columna,fila){
    $("#configuracion_rack_titulo").html("Configuracion del Rack " + columna + "_" + fila);
    $('#Racks').modal('show');
}

</script>
<?php require ("footer.php"); ?>