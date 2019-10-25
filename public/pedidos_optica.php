<?php
$menu["ventas"] = "active";
$menu["cargas"] = "";
$menu["reportes"] = "";
require_once ("conection.php");
require ('header.php');
if (getRol() < 4 && getRol() != 1) {
    exit();
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
        .no-border{
            border: 0px solid;
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
                <div class="block-content">
                    <form class="form-horizontal" action="bd_dashboard.html" method="post" onsubmit="return false;">
                    ,<input type="hidden" id="pedido_nro" name="pedido_nro" value="" />
                        <div class="form-group">
                            <div class="col-xs-4">
                            <label>Seleccione el cliente</label>
                                
                                <select class="form-control" name="cliente" id="cliente">
                                    <option  value="0">Seleccione un cliente</option>
                                    <?php
                                        $sql = "SELECT 
                                                    c.*
                                                    FROM clientes c 
                                                    order by id DESC ";
                                        $resultado = $conn->query($sql) or die($conn->error);
                                        $datos = '{"data":"no data"}';
                                        if ($resultado->num_rows > 0) {
                                            // output data of each row
                                            while($row = $resultado->fetch_assoc()) { ?>
                                        <option value="<?php echo $row["id"]; ?>"><?php echo $row["razon_social"]."&nbsp;(".$row["cuit"].")"; ?></option>
                                            <?php }
                                        } ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="block block-rounded">
                <div class="block-content">
                    <form class="form-horizontal" id="datos_completos" method="post" onsubmit="return false;">
                        <div class="form-group">
                            <div class="col-xs-3">
                                <label for="bd-qsettings-name">Doctor</label>
                                <input class="form-control" type="text" id="doctor" name="doctor" placeholder="Nombre del profesional" value="">
                            </div>
                            <div class="col-xs-3">
                                <label for="bd-qsettings-name">Fecha R/P</label>
                                <input class="form-control" type="date" id="fecha_rp" name="fecha_rp" placeholder="dd/mm/yyyy" value="">
                            </div>
                            <div class="col-xs-3">
                                <label for="bd-qsettings-name">Pedido</label>
                                <input class="form-control" type="text" id="pedido" name="pedido"  value="">
                            </div>
                            <div class="col-xs-3">
                                <label for="bd-qsettings-name">Retira</label>
                                <input class="form-control" type="text" id="retira" name="retira"  value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <table border="1" style="width:100%">
                                    <thead>
                                        <th style="width:5%;text-align:center">&nbsp;</th>
                                        <th style="width:5%;text-align:center">&nbsp;</th>
                                        <th style="width:5%;text-align:center">&nbsp;ESF.</th>
                                        <th style="text-align:center">&nbsp;CIL.</th>
                                        <th style="text-align:center">&nbsp;EJE</th>
                                        <th style="text-align:center">&nbsp;D.I.P.</th>
                                        <th style="text-align:center">&nbsp;ALT. PEL.</th>
                                        <th style="text-align:center">&nbsp;PRODUCTO</th>
                                        <th style="text-align:center">&nbsp;ARMAZON</th>
                                    </thead>
                                    <tbody>
                                        <tr  style="height:30px;">
                                            <td>&nbsp;</td>
                                            <td rowspan="2" style="text-align:center">L</td>
                                            <td style="text-align:center">&nbsp;D</td>
                                            <td><input class="form-control no-border" type="text" id="d_esf"        name="d_esf"  value=""></td>
                                            <td><input class="form-control no-border" type="text" id="d_eje"        name="d_eje"  value=""></td>
                                            <td><input class="form-control no-border" type="text" id="d_dip"        name="d_dip"  value=""></td>
                                            <td><input class="form-control no-border" type="text" id="d_alt_pel"    name="d_alt_pel"  value=""></td>
                                            <td  rowspan="2"><textarea class="no-border" style="height:80px" name="producto"></textarea></td>
                                            <td  rowspan="2"><textarea class="no-border" style="height:80px" name="armazon"></textarea></td>
                                        </tr>
                                        <tr style="height:30px;">
                                            <td>&nbsp;</td>
                                            <td style="text-align:center">&nbsp;I</td>
                                            <td><input class="form-control  no-border" type="text" id="i_esf" name="i_esf"  value=""></td>
                                            <td><input class="form-control  no-border" type="text" id="i_eje" name="i_eje"  value=""></td>
                                            <td><input class="form-control  no-border" type="text" id="i_dip" name="i_dip"  value=""></td>
                                            <td><input class="form-control  no-border" type="text" id="i_alt_pel" name="i_alt_pel"  value=""></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="presupuesto" name="presupuesto" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                                    <i class="fa fa-check push-5-r"></i>Concretar Pedido
                                </button>
                            </div>
                        </div>
                    </form>
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
                                        <th style="width: 7%;">ID</th>
                                        <th style="width: 30%;">Cliente</th>
                                        <th style="width: 30%;">Doctor</th>
                                        <th style="width: 20%;">Fecha R/P</th>
                                        <th >PEDIDO</th>
                                        <th style="width: 15%;">RETIRA</th>
                                        <th style="width: 15%;">Ver Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                        $sql = "SELECT p.*,c.razon_social as cliente_nombre
                                                FROM pedidos_optica p 
                                                    INNER JOIN clientes c
                                                    ON c.id = p.cliente
                                                ORDER BY id DESC";
                                        $resultado = $conn->query($sql) or die($conn->error);
                                        if ($resultado->num_rows > 0) {
                                            // output data of each row
                                            while($row = $resultado->fetch_assoc()) { ?>
                                                
                                                    <tr>
                                                        <input type="hidden" value="<?php echo $row["d_esf"]; ?>" name="d_esf_<?php echo $row["id"]; ?>"     id="d_esf_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["d_eje"]; ?>" name="d_eje_<?php echo $row["id"]; ?>"     id="d_eje_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["d_dip"]; ?>" name="d_dip_<?php echo $row["id"]; ?>"     id="d_dip_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["d_alt_pel"]; ?>" name="d_alt_pel_<?php echo $row["id"]; ?>" id="d_alt_pel_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["producto"]; ?>" name="producto_<?php echo $row["id"]; ?>"  id="producto_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["armazon"]; ?>" name="armazon_<?php echo $row["id"]; ?>"   id="armazon_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["i_esf"]; ?>" name="i_esf_<?php echo $row["id"]; ?>"     id="i_esf_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["i_eje"]; ?>" name="i_eje_<?php echo $row["id"]; ?>"     id="i_eje_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["i_dip"]; ?>" name="i_dip_<?php echo $row["id"]; ?>"     id="i_dip_<?php echo $row["id"]; ?>" />
                                                        <input type="hidden" value="<?php echo $row["i_alt_pel"]; ?>" name="i_alt_pel_<?php echo $row["id"]; ?>" id="i_alt_pel_<?php echo $row["id"]; ?>" />
                                                        <td class="font-w600"><?php echo $row["id"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["cliente_nombre"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["doctor"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["fecha_rp"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["pedido"]; ?></td>
                                                        <td class="font-w600"><?php echo $row["retira"]; ?></td>
                                                        <td class="hidden-xs">
                                                            <a href="#" id="detalle_<?php echo $row["id"]; ?>">Detalle</a>
                                                        </td>
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
    
<div class="modal in" id="estados" tabindex="-1" role="dialog" aria-hidden="true" style="padding-right: 16px;">
    <div class="modal-dialog modal-lg">
        <div class="block-header bg-primary-dark">
            <ul class="block-options">
                <li>
                    <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                </li>
            </ul>
            <h3 class="block-title" id="configuracion_rack_titulo">Estado del Pedido <label style="sfont-size:15px;" id="etiqueta_caja"></label></h3>
        </div>
        <div class="modal-content" style="padding-top:3%">
            <div class="block block-themed block-transparent remove-margin-b">
                <div class="col-xs-12">
                    <table border="1" style="width:100%">
                        <thead>
                            <th style="width:5%;text-align:center">&nbsp;</th>
                            <th style="width:5%;text-align:center">&nbsp;</th>
                            <th style="width:5%;text-align:center">&nbsp;ESF.</th>
                            <th style="text-align:center">&nbsp;CIL.</th>
                            <th style="text-align:center">&nbsp;EJE</th>
                            <th style="text-align:center">&nbsp;D.I.P.</th>
                            <th style="text-align:center">&nbsp;ALT. PEL.</th>
                            <th style="text-align:center">&nbsp;PRODUCTO</th>
                            <th style="text-align:center">&nbsp;ARMAZON</th>
                        </thead>
                        <tbody>
                            <tr  style="height:30px;">
                                <td>&nbsp;</td>
                                <td rowspan="2" style="text-align:center">L</td>
                                <td style="text-align:center">&nbsp;D</td>
                                <td><input class="form-control no-border" type="text" id="d_esf_modal"        name="d_esf_modal"  value=""></td>
                                <td><input class="form-control no-border" type="text" id="d_eje_modal"        name="d_eje_modal"  value=""></td>
                                <td><input class="form-control no-border" type="text" id="d_dip_modal"        name="d_dip_modal"  value=""></td>
                                <td><input class="form-control no-border" type="text" id="d_alt_pel_modal"    name="d_alt_pel_modal"  value=""></td>
                                <td  rowspan="2"><textarea class="no-border" style="height:80px" name="producto_modal" id="producto_modal"></textarea></td>
                                <td  rowspan="2"><textarea class="no-border" style="height:80px" name="armazon_modal" id="armazon_modal"></textarea></td>
                            </tr>
                            <tr style="height:30px;">
                                <td>&nbsp;</td>
                                <td style="text-align:center">&nbsp;I</td>
                                <td><input class="form-control  no-border" type="text" id="i_esf_modal" name="i_esf_modal"  value=""></td>
                                <td><input class="form-control  no-border" type="text" id="i_eje_modal" name="i_eje_modal"  value=""></td>
                                <td><input class="form-control  no-border" type="text" id="i_dip_modal" name="i_dip_modal"  value=""></td>
                                <td><input class="form-control  no-border" type="text" id="i_alt_pel_modal" name="i_alt_pel_modal"  value=""></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="padding-top:3%">
                <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cerrar</button>
            </div>
        </div>
    </div>
</div>

    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
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


    <script src="/assets/js/pages/pedidos_optica.js?v=1"></script>
    <script>
        jQuery(function () {
            // Init page helpers (Table Tools helper)
            App.initHelpers('table-tools');

        });
    </script>
<?php require ("footer.php"); ?>