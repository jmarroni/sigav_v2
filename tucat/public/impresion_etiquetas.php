<html>
<?php

if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 2) {
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
$menu["impresion_etiquetas"] = "active";
$menu["reportes"] = "";
require ('header.php'); ?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Impresion Etiquetas</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se cargaron <?php echo $total; ?> productos</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Section -->

    <?php if (isset($_GET["mensaje"])){ ?>
        <div class="block block-rounded" id="add_success" style="background-color: #46c37b !important;color:white;">
            <div class="block-header">
                <div class="col-xs-12 bg-success" id="nombre-devuelto"><?php echo base64_decode($_GET["mensaje"]); ?></div>
            </div>
        </div>
    <?php } ?>

    <div class="block block-rounded">
        <div class="block-content">
            <form class="form-horizontal" action="/carga_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="nombre-producto" id="nombre-producto" value="" placeholder="Ingrese parte del nombre o codigo de barras" />
                        <input type="hidden" class="form-control" name="producto_id" id="producto_id" value="" />
                    </div>
                    <div class="col-xs-2">
                        <label for="bd-qsettings-name">Cantidad</label>
                        <input type="text" class="form-control" name="cantidad" id="cantidad" value="" placeholder="3,5,8" />
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" id="ver_etiquetas" style="width:98%;margin-top:25px;" type="button">
                            <i class="fa fa-check push-5-r"></i>Imprimir
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products
    <img alt="testing" src="/librarys/barcode.php?codetype=Code39&text=28256851&print=true&size=40" />
    <img alt="testing" src="/librarys/barcode.php?codetype=Code25&text=testing&print=true&size=40" /> -->

    <div class="block block-rounded bg-info" style="padding: 10px;">
        <div class="block-header">
            <h3 class="block-title">Codigo QR</h3>
        </div>
        <div style="text-align: center">
            <p id="mensajeqr">Por favor seleccione un producto</p>
            <p id="sitiowebvacio" class="hidden">Este producto no tiene codigo QR</p>
        </div>
        <div style="display: flex; align-items: center; justify-content: center;" id="qrcode"></div>
        <button class="btn btn-sm btn-minw btn-rounded btn-primary hidden" id="botonqr" onclick="printJS({ printable: 'qrcode', type: 'html', documentTitle: 'SIGAV', style: '#qrcode {display: flex; justify-content: center;}' })" style="width:98%;height:30px;margin-top:25px;" type="button">
            Imprimir QR
        </button>
    </div>

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Etiquetas</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <iframe src="etiquetas.php" id="iframe_etiquetas" style="width:90%;height:500px;"></iframe>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- script QR -->
    <script type="text/javascript" src="./assets/js/qr/qrcode.js"></script>

    <!-- PintJS -->
    <script type="text/javascript" src="./assets/js/printJS/print.min.js"></script>
<script>
    jQuery( "#nombre-producto" ).autocomplete({
                source: "search.php",
                minLength: 2,
                select: function( event, ui ) {
                    $("#nombre-producto").val(ui.item.value);
                    $("#producto_id").val(ui.item.id);
                }
            });

    var etiquetas = "";

    $("#ver_etiquetas").click(function(){
        if (etiquetas != "") etiquetas += "-" + $("#producto_id").val() + '@' + $("#cantidad").val();
        else etiquetas = $("#producto_id").val() + '@' + $("#cantidad").val();

        // Agarro el id del producto
        var id_producto = $("#producto_id").val();
        // Borro el qr
        $("#qrcode").empty();

        // Verifico que no este vacio o sea nulo el producto buscado
        if (id_producto != "" && id_producto != null) {
            $.get("get_qr.php", { id_producto: id_producto }, function(data, status) {
                if (status === 'success') {
                    var dato = JSON.parse(data);

                    $("#mensajeqr").addClass("hidden");

                    if (dato.sitio_web !== '' && dato.sitio_web !== null) {
                        $("#sitiowebvacio").addClass("hidden");
                        $("#botonqr").removeClass("hidden");

                        new QRCode("qrcode", {
                            text: dato.sitio_web,
                            width: 128,
                            height: 128,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    } else {
                        $("#sitiowebvacio").removeClass("hidden");
                        $("#botonqr").addClass("hidden");
                    }
                }
            });
        } else {
            $("#botonqr").addClass("hidden");
            $("#sitiowebvacio").addClass("hidden");
            $("#mensajeqr").removeClass("hidden");
        }

        $("#nombre-producto").val('');
        $("#cantidad").val('');
        $("#producto_id").val('');
        $("#iframe_etiquetas").attr("src","etiquetas.php?etiquetas=" + etiquetas);
    });
</script>
<?php require ("footer.php"); ?>