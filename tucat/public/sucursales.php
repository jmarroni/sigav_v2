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
$menu["cargas"] = "active";
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

    <?php if (isset($_GET["mensaje"])){ ?>
        <div class="block block-rounded" id="add_success" style="background-color: #46c37b !important;color:white;">
            <div class="block-header">
                <div class="col-xs-12 bg-success" id="nombre-devuelto"><?php echo base64_decode($_GET["mensaje"]); ?></div>
            </div>
        </div>
    <?php } ?>

    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/sucursales_post.php" method="post" enctype="multipart/form-data">
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="" placeholder="Nombre de la Sucursal" />
                    </div>
                    <div class="col-xs-6">
                        <label>Direcci&oacute;n</label>
                        <input type="text" class="form-control" name="direccion" id="direccion" value="" placeholder="Alvear XXX, Viedma, Rio Negro " />
                    </div>
                </div><div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Codigo Postal</label>
                        <input type="text" class="form-control" name="codigo_postal" id="codigo_postal" value="" placeholder="" />
                    </div>
                    <div class="col-xs-6">
                        <label>Provincia</label>
                        <input type="text" class="form-control" name="provincia" id="provincia" value="" placeholder="" />
                    </div>
                </div><div  class="form-group">
                    <div class="col-xs-6">
                        <label>Fecha de alta</label>
                        <input type="date" class="form-control" name="Fecha_alta" id="Fecha_alta" value="0" placeholder="dd/mm/yyyy" />
                    </div>
                    <div class="col-xs-6">
                        <label>Fecha de baja</label>
                        <input type="date" class="form-control" name="Fecha_baja" id="Fecha_baja" value="0" placeholder="dd/mm/yyyy" />
                    </div>
                    
                </div>
                
                <div class="form-group">
                    <div class="col-xs-6">
                        <label>Subir Imagen</label>
                        <input type="file" class="form-control" name="imagen" id="imagen" placeholder="Seleccione una imagen" />
                    </div>
                    <div class="col-xs-6">
                        <label>Punto de Venta</label>
                        <input type="text" class="form-control" name="pto_vta" id="pto_vta" value="" placeholder="1234" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-6 col-xs-offset-3">
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
                <h3 class="block-title">Filtro de Sucursal</h3>
                <input  class="form-control" type="text" placeholder="Ingrese parte del producto" value="" id="filtro" name="filtro" />
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT *
                            FROM sucursales
                            ORDER BY nombre DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) {
                            ?>
                            <tr id="articulo_<?php echo strtolower($row["nombre"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                    <?php if (isset($row["imagen"])){ ?>
                                        <img class="img-responsive" src="<?php echo $row["imagen"]; ?>"
                                             alt="<?php echo $row["nombre"]; ?>">
                                    <?php }else{ ?>
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="<?php echo $row["nombre"]; ?>">
                                    <?php } ?>
                                    </div>
                                </td>
                                <td>
                                    <h4><?php echo $row["nombre"]; ?> </h4>
                                    <p class="remove-margin-b">Direcci&oacute;n: <b><?php echo utf8_encode($row["direccion"]); ?></b></p>
                                    <button onclick="modificar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i>Eliminar
                                    </button>
                                </td>
                                <td>
                                    <p class="remove-margin-b">Fecha de alta: <span class="text-gray-dark"><?php $fecha = explode("-",substr($row["fecha_alta"],0,10)); echo $fecha[2]."-".$fecha[1]."-".$fecha[0]; ?></span></p>
                                    <p class="remove-margin-b">Fecha de baja: <span class="text-gray-dark"> <?php if ($row["fecha_baja"] != ""){$fecha = explode("-",substr($row["fecha_baja"],0,10)); echo $fecha[2]."-".$fecha[1]."-".$fecha[0];}else{"No posee";} ?></span></p>
                                    <p class="remove-margin-b">Usuario: <span class="text-gray-dark"> <?php echo $row["usuario"]; ?></span></p>
                                    <p>Punto de Venta: <span class="text-gray-dark"> <?php echo $row["pto_vta"]; ?></span></p>
                                </td>
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No hay productos</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="/assets/js/pages/sucursales.js?v=1.03"></script>
<?php require ("footer.php"); ?>