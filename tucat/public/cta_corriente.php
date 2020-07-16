<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 5) {
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
$menu["cta_corriente"] = "active";
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
            <h3 class="block-title">INGRESE LA DESCRIPCION DE LA MERCADERIA EN CTA CORRIENTE</h3>
        </div>
        <div class="block-content">
            <form class="form-horizontal" action="/cta_corriente_post.php" method="post" >
                <div class="form-group">
                    <div class="col-xs-6">
                        <label>Producto</label>
                        <input class="form-control" type="text" id="nombre-producto" name="nombre-producto" placeholder="Ingrese parte del nombre" value="">
                        <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="5">
                    </div>
                    <div class="col-xs-2">
                        <label>Costo</label>
                        <input type="text" readonly="readonly" class="form-control" name="costo" id="costo" value="" placeholder="34" />
                    </div>
                    <div class="col-xs-2">
                        <label>Usuario</label>
                        <select class="form-control" id="usuario" name="usuario">
                        <?php
                        $sql = "Select * FROM usuarios ORDER BY nombre DESC";
                        $resultado_stock = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
                        if ($resultado_stock->num_rows > 0) {
                            while ($row_stock = $resultado_stock->fetch_assoc()) { ?>
                                <option value="<?php echo $row_stock["usuario"]; ?>"><?php echo $row_stock["usuario"]; ?></option>    
                            <?php }
                        } ?>
                        </select>
                    </div>
                    <div class="col-xs-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top: 25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Ingresar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Cuentas</h3>
        </div>
        <div class="block-content">
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Usuario</td>
                                    <td>Fecha</td>
                                    <td>Productos</td>
                                    <td>Costo</td>
                                    <td>Status</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                                $sql = "SELECT cc.*,pr.nombre,pr.costo FROM `cuenta_corriente` cc inner join productos pr on cc.productos_id = pr.id";
                            $resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
                            if ($resultado->num_rows > 0) {
                                $i = 1;
                                while($row = $resultado->fetch_assoc()) {?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $row["usuario"]; ?></td>
                                        <td><?php echo $row["fecha"]; ?></td>
                                        <td><?php echo $row["nombre"]; ?></td>
                                        <td><?php echo $row["costo"]; ?></td>
                                        <td><?php echo ($row["estado"] == 0)?"DEBE":"PAGADO"; ?></td>
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
<script>
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
});
</script>
   <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<script src="/assets/js/jquery.dataTables.min.js"></script>
       <!-- END Page Content -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
 
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script type="text/javascript">

    var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){
        $('#tabla_compras').DataTable({
            "language": {
               "url": "/assets/language/Spanish.json"
           }
       });
        $( "#nombre-producto" ).autocomplete({
            source: "search.php",
            minLength: 2,
            select: function( event, ui ) {
                $("#nombre-producto").val(ui.item.value);
                $("#producto_id").val(ui.item.id);
                costo = ui.item.costo;
                if (costo > 0)
                $("#costo").val(costo);
            else
                $("#costo").val("0.00");
            }
        });
    });

</script>
<?php require ("footer.php"); ?>
