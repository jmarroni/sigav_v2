<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
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
$menu["caja"] = "active";
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

    <div class="block block-rounded">
    <div class="block-header">
            <h3 class="block-title">INGRESE CANTIDAD DE BILLETES</h3>
        </div>
        <div class="block-content">
            <form class="form-horizontal" action="/caja_post.php" method="post" >
                <div class="form-group">
                    <div class="col-xs-1">
                        <label>100</label>
                        <input type="text" class="form-control" name="cien" value="" placeholder="12" />
                    </div>
                    <div class="col-xs-1">
                        <label>50</label>
                        <input type="text" class="form-control" name="cincuenta" value="" placeholder="11" />
                    </div>
                    <div class="col-xs-1">
                        <label>20</label>
                        <input type="text" class="form-control" name="veinte" value="" placeholder="5" />
                    </div>
                    <div class="col-xs-1">
                        <label>10</label>
                        <input type="text" class="form-control" name="diez" value="" placeholder="12" />
                    </div>
                    <div class="col-xs-1">
                        <label>5</label>
                        <input type="text" class="form-control" name="cinco" value="" placeholder="5" />
                    </div>
                    <div class="col-xs-2">
                        <label>Operaci&oacute;n</label>
                        <select class="form-control" name="operacion">
                            <option value="0">Cierre</option>
                            <option value="1">Apertura</option>
                            <option value="2">Extracci&oacute;n</option>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <label>Observaci&oacute;n</label>
                        <input type="text" class="form-control" name="observacion" value="" placeholder="nota" />
                    </div>
                    <div class="col-xs-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top: 25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Cierre/Apertura
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Productos</h3>
        </div>
        <div class="block-content">
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Id</td>
                                    <td>Sucursal</td>
                                    <td>Usuario</td>
                                    <td>Cien</td>
                                    <td>Cincuenta</td>
                                    <td>Veinte</td>
                                    <td>Diez</td>
                                    <td>Cinco</td>
                                    <td>Operacion</td>
                                    <td>Fecha</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                                $sql = "SELECT c.*,sc.nombre as sucursal_nombre FROM `caja` c inner join sucursales sc On sc.id = c.sucursal_id ";
                                if ($_COOKIE["kiosco"] != "jmarroni") $sql .= " WHERE fecha > '".date("Y-m-d 00:00:01")."'";
                            $resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
                            if ($resultado->num_rows > 0) {
                                $i = 1;
                                while($row = $resultado->fetch_assoc()) {?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $row["id"]; ?></td>
                                        <td><?php echo $row["sucursal_nombre"]; ?></td>
                                        <td><?php echo $row["usuario"]; ?></td>
                                        <td><?php echo $row["cien"]; ?></td>
                                        <td><?php echo $row["cincuenta"]; ?></td>
                                        <td><?php echo $row["veinte"]; ?></td>
                                        <td><?php echo $row["diez"]; ?></td>
                                        <td><?php echo $row["cinco"]; ?></td>
                                        <td title="<?php echo $row["observacion"]; ?>"><?php
                                        switch ($row["operacion"]) {
                                            case '0':
                                                echo "Cierre";
                                                break;
                                            case '1':
                                                echo "Apertura";
                                                break;
                                            case '2':
                                                echo "Extracci&oacute;n";
                                                break;
                                        }; ?></td>
                                        <td><?php echo $row["fecha"]; ?></td>
                                    </tr>
                                <?php $i++;}
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
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
       <!-- END Page Content -->
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/vfs_fonts.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        <?php if ($_COOKIE["kiosco"] == "jmarroni"){ ?>
        $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
        <?php }else{?>
            $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            }
        });
        <?php } ?>
    });
</script>
<?php require ("footer.php"); ?>