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
$menu["stock_sucursales"] = "active";
$menu["reportes"] = "";
$sucursal_activa = (isset($_GET["sucursal"]) &&  intval($_GET["sucursal"]))?$_GET["sucursal"]:"";
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
            <div class="col-xs-4" > 
                <h3 class="block-title">Seleccione sucursal </h3>
                <select class="form-control" id="sucursal" name="sucursal">
                <?php $sql = "SELECT *
                                FROM sucursales
                                ORDER BY `nombre`";
                            $resultado = $conn->query($sql) or $conn->error;
                            $i = 0;
                                // if ($resultado->num_rows > 0) {
                                    // output data of each row
                                    while($row = $resultado->fetch_assoc()) { 
                                        if ($sucursal_activa == "" && $i == 0) $sucursal_activa = $row["id"]; $i ++;
                                        ?>
                    <option value="<?php echo $row["id"]; ?>" <?php echo ($sucursal_activa == $row["id"])?"selected='selected'":''; ?>><?php echo $row["nombre"]; ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-xs-4" > 
            <h3 class="block-title">&nbsp;</h3>
            <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="filtrar">
                                    <i class="fa fa-check push-5-r"></i>Filtrar
                                </button>
            </div>
        </div>
        <div class="block-content">
<!-- Page Content -->
            <!-- Settings -->
                        <?php
                        $sql = "SELECT 
                        p.*,
                        s.stock as stock_sucursal,
                        s.stock_minimo as stock_minimo_sucursal,
                        ip.imagen_url as imagen
                        FROM productos p 
                        Left JOIN proveedor prov
                        ON prov.id = p.proveedores_id
                        Left JOIN categorias c
                        ON c.id = p.categorias_id
                        LEFT JOIN stock s
                        ON p.id = s.productos_id and s.sucursal_id = ".$sucursal_activa." 
                        LEFT JOIN imagen_producto ip 
                        ON ip.productos_id = p.id
                        WHERE s.stock > 0
                        order by p.nombre ASC ";
                       // echo $sql;exit();
                        $resultado = $conn->query($sql) or die($sql." -- ".$conn->error);
                        $datos = '{"data":"no data"}';
                        $contador_letra = 0;
                        ?>
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Codigo Barras</td>
                                    <td>Producto</td>
                                    <td>En Stock</td>
                                    <td>Precio</td>
                                    <td>Ganancia</td>
                                </tr>
                            </thead>
                            <tbody>
                        <?php
                        if ($resultado->num_rows > 0) {
                            // output data of each row
                            while($row = $resultado->fetch_assoc()) { ?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $row["codigo_barras"]; ?></td>
                                        <td><?php echo $row["nombre"]; ?></td>
                                        <td><?php echo $row["stock_sucursal"]; ?></td>
                                        <td><?php echo round($row["precio_unidad"],2); ?></td>
                                        <td><?php echo round($row["precio_unidad"] - $row["costo"],2); ?></td>
                                    </tr>
                                <?php
                            }
                        }?>
                        </table>
                </div>
            <!-- END Main Content -->
        </div>
  
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" />
<script src="/assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/vfs_fonts.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function(){
        $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        $("#filtrar").click(function(){
            document.location.href='?sucursal=' + $("#sucursal").val();
        });
    });
</script>
<?php require ("footer.php"); ?>