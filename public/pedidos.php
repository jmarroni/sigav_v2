<?php
$menu["ventas"] = "active";
$menu["cargas"] = "";
$menu["reportes"] = "";
require_once ("conection.php");
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
$sql = "SELECT * FROM `pedidos`";
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas = $resultado->num_rows;
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
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Pedidos</h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se emitieron <?php echo $cantidad_de_ventas; ?> pedidos</h2>
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
                <div class="block-content" id="block-content">
                    <div class="block-title" >
                        Selecci&oacute;n / Alta de Clientes
                    </div>
                    <div class="block-content" id="block-content">
                        <form class="form-horizontal" id="cliente_alta" method="post" >
                            <input type="hidden" value="" name="id" id="id" />
                            <div class="form-group">
                                <div class="col-xs-4">
                                    <label for="bd-qsettings-name">Raz&oacute;n Social (*):</label>
                                    <input type="text" class="form-control" name="razon_social" autocomplete="false" id="razon_social" value="" placeholder="Empresa S.A." />
                                    <input class="form-control" type="hidden" id="clientes_id" name="clientes_id" placeholder="" value="">
                                </div>
                                <div class="col-xs-4">
                                    <label>Domicilio Legal (*):</label>
                                    <input type="text" class="form-control" name="domicilio_legal" id="domicilio_legal" value="" placeholder="Calle altura, piso ..." />
                                </div>
                                <div class="col-xs-4">
                                    <label>C&oacute;digo Postal (*):</label>
                                    <input type="text" class="form-control" name="codigo_postal" id="codigo_postal" value="" placeholder="8500" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Tel&eacute;fono (*):</label>
                                    <input type="phone" class="form-control" name="telefono" id="telefono" value="" placeholder="+54 9 2920 534323" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Provincia (*):</label>
                                    <input type="text" class="form-control" name="provincia" id="provincia" value="" placeholder="Rio Negro" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Localidad (*):</label>
                                    <input type="text" class="form-control" name="localidad" id="localidad" value="" placeholder="Viedma" />
                                </div>
                                <div class="col-xs-4">
                                    <label>CUIT (*):</label>
                                    <input type="text" class="form-control" name="cuit" id="cuit" value="" placeholder="23282568519" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Condicion ante el IVA (*):</label>
                                    <select class="form-control" name="condicion_iva" id="condicion_iva" >
                                        <option value="0">Seleccione una opci&oacute;n</option>
                                        <option value="1">Resp. Inscripto</option>
                                        <option value="2">Monotributista</option>
                                        <option value="3">Excento</option>
                                        <option value="4">Cons. Final</option>
                                    </select>
                                </div>
                                <div class="col-xs-4">
                                    <label>Representante Legal (*):</label>
                                    <input type="text" class="form-control" name="representante" id="representante" value="" placeholder="Juan Garay" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Email (*):</label>
                                    <input type="mail" class="form-control" name="email_representante" id="email_representante" value="" placeholder="mail@mail.com" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Responsable de contrataci&oacute;n:</label>
                                    <input type="text" class="form-control" name="responsable_contratacion" id="responsable_contratacion" value="" placeholder="Juan Perez" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Email:</label>
                                    <input type="mail" class="form-control" name="email_constratacion" id="email_constratacion" value="" placeholder="mail@responsable.com" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Responsable de Pagos:</label>
                                    <input type="text" class="form-control" name="responsable_pagos" id="responsable_pagos" value="" placeholder="Juan Gonzalez" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Email:</label>
                                    <input type="text" class="form-control" name="email_pagos" id="email_pagos" value="" placeholder="email@pagos.com" />
                                </div>
                                <div class="col-xs-4">
                                    <label>Horario de consulta pago a proveedores:</label>
                                    <input type="text" class="form-control" name="consulta_proveedores" id="consulta_proveedores" value="" placeholder="Martes - Viernes 8:30 a 12:00hs." />
                                </div>
                                <div class="col-xs-4">
                                    <label>Horario de entregas y retiros:</label>
                                    <input type="text" class="form-control" name="entrega_retiros" id="entrega_retiros" value="" placeholder="Lunes, Miercoles y Viernes de 9 a 12hs." />
                                </div>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </div>
            <div class="block block-rounded">
                <div class="block-content">
                    <form class="form-horizontal" id="item_pedido" method="post" onsubmit="return false;">
                        <div class="form-group">
                            <div class="col-xs-4">
                                <label for="bd-qsettings-name">Recepcion</label>
                                <input class="form-control" type="date" id="recepcion" name="recepcion" placeholder="" value="">
                            </div>
                            <div class="col-xs-4">
                                <label for="bd-qsettings-name">Entrega</label>
                                <input class="form-control" type="date" id="entrega" name="entrega" placeholder="" value="">
                            </div>
                            <div class="col-xs-4">
                                <label>Monto</label>
                                $&nbsp;<input class="form-control" type="text" id="precio" name="precio" placeholder="" value="0.00" />
                            </div>
                            <div class="col-xs-6">
                                <label for="bd-qsettings-name">Item</label>
                                <input class="form-control" type="text" id="nombre-producto" name="nombre-producto" placeholder="Ingrese parte del nombre" value="">
                                <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="5">
                                <button style="width: 100%;margin-top: 30px;" class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="anadir_pedido">
                                    <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                                </button>
                            </div>
                            <div class="col-xs-6">
                                <label for="bd-qsettings-name">Comentario</label>
                                <textarea style="height: 93px;" class="form-control"  name="comentario" id="comentario"></textarea>
                            </div>
                            
                            <div class="col-xs-6">
                                
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Products -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Pedido Actual Total: $ <label id="total_ventas">0.00</label></h3>
                </div>
                <div class="block-content" style="text-align: center">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <tbody id="tablaProductos">
                
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="presupuesto" name="presupuesto" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                        <i class="fa fa-check push-5-r"></i>Concretar Pedido
                    </button>
                </div>
            </div>

            <!-- Productos Vendidos -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Pedidos</h3>
                </div>
                        <div class="block-content">
                            <table id="tabla_compras">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"></th>
                                        <th style="width: 7%;">Pedido</th>
                                        <th style="width: 7%;">Item</th>
                                        <th >Nombre Cliente</th>
                                        <th style="width: 15%;">Telefono</th>
                                        <th style="width: 15%;">Domicilio</th>
                                        <th style="width: 15%;">Comentario</th>
                                        <th style="width: 15%;">Precio</th>
                                        <th style="width: 15%;">Estado</th>
                                        <th style="width: 15%;">Fecha Ingreso</th>
                                        <th style="width: 15%;">Fecha Egreso</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                        $sql = "SELECT p.`nro_pedido`,
                                                    p.`fecha_ingreso`,
                                                    p.`fecha_egreso`,
                                                    p.comentarios,
                                                    p.fecha_pedido,
                                                    p.item,
                                                    p.`estado` as estado_pedido,
                                                    c.* 
                                                FROM pedidos p 
                                                        INNER JOIN clientes c 
                                                            ON p.`cliente_id` = c.`id`
                                                ORDER BY nro_pedido DESC";
                                        $resultado = $conn->query($sql) or die($conn->error);
                                        if ($resultado->num_rows > 0) {
                                            // output data of each row
                                            while($row = $resultado->fetch_assoc()) { ?>
                                                
                                                    <tr>
                                                        <td class="text-center">
                                                            <i class="fa fa-angle-right"></i>
                                                        </td>
                                                        <td class="font-w600"><?php echo $row["nro_pedido"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["item"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["razon_social"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["telefono"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["domicilio_legal"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["comentarios"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["precio"]; ?></td>
                                                        <td>
                                                            
                                                            <?php
                                                            switch ($row["estado_pedido"]) {
                                                                case '1':$estado = "En Mostrador";$class = "primary";break;
                                                                case '2':$estado = "En taller";$class = "warning";break;
                                                                case '3':$estado = "En transito";$class = "warning";break;
                                                                case '4':$estado = "Finalizado";$class = "success";break;
                                                                
                                                                default: echo "sin definir"; break;
                                                            } 
                                                            ?>
                                                            <span class="label label-<?php echo $class; ?>" id="pendiente_<?php echo $row["nro_pedido"]; ?>"><?php echo $estado; ?>
                                                            </span>
                                                        </td>
                                                        <td class="font-w600"><?php echo $row["fecha_ingreso"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["fecha_egreso"]; ?></td>
                                                    </tr>
                                                
                                            <?php } 
                                        } ?>
                                        </tbody> 
                            </table>
                        </div>
            </div>
            <!-- END Products -->
        </div>
        <!-- END Page Content -->
    
<div class="modal in" id="estados" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; padding-right: 16px;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="block block-themed block-transparent remove-margin-b">
                    <div class="block-header bg-primary-dark">
                        <ul class="block-options">
                            <li>
                                <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                            </li>
                        </ul>
                        <h3 class="block-title" id="configuracion_rack_titulo">Estado del Pedido <label style="sfont-size:15px;" id="etiqueta_caja"></label></h3>
                    </div>
                    <div class="block-content">
                        <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
                            <div class="alert alert-success alert-dismissable" style="display:none;" id="rack_success" >
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h3 class="font-w300 push-15">Mensaje</h3>
                                <p id="nombre-devuelto"></p>
                            </div>
                        </div>
                        <select id="estado_nuevo" style="margin-bottom:33px;" name="estado_nuevo" class="form-control">
                                <option value="">Seleccione el nuevo estado para el pedido</option>
                                <option value="1">En Mostrador</option>
                                <option value="2">En taller</option>
                                <option value="3">En transito</option>
                                <option value="4">Finalizado</option>
                        </select>
                    </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-default" type="button" id="cambiar_estado" >Cambiar Estado</button>
                <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cancelar</button>
            </div>
        </div>
    </div>
</div>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/pages/pedidos.js?v=1.1<?php echo rand(); ?>"></script>


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
    <script>
        jQuery(function () {
            // Init page helpers (Table Tools helper)
            App.initHelpers('table-tools');


        });

        $(document).ready(function(){
            jQuery("#razon_social").keyup(function(e){
                if(e.keyCode == 8){
                    $("#clientes_id").val("");
                }
            });

            $('#tabla_compras').DataTable({
                 "language": {
                    "url": "/assets/language/Spanish.json"
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });


            jQuery("#razon_social").autocomplete({
                source: "get_cliente.php",
                minLength: 2,
                select: function( event, ui ) {
                    $("#razon_social").val(ui.item.razon_social);
                    $("#clientes_id").val(ui.item.id);
                    $("#domicilio_legal").val(ui.item.domicilio_legal);
                    $("#codigo_postal").val(ui.item.codigo_postal);
                    $("#telefono").val(ui.item.telefono);
                    $("#provincia").val(ui.item.provincia);
                    $("#localidad").val(ui.item.localidad);
                    $("#cuit").val(ui.item.cuit);
                    $("#condicion_iva").val(ui.item.condicion_iva);
                    $("#representante").val(ui.item.representante);
                    $("#email_representante").val(ui.item.email_representante);
                    $("#responsable_contratacion").val(ui.item.responsable_contratacion);
                    $("#email_constratacion").val(ui.item.email_constratacion);
                    $("#responsable_pagos").val(ui.item.responsable_pagos);
                    $("#email_pagos").val(ui.item.email_pagos);
                    $("#consulta_proveedores").val(ui.item.consulta_proveedores);
                    $("#entrega_retiros").val(ui.item.entrega_retiros);
                    $("#fecha_alta").val(ui.item.fecha_alta);
                    $("#deshabilitado").val(ui.item.deshabilitado);

                }
            });
        });
    </script>
<?php require ("footer.php"); ?>