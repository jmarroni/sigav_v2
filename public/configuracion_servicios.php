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
$menu["usuario"] = "active";
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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Servicios</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Bajas/Modificaciones de servicios</h2>
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
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/servicios_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Servicio</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="" placeholder="Nombre de fantasia del servicio" />
                    </div>
                    <div class="col-xs-3 col-xs-offest-1" style="margin-top:2%;">
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="habilitado" value="1" name="habilitado" ><span></span> Habilitado
                        </label>
                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Periodo en dias para facturar</label>
                        <input type="text" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Colocar cero para facturar cuando se adhiere" class="form-control" name="periodo" id="periodo" value="" placeholder="terminos en dias a ejecutar la facturacion" />
                    </div>
                </div><div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Precio de venta</label>
                        <input type="text" class="form-control" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Costo a facturar por cada periodo" name="costo" id="costo" value="" placeholder="costo del servicio, ej. 34.98" />
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
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
            <div class="col-xs-8" > 
                <h3 class="block-title">Servicios</h3>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT  
                            *
                            FROM servicios                                    
                            ORDER BY nombre DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { 
                            $nombre = $row["nombre"];
                            ?>
                            <tr id="articulo_<?php echo strtolower($row["id"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4>Servicio: <?php echo $nombre; ?> </h4>
                                    <p class="remove-margin-b">Habilitado:<?php echo ($row["habilitado"] == 1)?"Habilitado":"Deshabilitado"; ?></p>
                                     <input type="hidden" value="<?php echo $seccion_id ?>" name="seccion_<?php echo $row["rol_id"]; ?>" id="seccion_<?php echo $row["rol_id"]; ?>" />
                                    <button onclick="modificar('<?php echo $row["id"]; ?>','<?php echo $nombre; ?>','<?php echo $row["habilitado"]; ?>','<?php echo $row["periodo"]; ?>','<?php echo $row["costo"]; ?>');"  class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["rol_id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i>Eliminar
                                    </button>
                                </td> 
                                <td style="width:30%">
                                    <p class="remove-margin-b">Periodo:&nbsp;<?php echo ($row["periodo"] > 0)?$row["periodo"]." d&iacute;as":"Al momento de asociarlo"; ?></p>
                                    <p class="remove-margin-b">Precio de venta:&nbsp;$<?php echo number_format($row["costo"],"2",",","."); ?></p>
                                </td>                                
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No existen Servicios</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
function modificar(identificador,titulo,habilitado,periodo,costo){
    $("#nombre").val(titulo);
    $("#id").val(identificador);
    if (habilitado == 1){$("#habilitado").prop("checked",true);}else{$("#habilitado").prop("checked",false);}
    $("#periodo").val(periodo);
    $("#costo").val(costo);
    
    document.location.href ="#formulario";
}
</script>
<?php require ("footer.php"); ?>