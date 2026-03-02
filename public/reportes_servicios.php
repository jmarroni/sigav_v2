<?php
if ($_COOKIE["kiosco"] != "jmarroni"){    exit();}
$menu["ventas"] = "";
$menu["cargas"] = "";
$menu["reportes"] = "active";
$proveedor_id = 0;
require_once ("conection.php");
require ('header.php');

if (getRol() < 2) {
    exit();
}
$proveedor = "";
//Obtengo de lunes a lunes
$fecha_actual = date("Y-m-d");
$semana_pasada = strtotime ( '-7 day' , strtotime ( $fecha_actual ) ) ;
$semana_pasada = date ( 'Y-m-d' , $semana_pasada );
$reporte_desde = date("Y-m-d", strtotime('monday this week', strtotime($semana_pasada)));


$reporte_hasta = date("Y-m-d", strtotime('monday this week', strtotime($fecha_actual)));


if(isset($_POST["reporte_desde"])) $reporte_desde = $_POST["reporte_desde"];
if(isset($_POST["reporte_hasta"])) $reporte_hasta = $_POST["reporte_hasta"];
if(isset($_POST["proveedor"]) && $_POST["proveedor"] != 0){ $proveedor = " and pr.proveedores_id = ".$_POST["proveedor"]; $proveedor_id =$_POST["proveedor"];}


//Productos vendidos hoy
$total_ganancia = 0;
$total_costo = 0;
$total_precio = 0;
$total_pagos = 0;

$sql = "SELECT *"
        . "FROM proveedores WHERE nombre <> 'Alquiler' AND nombre <> 'Chicos'";   
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
    $pagos_ganancias = array();
    $indice = 0;
    while($row = $resultado->fetch_assoc()) {
        //Busco las ganancias
        $sql_cobro = "SELECT
        p.`id` AS id_proveedor,
        ROUND(SUM(pr.`costo` * cantidad),2) AS costo_producto,
        ROUND(SUM(precio * cantidad),2) AS precio,
        ROUND(SUM((precio - pr.`costo`) * cantidad),2) AS ganancia
        FROM `ventas` v 
        INNER JOIN productos pr 
        ON pr.id= v.productos_id  
        INNER JOIN proveedores p
        ON p.`id` = pr.`proveedores_id`
        WHERE v.sucursal_id = ".getSucursal($_COOKIE["sucursal"])." AND v.`fecha` BETWEEN '$reporte_desde' AND '$reporte_hasta' AND p.id ='{$row["id"]}' GROUP BY p.`id`";
       //s echo $sql_cobro;exit();
        $resultado_query_cobro = $conn->query($sql_cobro);
        if ($row_pago_cobro = $resultado_query_cobro->fetch_assoc()) {
            $pagos_ganancias[$indice]["costo"] = $row_pago_cobro["costo_producto"]; 
            $pagos_ganancias[$indice]["precio"] = $row_pago_cobro["precio"];
            $pagos_ganancias[$indice]["ganancia"] = $row_pago_cobro["ganancia"];
            $total_costo += $row_pago_cobro["costo_producto"];
            $total_precio += $row_pago_cobro["precio"];
            $total_ganancia += $row_pago_cobro["ganancia"];
        }else{
            $pagos_ganancias[$indice]["costo"] = "0.00"; 
            $pagos_ganancias[$indice]["precio"] = "0.00";
            $pagos_ganancias[$indice]["ganancia"] = "0.00"; 
        }
            // Busco por cada proveedor lo pagado la semana pasada
        $reporte_hasta_siguiente_semana = strtotime ( '+8 day' , strtotime ( $reporte_hasta ) ) ;
        $reporte_hasta_siguiente_semana = date ( 'Y-m-d' , $reporte_hasta_siguiente_semana );
        $sql_query = "SELECT
                p.`id` AS id_proveedor,
                ROUND(SUM(pap.monto),2) AS monto_pagado
                FROM `pagos_a_proveedores` pap
                INNER JOIN `proveedores` p
                ON p.`id` = pap.proveedores_id
                WHERE p.id = '{$row["id"]}' AND `fecha` BETWEEN '$reporte_hasta' AND '$reporte_hasta_siguiente_semana' GROUP BY p.`id`";
        $resultado_query = $conn->query($sql_query);
        
        if ($row_pago = $resultado_query->fetch_assoc()) {
            $pagos_ganancias[$indice]["monto_pagado"] = $row_pago["monto_pagado"]; 
            $total_pagos += $row_pago["monto_pagado"];
        }else{
            $pagos_ganancias[$indice]["monto_pagado"] = "0.00";
        }
        $pagos_ganancias[$indice]["id"] = $row["id"]; 
        $pagos_ganancias[$indice]["proveedor"] = $row["nombre"]; 
        $indice ++;
    }
    // echo "<pre>";
    // print_r($pagos_ganancias);exit();
}


?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['bar']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Plan','Cantidad'],
      <?php  
      $sql_grafico = "SELECT
                        s.nombre AS plan,
                        COUNT(s.id) AS cantidad
                        FROM clientes c
                            INNER JOIN relacion_servicio_cliente rsc
                            ON c.`id` = rsc.`cliente_id`
                                INNER JOIN servicios s
                                ON s.`id` = rsc.servicios_id
                        GROUP BY s.nombre
                        ORDER BY s.nombre";
      
      $resultado_query = $conn->query($sql_grafico);
        
      while ($value = $resultado_query->fetch_assoc()) {
          echo "['".$value["plan"]."', ".$value["cantidad"]."],";
      }?>
    ]);

    var options = {
      chart: {
        title: 'Cuadro comparativo',
        subtitle: 'Cantidad de clientes por Servicio',
      }
    };

    var chart = new google.charts.Bar(document.getElementById('columnchart_material'));

    chart.draw(data, google.charts.Bar.convertOptions(options));
  }
</script>
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
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Reporte de Clientes por Servicios</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Stats -->
            <div class="row text-uppercase">
                <div class="col-sm-12">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div id="columnchart_material" style="width: 100%; height: 500px;"></div>
                        </div>
                    </div>
                </div>
                </div>
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Plan</td>
                                    <td>Cliente</td>
                                    <td>cuit</td>
                                    <td>Domicilio</td>
                                    <td>Localidad, Provincia</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $i = 0;
                            $sql_grafico = "SELECT c.*,s.*,s.nombre as plan
                                            FROM clientes c
                                                INNER JOIN relacion_servicio_cliente rsc
                                                ON c.`id` = rsc.`cliente_id`
                                                    INNER JOIN servicios s
                                                    ON s.`id` = rsc.servicios_id
                                            ORDER BY s.nombre";
                            
                            $resultado_query = $conn->query($sql_grafico);
                                
                            while ($value = $resultado_query->fetch_assoc()) { ?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $value["plan"]; ?></td>
                                        <td><?php echo $value["razon_social"]; ?></td>
                                        <td><?php echo $value["cuit"]; ?></td>
                                        <td><?php echo $value["domicilio_legal"]; ?></td>
                                        <td><?php echo $value["localidad"].", ".$value["provincia"]; ?></td>
                                    </tr>

                            <?php
                            $i ++;
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
            pageLength: 50,
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
</script>