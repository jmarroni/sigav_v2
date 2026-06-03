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
$pagos_ganancias = array();


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
      ['Proveedor','Precio' ,'Costo', 'Pago','Ganancia'],
      <?php  foreach ($pagos_ganancias as $key => $value) {
          if ($value["costo"] != "0.00" || $value["monto_pagado"] != "0.00")
          echo "['".$value["proveedor"]."', ".$value["precio"].", ".$value["costo"].", ".$value["monto_pagado"].", ".($value["costo"] + $value["ganancia"] - $value["monto_pagado"])."],";
      }?>
    ]);

    var options = {
      chart: {
        title: 'Cuadro comparativo, ventas ($ <?php echo $total_precio ?>)/pago ($ <?php echo $total_pagos ?>)/ganancia ($ <?php echo $total_ganancia ?>)/costo ($ <?php echo $total_costo ?>)',
        subtitle: 'Entre las fechas: <?php echo $reporte_desde; ?> a <?php echo $reporte_hasta; ?> para ganancias,\n\r Y entre <?php echo $reporte_hasta; ?> a <?php echo $reporte_hasta_siguiente_semana; ?> para los pagos ',
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
        <div class="content content-boxed sigav-app">

            <!-- Hero -->
            <div class="sg-hero">
                <p class="sg-hero__eyebrow">Reportes</p>
                <h1>Reporte de Pagos a Proveedores</h1>
            </div>

            <!-- Filtros -->
            <section class="sg-card">
                <header class="sg-card__head">
                    <div class="sg-card__title"><span class="sg-dot"></span><h3>Filtros</h3></div>
                </header>
                <div class="sg-card__body">
                    <form class="sg-filters" action="reportes.php" method="POST">
                        <div class="sg-field">
                            <label for="reporte_desde">Desde</label>
                            <input type="date" class="form-control" name="reporte_desde" id="reporte_desde" value="<?php echo $reporte_desde; ?>">
                        </div>
                        <div class="sg-field">
                            <label for="reporte_hasta">Hasta</label>
                            <input type="date" class="form-control" name="reporte_hasta" id="reporte_hasta" value="<?php echo $reporte_hasta; ?>">
                        </div>
                        <div class="sg-filter-actions">
                            <button class="sg-btn sg-btn--primary" type="submit"><i class="fa fa-filter"></i> Filtrar</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Grafico -->
            <section class="sg-card">
                <header class="sg-card__head">
                    <div class="sg-card__title"><span class="sg-dot"></span><h3>Cuadro comparativo ventas / pago / ganancia / costo</h3></div>
                </header>
                <div class="sg-card__body">
                    <div class="sg-chart">
                        <div id="columnchart_material" style="width: 100%; height: 500px;"></div>
                    </div>
                </div>
            </section>

            <!-- Detalle -->
            <section class="sg-card">
                <header class="sg-card__head">
                    <div class="sg-card__title"><span class="sg-dot"></span><h3>Detalle</h3></div>
                </header>
                <div class="sg-card__body sg-table-wrap">
                        <table id="tabla_compras" class="sg-table">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th class="sg-num">Precio</th>
                                    <th class="sg-num">Costo</th>
                                    <th class="sg-num">Ganancia</th>
                                    <th class="sg-num">Pagos</th>
                                    <th class="sg-num">Ganancia Final</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $i = 0;
                            foreach ($pagos_ganancias as $key => $value) {?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $value["proveedor"]; ?></td>
                                        <td><?php echo round($value["precio"],2); ?></td>
                                        <td><?php echo round($value["costo"],2); ?></td>
                                        <td><?php echo round($value["ganancia"],2); ?></td>
                                        <td><?php echo $value["monto_pagado"]; ?></td>
                                        
                                        <td><?php echo round(($value["costo"] + $value["ganancia"] - $value["monto_pagado"]),2); ?></td>
                                    </tr>

                            <?php
                            $i ++;
                            }
                            ?>
                            </tbody>
                        </table>
                </div>
            </section>

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