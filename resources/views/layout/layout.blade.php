<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once (public_path()."/conection.php");
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
$menu["cargas"] = "active";
$menu["reportes"] = "";
require (public_path().'/header.php'); 
?>
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
    .header-navbar-fixed #main-container {
        padding-top: 50px !important;
    }
</style>
<link rel="stylesheet" href="/assets/css/core/bootstrapv3.3.7.css">
<link rel="stylesheet" href="/assets/css/core/sweetalert.minv1.1.3.css">
<link rel="stylesheet" type="text/css" href="/assets/css/core/slickv1.8.1.css"/>
<!-- Page Content -->
<div class="content content-boxed">
    <?php if (isset($mensaje)){ ?>
        <div class="block block-rounded" id="add_success" style="background-color: #46c37b !important;color:white;">
            <div class="block-header">
                <div class="col-xs-12 bg-success" style="background-color:#46c37b;" id="nombre-devuelto"><?php echo base64_decode($mensaje); ?></div>
            </div>
        </div>
    <?php } ?>
    <!-- END Products -->
    <!-- END Main Container --> 
    @section('body')
    @show
    <!-- Footer -->
</div>
@include('sweet::alert')
@section('scripts')
<script src="/assets/js/core/jqueryv1.12.4.js"></script>
<script src="/assets/js/pages/carga.js?v=1.08"></script>

@show
<?php require (public_path()."/footer.php"); ?>