<?php
$menu["operaciones"] = "active";
$menu["cargas"] = "";
$menu["reportes"] = "";
require_once ("conection.php");
require 'vendor/autoload.php';
require ('header.php');
if (getRol() < 4 && getRol() != 1) {
    exit();
}
//Productos vendidos hoy
$sql = "SELECT * FROM `ventas` v WHERE `fecha` > '".date("Y-m-d")."'";
$resultado = $conn->query($sql);
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas = $resultado->num_rows; 
}else{
    $cantidad_de_ventas = 0;
}
$caja = 0;
$total =0;
//Calculo la caja
$sql_caja = "SELECT * FROM `caja` WHERE `fecha` > '".date("Y-m-d")." 00:00:00' and usuario ='".$_COOKIE["kiosco"]."'";
$resultado_caja = $conn->query($sql_caja);
if ($resultado_caja->num_rows > 0) {
	$caja = 0;
	while($row_caja = $resultado_caja->fetch_assoc()) {
		switch ($row_caja["operacion"]) {
			case 1:
				$caja += $row_caja["cien"] * 100 +
						$row_caja["cincuenta"] * 50 +
						$row_caja["veinte"] * 20 +
						$row_caja["diez"] * 10 +
						$row_caja["cinco"] * 5;
			break;
			case 2:
				$caja -= $row_caja["cien"] * 100 +
						$row_caja["cincuenta"] * 50 +
						$row_caja["veinte"] * 20 +
						$row_caja["diez"] * 10 +
						$row_caja["cinco"] * 5;
				break;
		}
	} 
}else{
	$cantidad_de_ventas = 0;
}

//Productos vendidos hoy por el usuario
$sql = "SELECT * FROM `ventas` v WHERE `fecha` > '".date("Y-m-d")."' and usuario = '".$_COOKIE["kiosco"]."' AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
if ($resultado->num_rows > 0) {
    $cantidad_de_ventas_usuario = $resultado->num_rows; 
    while($row = $resultado->fetch_assoc()) {
        $total += $row["precio"] * $row["cantidad"];
    }
}

// Elimino cualquier resto de venta que no se realizo para que no se vuelva a facturar, todas quedan en estado 5.
$sql_update = "UPDATE ventas SET estado = 5 WHERE estado = 1 OR estado = 3";
$conn->query($sql_update);
?>
    <style>
        .ui-autocomplete-loading {
            background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
        }
    </style>
        <!-- Page Content -->
        <div class="content content-boxed">
            <!-- Section -->
            <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/_photo25@2x.jpg');background-position-y:-280px;">
                <div class="bg-black-op">
                    <div class="content">
                        <div class="block block-transparent block-themed text-center">
                            <div class="block-content">
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Nota de Debito</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->


            <div class="block block-rounded" id="add_success" style="display: none;background-color: #46c37b !important;color:white;">
                <div class="block-header">
                    <div class="col-xs-12 bg-success" id="nombre-devuelto"></div>
                </div>
            </div>
            <div class="block block-rounded" id="add_success_error" style="display: none;background-color: #d26a5c !important;color:white;">
                <div class="block-header">
                    <div class="col-xs-12 bg-danger" id="nombre-devuelto-error"></div>
                </div>
            </div>
            <div class="block block-rounded">
                <div class="block-content">
                    <form class="form-horizontal" action="bd_dashboard.html" method="post" onsubmit="return false;">
                        <div class="form-group">
                            <div class="col-xs-4">
                                <label for="bd-qsettings-name">Comprobante asociado</label>
                                <select  class="form-control" name="factura" id="factura">
                                    <option id="0">Seleccione una Nota de Credito</option>
                                    <?php 
                                        $sql_facturas = "SELECT *
                                                        FROM `nota_de_credito` ndc
                                                        WHERE cae <> '' AND fechacae <> '' ORDER BY fecha DESC";
                                        $resultado = $conn->query($sql_facturas);
                                        if ($resultado->num_rows > 0) {
                                            // output data of each row
                                            while($row = $resultado->fetch_assoc()) { ?>
                                            <option value="<?php echo $row["id"]; ?>" >Numero: <?php echo $row["numero"]; ?>, Fecha: <?php echo substr($row["fecha"],0,10); ?>  </option>
                                        <?php }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-xs-5">
                                <label for="bd-qsettings-name">Observaci&oacute;n</label>
                                <input class="form-control" type="text" id="observacion" name="observacion" placeholder="Observaciones de la anulacion" value="" />
                            </div>
                            <div class="col-xs-1">
                                <label>Monto</label>
                                <input class="form-control" type="text" id="precio" name="precio" placeholder="Indicar Monto de la factura a anular" value="">
                            </div>
                            <div class="col-xs-2">
                                <label>Fecha de Facturaci&oacute;n</label>
                                <input class="form-control" type="date" id="fecha" name="fecha" placeholder="dd/mm/yyyy" value="<?php echo date("Y-m-d") ?>">
                            </div>
                            <div class="col-xs-12" style="text-align:center">
                                <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="concretar_venta" style="margin-bottom: 20px;margin-top: 20px;width: 30%;">
                                    <i class="fa fa-check push-5-r"></i>Generar Nota de Credito
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Products -->
            
            <div class="block block-rounded" id="factura_iframe" >
                <div class="block-header">
                    <h3 class="block-title">Nota de Debito</h3>
                </div>
                <div class="block-content" style="text-align: center">
                    <iframe id="iframe" style="width: 98%;height: 300px;margin-bottom: 30px;"></iframe>
                </div>
            </div>
            <!-- END Products -->
            
        </div>
        <!-- END Page Content -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/pages/nota_debito.js?v=1.04"></script>
<?php require ("footer.php"); ?>