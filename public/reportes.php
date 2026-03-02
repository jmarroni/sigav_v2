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
$sucursal_id = getSucursal($_COOKIE["sucursal"]);

// Validar y sanitizar fechas
if(isset($_POST["reporte_desde"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["reporte_desde"])) {
    $reporte_desde = $_POST["reporte_desde"];
} else {
    $reporte_desde = date("Y-m-d");
}

if(isset($_POST["reporte_hasta"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST["reporte_hasta"])) {
    $reporte_hasta = $_POST["reporte_hasta"];
} else {
    $reporte_hasta = (new DateTime(date("Y-m-d")))->modify('+1 day')->format('Y-m-d');
}

if(isset($_POST["proveedor"]) && intval($_POST["proveedor"]) != 0) {
    $proveedor_id = intval($_POST["proveedor"]);
}

// Productos vendidos hoy - usando prepared statement
$fecha_hoy = date("Y-m-d");
if ($proveedor_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.sucursal_id = ? AND v.`fecha` > ? AND pr.proveedores_id = ?");
    $stmt->bind_param("isi", $sucursal_id, $fecha_hoy, $proveedor_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.sucursal_id = ? AND v.`fecha` > ?");
    $stmt->bind_param("is", $sucursal_id, $fecha_hoy);
}
$stmt->execute();
$resultado = $stmt->get_result();
$cantidad_de_ventas = $resultado->num_rows;
$stmt->close();

// Productos vendidos - reporte
if (isset($_POST["reporte_desde"]) && isset($_POST["reporte_hasta"])) {
    if ($proveedor_id > 0) {
        $stmt = $conn->prepare("SELECT v.*, pr.*, v.fecha as vfecha FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.fecha BETWEEN ? AND ? AND v.sucursal_id = ? AND pr.proveedores_id = ?");
        $stmt->bind_param("ssii", $reporte_desde, $reporte_hasta, $sucursal_id, $proveedor_id);
    } else {
        $stmt = $conn->prepare("SELECT v.*, pr.*, v.fecha as vfecha FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.fecha BETWEEN ? AND ? AND v.sucursal_id = ?");
        $stmt->bind_param("ssi", $reporte_desde, $reporte_hasta, $sucursal_id);
    }
} else {
    if ($proveedor_id > 0) {
        $stmt = $conn->prepare("SELECT v.*, pr.*, v.fecha as vfecha FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.fecha > ? AND v.sucursal_id = ? AND pr.proveedores_id = ?");
        $stmt->bind_param("sii", $fecha_hoy, $sucursal_id, $proveedor_id);
    } else {
        $stmt = $conn->prepare("SELECT v.*, pr.*, v.fecha as vfecha FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.fecha > ? AND v.sucursal_id = ?");
        $stmt->bind_param("si", $fecha_hoy, $sucursal_id);
    }
}
$stmt->execute();
$resultado = $stmt->get_result();
$total = 0;
$cantidad_de_ventas_usuario = 0;
$caja = 540;

// Registro de horas
$datos_horas = array();
$ganancia_total = 0;
$total_facturado = 0;
for ($i=0; $i < 24; $i++) {
    $datos_horas[$i]["precio"] = 0;
    $datos_horas[$i]["ganancia"] = 0;
}
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas_usuario = $resultado->num_rows;
    while($row = $resultado->fetch_assoc()) {
        $hora = explode(':', explode(" ", $row["vfecha"])[1])[0];
        $datos_horas[intval($hora)]["precio"] += $row["precio"] * $row["cantidad"];
        $datos_horas[intval($hora)]["ganancia"] += ($row["precio"] * $row["cantidad"] - $row["costo"] * $row["cantidad"]);
        $ganancia_total += ($row["precio"] * $row["cantidad"] - $row["costo"] * $row["cantidad"]);
        $total_facturado += $row["precio"] * $row["cantidad"];
    }
}
$stmt->close();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Hora', 'Precio', 'Ganancia'],<?php
      for ($i=0; $i < 24; $i++) {
            echo "['" . ($i) . "-" . ($i + 1) . "'," . $datos_horas[$i]["precio"] . "," . $datos_horas[$i]["ganancia"] . "]";
            echo ($i < 23) ? "," : "";
        }?>
    ]);

    var options = {
      title: 'Grafico Ganancia (Total:<?php echo $ganancia_total; ?>), Precio (Total:<?php echo $total_facturado; ?>)',
      hAxis: {title: 'Hora',  titleTextStyle: {color: '#333'}},
      vAxis: {minValue: 0}
    };

    var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
    chart.draw(data, options);
  }

<?php
// Reporte por usuario
if (isset($_POST["reporte_desde"]) && isset($_POST["reporte_hasta"])) {
    $stmt = $conn->prepare("SELECT SUM(precio) AS precios, COUNT(id) AS cantidad, usuario FROM ventas WHERE fecha BETWEEN ? AND ? AND sucursal_id = ? GROUP BY usuario");
    $stmt->bind_param("ssi", $reporte_desde, $reporte_hasta, $sucursal_id);
} else {
    $stmt = $conn->prepare("SELECT SUM(precio) AS precios, COUNT(id) AS cantidad, usuario FROM ventas WHERE fecha > ? AND sucursal_id = ? GROUP BY usuario");
    $stmt->bind_param("si", $fecha_hoy, $sucursal_id);
}
$stmt->execute();
$resultado_3d = $stmt->get_result();

if ($resultado_3d->num_rows > 0) {
?>
  function drawChart_3d() {

    var data = google.visualization.arrayToDataTable([
    	['Usuario', 'Monto'],
<?php while($row_3d = $resultado_3d->fetch_assoc()) { ?>
      ['<?php echo htmlspecialchars($row_3d["usuario"]); ?>', <?php echo floatval($row_3d["precios"]); ?>],
<?php } ?>
    ]);

    var options = {
      title: 'Monto Facturado',
      is3D: true,
    };

    var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
    chart.draw(data, options);
  }
  google.charts.load("current", {packages:["corechart"]});
  google.charts.setOnLoadCallback(drawChart);
  google.charts.setOnLoadCallback(drawChart_3d);
  google.charts.setOnLoadCallback(line_chart);
<?php
}
$stmt->close();

// Pagos a proveedores
$mes_reporte = substr($reporte_desde, 0, 7) . "%";
$stmt = $conn->prepare("SELECT ROUND(SUM(monto),2) AS montos, fecha FROM pagos_a_proveedores pp LEFT JOIN proveedores p ON pp.`proveedores_id` = p.`id` WHERE fecha LIKE ? GROUP BY fecha");
$stmt->bind_param("s", $mes_reporte);
$stmt->execute();
$resultado_sql = $stmt->get_result();

$data_proveedores = array();
if ($resultado_sql->num_rows > 0) {
    $i = 0;
    while($row_line = $resultado_sql->fetch_assoc()) {
        $data_proveedores[$row_line["fecha"]]["monto"] = $row_line["montos"];
        if ($i > 0) $data_proveedores[$row_line["fecha"]]["monto"] += $data_proveedores[$data_proveedores[$i -1]["fecha"]]["monto"];
        $data_proveedores[$i]["fecha"] = $row_line["fecha"];
        $i++;
    }
}
$stmt->close();

// Facturacion por dia
$stmt = $conn->prepare("SELECT SUM(precio * cantidad) AS montos, SUBSTRING(fecha,1,10) as fecha FROM ventas WHERE fecha LIKE ? AND sucursal_id = ? GROUP BY SUBSTRING(fecha,1,10)");
$stmt->bind_param("si", $mes_reporte, $sucursal_id);
$stmt->execute();
$resultado_sql = $stmt->get_result();

$data_facturacion = array();
if ($resultado_sql->num_rows > 0) {
    $i = 0;
    while($row_line = $resultado_sql->fetch_assoc()) {
        $data_facturacion[$row_line["fecha"]]["monto"] = $row_line["montos"];
        if ($i > 0) $data_facturacion[$row_line["fecha"]]["monto"] += $data_facturacion[$data_facturacion[$i -1]["fecha"]]["monto"];
        $data_facturacion[$i]["fecha"] = $row_line["fecha"];
        $i++;
    }
}
$stmt->close();
?>

function line_chart() {
	  var data = google.visualization.arrayToDataTable([
	    ['Dia', 'Pagos', 'Facturacion'],
		<?php
		$fecha_monto = 0;
		$fecha_facturacion = 0;
		for ($i = 1; $i < 32; $i++) {
			$fecha_recorrido = (intval($i) < 10) ? (substr($reporte_desde,0,7) . "-0" . $i) : (substr($reporte_desde,0,7) . "-" . $i);
			$fecha_monto = (isset($data_proveedores[$fecha_recorrido])) ? $data_proveedores[$fecha_recorrido]["monto"] : $fecha_monto;
			$fecha_facturacion = (isset($data_facturacion[$fecha_recorrido])) ? $data_facturacion[$fecha_recorrido]["monto"] : $fecha_facturacion;
			echo "['" . htmlspecialchars($fecha_recorrido) . "'," . floatval($fecha_monto) . "," . floatval($fecha_facturacion) . "],";
		}?>
	  ]);

	  var options = {
	    title: 'Comparacion Pagos / Facturacion',
	    legend: { position: 'bottom' }
	  };

	  var chart = new google.visualization.LineChart(document.getElementById('line_chart'));

	  chart.draw(data, options);
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
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Reporte de ventas</h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se vendieron <?php echo $cantidad_de_ventas; ?> productos hoy <?php echo ($proveedor_id > 0) ? "del proveedor seleccionado" : ""; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Stats -->
            <div class="row text-uppercase">
            <form action="reportes.php" method="POST" >
                <div class="block block-rounded">
                    <div class="col-sm-3">
                    <div class="block block-rounded">
                            <div class="block-content block-content-full">
                                Desde:&nbsp;<input type="date" class="form-control" name="reporte_desde" id="reporte_desde" value="<?php echo htmlspecialchars($reporte_desde); ?>">
                            </div></div>
                    </div>
                    <div class="col-sm-3">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            Hasta&nbsp;<input type="date" class="form-control" name="reporte_hasta" id="reporte_hasta" value="<?php echo htmlspecialchars($reporte_hasta); ?>">
                        </div></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="block block-rounded">
                            <div class="block-content block-content-full">
                                Proveedor&nbsp;<select class="form-control" name="proveedor">
                                    <option value="0">Seleccione el proveedor</option>
                                    <?php
                                    $sql = "SELECT * FROM `proveedores`";
                                    $resultado = $conn->query($sql);
                                    if ($resultado->num_rows > 0) {
                                        while($row = $resultado->fetch_assoc()) {
                                            ?>
                                            <option <?php echo ($proveedor_id == $row["id"]) ? "selected='selected'" : ""; ?> value="<?php echo intval($row["id"]); ?>"><?php echo htmlspecialchars($row["nombre"]); ?></option>
                                        <?php }} ?>
                                </select>
                            </div></div>
                    </div>
                    <div class="col-sm-2">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full" style="padding-top: 40px;">
                            <button class="btn btn-primary" style="width: 100%;">Filtrar</button>
                        </div></div>
                    </div>
                </div>
            </form>
                <div class="col-sm-12">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div id="chart_div" style="width: 100%; height: 500px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div id="piechart_3d" style="width: 100%; height: 500px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full">
                            <div id="line_chart" style="width: 100%; height: 500px;"></div>
                        </div>
                    </div>
                </div>
                </div>
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
                                    <td>Costo Reposici&oacute;n</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Reporte de productos vendidos
                            if (isset($_POST["reporte_desde"]) && isset($_POST["reporte_hasta"])) {
                                if ($proveedor_id > 0) {
                                    $stmt = $conn->prepare("SELECT SUM(v.cantidad) as cantidad, pr.nombre, pr.precio_unidad as precio, pr.costo as costo, pr.precio_unidad - pr.costo as ganancia, stock, precio_reposicion FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.`fecha` BETWEEN ? AND ? AND v.sucursal_id = ? AND pr.proveedores_id = ? GROUP BY pr.id ORDER BY cantidad DESC");
                                    $stmt->bind_param("ssii", $reporte_desde, $reporte_hasta, $sucursal_id, $proveedor_id);
                                } else {
                                    $stmt = $conn->prepare("SELECT SUM(v.cantidad) as cantidad, pr.nombre, pr.precio_unidad as precio, pr.costo as costo, pr.precio_unidad - pr.costo as ganancia, stock, precio_reposicion FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.`fecha` BETWEEN ? AND ? AND v.sucursal_id = ? GROUP BY pr.id ORDER BY cantidad DESC");
                                    $stmt->bind_param("ssi", $reporte_desde, $reporte_hasta, $sucursal_id);
                                }
                            } else {
                                if ($proveedor_id > 0) {
                                    $stmt = $conn->prepare("SELECT SUM(v.cantidad) as cantidad, pr.nombre, pr.precio_unidad as precio, pr.costo as costo, pr.precio_unidad - pr.costo as ganancia, stock, precio_reposicion FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.`fecha` > ? AND v.sucursal_id = ? AND pr.proveedores_id = ? GROUP BY pr.id ORDER BY cantidad DESC");
                                    $stmt->bind_param("sii", $fecha_hoy, $sucursal_id, $proveedor_id);
                                } else {
                                    $stmt = $conn->prepare("SELECT SUM(v.cantidad) as cantidad, pr.nombre, pr.precio_unidad as precio, pr.costo as costo, pr.precio_unidad - pr.costo as ganancia, stock, precio_reposicion FROM `ventas` v INNER JOIN productos pr ON pr.id = v.productos_id WHERE v.`fecha` > ? AND v.sucursal_id = ? GROUP BY pr.id ORDER BY cantidad DESC");
                                    $stmt->bind_param("si", $fecha_hoy, $sucursal_id);
                                }
                            }
                            $stmt->execute();
                            $resultado = $stmt->get_result();

                            if ($resultado->num_rows > 0) {
                                $i = 1;
                                while($row = $resultado->fetch_assoc()) {?>
                                    <tr style="<?php echo (($i % 2) == 0) ? "background-color: #fff !important;" : "background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo htmlspecialchars($row["nombre"]); ?></td>
                                        <td><?php echo intval($row["cantidad"]); ?></td>
                                        <td><?php echo intval($row["stock"]); ?></td>
                                        <td><?php echo round($row["precio"] * $row["cantidad"], 2); ?></td>
                                        <td><?php echo round($row["costo"] * $row["cantidad"], 2); ?></td>
                                        <td><?php echo round($row["ganancia"] * $row["cantidad"], 2); ?></td>
                                        <td><?php echo round($row["precio_reposicion"]); ?></td>
                                    </tr>
                                <?php $i++;}
                            }
                            $stmt->close();
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
