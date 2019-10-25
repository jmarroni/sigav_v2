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
$menu["cargas"] = "";
$menu["reportes"] = "";
$menu["actualizaciones"] = "active";
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

    <div class="block block-rounded">
        <div class="block-content">
            <form class="form-horizontal" action="/categoria_post.php" method="post" >
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="producto" value="" placeholder="Artesania Lana" />
                    </div>
                    <div class="col-xs-4">
                        <label>Abreviatura</label>
                        <input type="text" class="form-control" name="costo" value="" placeholder="ALAN" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;margin-top: 7%;" type="submit">
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
            <h3 class="block-title">Categorizaci&oacute;n</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody>
                    <?php
                    $sql = "SELECT * FROM `categorias` ORDER BY nombre";
                    $resultado = $conn->query($sql);
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { ?>
                            <tr>
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png" alt="">
                                        </div>
                                    </td>
                                <td>
                                    <h4><?php echo $row["nombre"] ?>(
                                        <?php if ($row["habilitada"] == 0){?>
                                            <a href="/categoria_post.php?id=<?php echo $row["id"] ?>&habilitar=1">Deshabilitar</a>)</h4>
                                        <?php }else{ ?>
                                            <a href="/categoria_post.php?id=<?php echo $row["id"] ?>&habilitar=0">Habilitar</a>)</h4>
                                        <?php } ?>
                                     <p class="remove-margin-b">Ingresado por <?php echo $row["usuario"]; ?></p>
                                    </td>
                                <td>
                                    <p class="remove-margin-b">Abreviatura: <span class="text-gray-dark"><?php echo  $row["abreviatura"] ?></span></p>
                                    <p>Productos relacionados: <span class="text-gray-dark"><?php echo 0; ?></span></p>
                                </td>
                                <td class="text-center">
                                    <span class="h1 font-w700 text-success" <?php echo ($row["habilitada"] == 0)?"":"style='color:red;'"; ?>>Habilitado <?php echo ($row["habilitada"] == 0)?"Si":"No"; ?></span>
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
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
});
</script>
<?php require ("footer.php"); ?>