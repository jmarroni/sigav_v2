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
    <?php 
        $datos["id"]            = "";
        $datos["nombre"]        = "";
        $datos["razon_social"]  = "";
        $datos["direccion"]     = "";
        $datos["mail"]          = "";
        $datos["telefono"]      = "";
        $datos["provincia"]     = "";
        $datos["localidad"]     = "";
        $datos["logo"]          = "";
        $sql = "SELECT * FROM `perfil`";
        $resultado = $conn->query($sql);
        if ($resultado->num_rows > 0) {
            // output data of each row
            if ($row = $resultado->fetch_assoc()) {
                $datos["id"]            = $row["id"];
                $datos["nombre"]        = $row["nombre"];
                $datos["razon_social"]  = $row["razon_social"];
                $datos["direccion"]     = $row["direccion"];
                $datos["mail"]          = $row["mail"];
                $datos["telefono"]      = $row["telefono"];
                $datos["provincia"]     = $row["provincia"];
                $datos["localidad"]     = $row["localidad"];
                $datos["logo"]          = $row["logo"];
            }
        }
    ?>
    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/perfil_post.php" method="post" enctype="multipart/form-data">
                <input type="hidden" value="<?php echo $datos["id"]; ?>" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $datos["nombre"]; ?>" placeholder="Nombre Fantasia" />
                    </div>
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Razon Social</label>
                        <input type="text" class="form-control" name="razon_social" id="razon_social" value="<?php echo $datos["razon_social"]; ?>" placeholder="Nombre a colocar en la facturaci&oacute;n" />
                    </div>
                    <div class="col-xs-6">
                        <label>Direcci&oacute;n Fiscal</label>
                        <input type="text" class="form-control" name="direccion" id="direccion" value="<?php echo $datos["direccion"]; ?>" placeholder="Alvear XXX, Viedma, Rio Negro " />
                    </div>
                    <div class="col-xs-6">
                        <label>Mail</label>
                        <input type="mail" class="form-control" name="mail" id="mail" value="<?php echo $datos["mail"]; ?>" placeholder="mail@mail.com.ar" />
                    </div>
                    <div class="col-xs-6">
                        <label>Telefono</label>
                        <input type="phone" class="form-control" name="telefono" id="telefono" value="<?php echo $datos["telefono"]; ?>" placeholder="2920535345" />
                    </div>
                    <div class="col-xs-6">
                        <label>Provincia</label>
                        <input type="text" class="form-control" name="provincia" id="provincia" value="<?php echo $datos["provincia"]; ?>" placeholder="Rio Negro" />
                    </div>
                    <div class="col-xs-6">
                        <label>Localidad</label>
                        <input type="text" class="form-control" name="localidad" id="localidad" value="<?php echo $datos["localidad"]; ?>" placeholder="Localidad" />
                    </div>
                    <div class="col-xs-6">
                        <img src="<?php echo $datos["logo"]; ?>" style="width:50px;display:block" />
                        <label>Subir Logo</label>
                        <input type="file" class="form-control" name="imagen" id="imagen" value="<?php echo $datos["logo"]; ?>" placeholder="Seleccione una imagen" />
                    </div>
                    
                    <div class="col-xs-6 col-xs-offset-3">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Configurar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="/assets/js/pages/sucursales.js?v=1.01"></script>
<?php require ("footer.php"); ?>