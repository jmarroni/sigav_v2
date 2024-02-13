<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
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
        <div class="bg-black-op">
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
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Productos</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT * FROM productos where codigo_barras = '' order by id DESC ";
                    $resultado = $conn->query($sql);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) {
                            $costo = $row["costo"];
                            $precio = $row["precio_unidad"];
                            $datos = $row;
                            ?>
                            <tr>
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4><?php echo $row["nombre"]; ?></h4>
                                    <p class="remove-margin-b">Costo $  <?php echo $row["costo"]; ?></p>
                                </td>
                                <td>
                                    <p class="remove-margin-b">Precio: <span class="text-gray-dark">$  <?php echo $row["precio_unidad"]; ?></span>
                                    </p>
                                    <p>Quedan en Stock: <span class="text-gray-dark"> <?php echo $row["stock"]; ?></span></p>
                                    <input type="text" id="codigo_barras_add_<?php echo $row["id"]; ?>" name="<?php echo $row["id"]; ?>" ?><label style="display: none;" id="<?php echo $row["id"]; ?>"> - OK<label>
                                </td>
                                <td class="text-center">
                                    <span class="h1 font-w700 text-success">$ <?php echo $row["precio_unidad"] - $row["costo"]; ?></p> </span>
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
<script>
jQuery("document").ready(function() {
    $('input[id^="codigo_barras_add_"]').keypress(function(e) {
        if(e.which == 13) {
            var id_carga = $(this).attr('name');
            $.ajax({
                    method: "POST",
                    url: "cargar_codigo_post.php",
                    datatype: 'json',
                    data: {id: id_carga, codigo: $(this).val()}
                })
                .done(function (msg) {
                    if (msg == "OK"){
                        $("#" + id_carga).show();
                    }
                });
        }  
    });
});
</script>
<?php require ("footer.php"); ?>