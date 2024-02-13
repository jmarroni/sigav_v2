<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
if (getRol() < 4) {
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
$menu["clientes"] = "active";
$menu["reportes"] = "";
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
    <div id="formulario" class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op" id="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Relacion Servicios - Clientes</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Bajas/Modificaciones de relaciones</h2>
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
        <div class="block-header bg-primary">
            <h3 class="block-title">Agregar Relaci&oacute;n Cliente - Servicios</h3>
        </div>
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/relacion_cliente_servicios.php" method="post" >
                <input type="hidden" value="" name="cliente_id" id="cliente_id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Ingrese el cliente</label>
                        <input type="text" autocomplete="false" title="Ingresar parte del nombre y seleccionarlo" class="form-control" name="razon_social" id="razon_social" value="" placeholder="Juan Garay" />
                        <p>
                            
                        </p>
                    </div>
                    <div id="servicios" class="col-xs-6">
                        
                    </div>
                </div><div class="form-group">
                    <div class="col-xs-6 col-xs-offset-3">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Actualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header bg-primary">
            <h3 class="block-title">Servicios</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                            $sql = "SELECT *                            
                            FROM clientes
                            ORDER BY razon_social";
                                $resultado = $conn->query($sql) or die($conn->error);
                                $datos = '{"data":"no data"}';
                                if ($resultado->num_rows > 0) {
                                // output data of each row
                                    $arrDevolucion = array();
                                    while ($row = $resultado->fetch_assoc()) { ?>
                            <tr id="articulo_<?php echo strtolower($row["id"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4>Servicio: <?php echo $row["razon_social"]; ?> </h4>
                                    <p class="remove-margin-b">Habilitado:<?php echo ($row["deshabilitado"] == "0")?"Habilitado":"Deshabilitado"; ?></p>
                                     <input type="hidden" value="<?php echo $seccion_id ?>" name="seccion_<?php echo $row["rol_id"]; ?>" id="seccion_<?php echo $row["rol_id"]; ?>" />
                                </td> 
                                <td>
                                    <p class="remove-margin-b"><b>Servicios habilitados:</b></p>
                                    <p class="remove-margin-b">
                                   <?php $sql = "SELECT  
                                                    ss.*,rsc.id as activado
                                                    FROM servicios ss 
                                                        INNER JOIN relacion_servicio_cliente rsc ON
                                                        ss.id = rsc.servicios_id and rsc.cliente_id = {$row["id"]}                                   
                                                    ORDER BY nombre DESC ";
                                            $resultado_servicios = $conn->query($sql) or die($conn->error);
                                            $servicios = "No posee";
                                            if ($resultado_servicios->num_rows > 0) {
                                                // output data of each row
                                                $arrDevolucion = array();
                                                $servicios = "";
                                                while ($row_servicios = $resultado_servicios->fetch_assoc()) { 
                                                    $servicios .= ($servicios != "")?",":"";
                                                    $servicios .= $row_servicios["nombre"];
                                                 }
                                            } echo "<i>".$servicios."</i>"; ?>
                                        
                                    </p>
                                </td>                                
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No hay Roles</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    $(document).ready(function(){
        jQuery( "#razon_social" ).autocomplete({
            source: "get_cliente.php",
            minLength: 2,
            select: function( event, ui ) {
                $("#razon_social").val(ui.item.value);
                $("#cliente_id").val(ui.item.id);
                getServicios(ui.item.id);
            }
        });
    });

    function getServicios(cliente){
        $.post("get_servicios.php?cliente=" + cliente, function(data, status){  
            var jsonData = JSON.parse(data);
            $("#servicios").html('');
            for(var i = 0; i < jsonData.length; i ++){
                $servicio = '<div class="col-xs-12">' +
                                            '<label class="css-input switch switch-success">' +
                                                '<input type="checkbox" onchange="habilitarServicio(' + jsonData[i].id + ')" ';
                if (jsonData[i].activado) $servicio += ' checked=""';
                $servicio += ' ><span></span> ' + jsonData[i].nombre + '</label></div>';
                $("#servicios").append($servicio);
            }
        });
    }

    function habilitarServicio(identificador){
        console.log(identificador);
        console.log($("#cliente_id").val());
        $.post("relacion_servicio_cliente_post.php?cliente=" + $("#cliente_id").val() + "&servicio=" + identificador, function(data, status){  
           console.log(data);
        });
    }
</script>
<?php require ("footer.php"); ?>