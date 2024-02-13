<?php
$menu["ventas"] = "";
$menu["cargas"] = "";
$menu["reportes"] = "active";
$proveedor_id = 0;
require_once ("conection.php");
require ('header.php');

if (getRol() < 4) {
    exit();
}
$proveedor = "";
if(isset($_POST["reporte_desde"])) $reporte_desde = $_POST["reporte_desde"];
if(isset($_POST["reporte_hasta"])) $reporte_hasta = $_POST["reporte_hasta"];
if(isset($_POST["proveedor"]) && $_POST["proveedor"] != 0){ $proveedor = " and pr.proveedores_id = ".$_POST["proveedor"]; $proveedor_id =$_POST["proveedor"];}
//Productos vendidos hoy
$sql = "SELECT * FROM `ventas` v inner join productos pr ON pr.id= v.productos_id  WHERE v.sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND v.`fecha` > '".date("Y-m-d")."'".$proveedor;

$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas = $resultado->num_rows; 
}else{
    $cantidad_de_ventas = 0;
}


?>

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
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Reporte de ventas</h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se vendieron <?php echo $cantidad_de_ventas; ?> productos hoy <?php echo ($proveedor  != "")?"del proveedor selecionado":""; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Stats -->
            <div class="row text-uppercase">
            
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Producto</td>
                                    <td>Cantidad Vendida</td>
                                    <td>En Stock</td>
                                    <td>Precio</td>
                                    <td>Costo</td>
                                    <td>Ganancia</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            //Productos vendidos hoy por el usuario
                            $sql = "SELECT *
                            FROM sucursales s
                                INNER JOIN stock st
                                ON s.`id`= st.`sucursal_id`
                                    INNER JOIN productos p
                                    ON p.id - st.`productos_id`
                            WHERE  sucursal_id = 4
                            AND p.`nombre` <> ''";
                            $resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
                            if ($resultado->num_rows > 0) {
                                $i = 1;
                                while($row = $resultado->fetch_assoc()) {?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $row["nombre"]; ?></td>
                                        <td><?php echo $row["cantidad"]; ?></td>
                                        <td><?php echo $row["stock"]; ?></td>
                                        <td><?php echo round($row["precio"] * $row["cantidad"],2); ?></td>
                                        <td><?php echo round($row["costo"] * $row["cantidad"],2); ?></td>
                                        <td><?php echo round($row["ganancia"] * $row["cantidad"],2); ?></td>
                                    </tr>
                                <?php $i++;}
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
        </div>
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

<?php require ("footer.php"); ?>
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
    });
</script>