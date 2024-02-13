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
$menu["cargas"] = "";
$menu["reportes"] = "";
$menu["proveedores"] = "active";
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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Pago a Proveedores</h1>
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
            <form class="form-horizontal" action="/proveedores_post.php" method="post" >
                <div class="form-group">
                    <div class="col-xs-2">
                        <label>Seleccione el proveedor</label>
                        <select class="form-control" name="proveedor">
                            <?php 
                            $sql = "SELECT * FROM `proveedor`";
                            $resultado = $conn->query($sql);
                            if ($resultado->num_rows > 0) {
                                // output data of each row
                                while($row = $resultado->fetch_assoc()) {
                            ?>
                                <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                            <?php }} ?>
                        </select>
                    </div>
                    <div class="col-xs-2">
                        <label>Monto</label>
                        <input type="number" step="0.01" class="form-control" name="monto" value="" placeholder="Costo por unidad" />
                    </div>
                    <div class="col-xs-3">
                        <label>Operaci&oacute;n</label>
                        <select class="form-control" id="operacion" name="operacion">
                                    <option value="1">Ingreso y Pago de Mercaderia</option>
                                    <option value="2">Ingreso de Mercaderia (Cta. Cte.)</option>
                                    <option value="3">Deposito, Pago de mercaderia</option>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <label>Detalle</label>
                        <input type="text" class="form-control" name="detalle" value="" placeholder="Detalle del deposito, nro cuenta,..." />
                    </div>
                    <div class="col-xs-2">
                        <label>Fecha</label>
                        <input type="date" class="form-control" name="fecha" value="<?php echo  date("Y-m-d") ?>" />
                    </div>
                    <div class="col-xs-6 col-xs-offset-3" style="padding-top: 25px;">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:100%" type="submit">
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
            <h3 class="block-title">Pagos Realizados</h3>
            <div class="col-xs-6" style="padding-left: 0px;padding-top: 20px;">
                <label>Filtro  por proveedor</label>
                <select class="form-control" name="proveedor_filtro" id="proveedor_filtro" >
                    <option value="0">Seleccione un proveedor</option>
                    <?php 
                    $sql = "SELECT * FROM `proveedor`";
                    $resultado = $conn->query($sql);
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) {
                    ?>
                        <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                    <?php }} ?>
                </select>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table id="tabla_pago_a_proveedores">
                    <thead>
                        <tr>
                            <td>Id</td>
                            <td>nombre</td>
                            <td>fecha</td>
                            <td>Debe</td>
                            <td>Haber</td>
                            <td>Detalle</td>
                            <td>usuario</td>
                        </tr>
                    </thead>
                    <tbody id="tablaPagoProveedores">
                    <?php
                    $proveedor_filtro = "";
                    if (isset($_GET["proveedor_filtro"]) && intval($_GET["proveedor_filtro"])) $proveedor_filtro = "WHERE p.id = ".$_GET["proveedor_filtro"];
					$sql = "SELECT p.nombre as nombre,
					       	pap.monto as monto,
						pap.fecha as fecha,
						pap.usuario as usuario,
						pap.id as id,
                        pap.operacion,
                        pap.detalle
						 FROM pagos_a_proveedores pap inner join proveedor p on pap.proveedores_id = p.id $proveedor_filtro order by pap.id DESC ";
                    $resultado = $conn->query($sql);
                    if ($resultado->num_rows > 0) {
                        $i = 1;?>
                        
                        <?php // output data of each row
                        while($row = $resultado->fetch_assoc()) { ?>
                            <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                <td><?php echo $row["id"]; ?></td>
                                <td><?php echo $row["nombre"]; ?></td>
                                <td><?php echo $row["fecha"]; ?></td>
                                <td><?php echo ($row["operacion"] == 1 || $row["operacion"] == 2)?"$ ".$row["monto"]:"" ?></td>                                
                                <td><?php echo ($row["operacion"] == 1 || $row["operacion"] == 3)?"$ ".$row["monto"]:"" ?></td>
                                <td><?php echo $row["detalle"]; ?></td>
                                <td><?php echo $row["usuario"]; ?></td>
                            </tr>

                            <?php
                        $i++; }
                    } else {
                        echo "<tr><td colspan='5'>No hay productos</td></tr>";
                    }
                    ?>
                    <tfoot align="right">
                        <tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>
                    </tfoot>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="//cdn.datatables.net/plug-ins/1.10.19/api/sum().js"></script>
<?php require ("footer.php"); ?>
<script type="text/javascript">
    $(document).ready(function(){

        $("#proveedor_filtro").change(function(){
            if ($(this).val() != 0){
                document.location.href = "?proveedor_filtro=" + $(this).val();
            }
        })

        $('#tabla_pago_a_proveedores').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            "footerCallback": function ( row, data, start, end, display ) {
                        var api = this.api(), data;
            
                        // Remove the formatting to get integer data for summation
                        var intVal = function ( i ) {
                            console.log(typeof i);
                            console.log(i);
                            return typeof i === 'string' ?
                                i.replace(/[\$,]/g, '')*1 :
                                typeof i === 'number' ?
                                    i : 0;
                        };
            
                        // Total over all pages
                        totalHaber = api
                            .column( 4 )
                            .data()
                            .reduce( function (a, b) {
                                var error_number = 0;
                                try {
                                    error_number = intVal(a) + intVal(b);
                                } catch (error) { console.log(error);}
                                return error_number;
                            }, 0 );
                        
                        totalDebe = api
                            .column( 3 )
                            .data()
                            .reduce( function (a, b) {
                                var error_number = 0;
                                try {
                                    error_number = intVal(a) + intVal(b);
                                } catch (error) { console.log(error);}
                                return error_number;
                            }, 0 );
            
                        // Total over this page 
                        pageTotal = api
                            .column( 4, { page: 'current'} )
                            .data()
                            .reduce( function (a, b) {
                                return intVal(a) + intVal(b);
                            }, 0 );
            
                        // Update footer
                        $( api.column( 0 ).footer() ).html('Totales&nbsp;:');
                        $( api.column( 4 ).footer() ).html('$&nbsp;'+ totalHaber);
                        $( api.column( 3 ).footer() ).html('$&nbsp;'+ totalDebe);
                        var subtotal = parseFloat(totalHaber) - parseFloat(totalDebe)
                        $( api.column( 5 ).footer() ).html('Diferencia&nbsp;$&nbsp;' + subtotal);
                        
                    }
        });
    });
</script>
