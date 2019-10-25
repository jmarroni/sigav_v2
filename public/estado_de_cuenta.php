<?php
    require_once ("conection.php");
    require ('header.php');

?>
<style>
        .ui-autocomplete-loading {
            background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
        }
    </style>
        <!-- Page Content -->
        <div class="content content-boxed">
            <div class="row text-uppercase">
                <div class="col-sm-12">
                    <table id="tabla_compras">
                        <thead>
                            <tr>
                                <td>ID</td>
                                <td>Fecha de Facturaci&oacute;n</td>
                                <td>Raz&oacute;n Social</td>
                                <td>Cuit</td>
                                <td>Telefono</td>
                                <td>Estado</td>
                                <td>Medio de Pago</td>
                                <td>Fecha de Pago</td>
                                <td>Periodo</td>
                                <td>Monto</td>
                                <td>Servicio</td>
                                <td>Domicilio</td>

                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $sql = "SELECT
                            ec.id,
                            ec.fecha_cuenta,
                            ec.estado,
                            ec.monto,
                            ec.fecha_pago,
                            ec.medio_pago,
                            ec.periodo,
                            s.nombre,
                            c.razon_social,
                            c.telefono,
                            c.cuit,
                            c.domicilio_legal,
                            c.localidad,
                            c.provincia
                            
                            FROM estados_contables ec
                                INNER JOIN `relacion_servicio_cliente` rsc
                                ON rsc.id = ec.`relacion_servicio_cliente_id`
                                    INNER JOIN servicios s 
                                    ON s.id = rsc.servicios_id
                                        INNER JOIN clientes c ON c.id = rsc.cliente_id";
                        $resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
                        if ($resultado->num_rows > 0) {
                            $i = 1;
                            while($row = $resultado->fetch_assoc()) {?>
                                <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                    <td><?php echo $row["id"]; ?></td>
                                    <td><?php echo $row["fecha_cuenta"]; ?></td>
                                    <td><?php echo $row["razon_social"]; ?></td>
                                    <td><?php echo $row["cuit"]; ?></td>
                                    <td><?php echo $row["telefono"]; ?></td>
                                    <td id="pendiente_<?php echo $row["id"]; ?>" 
                                        style="color:<?php
                                                switch ($row["estado"]) {
                                                    case '0': echo "#FF0000;";break;
                                                    case '1': echo "#01DF01;";break;
                                                    case '2': echo "#F7FE2E;";break;
                                                    default: echo "#646464";break;
                                                }
                                    ?>"><?php echo ($row["estado"] == "0")?"Impaga":(($row["estado"] == "1")?"Abonado":"Anulado"); ?></td>
                                    <td >
                                        <?php
                                                switch ($row["medio_pago"]) {
                                                    case '0': echo "Mercado Pago";break;
                                                    case '1': echo "Efectivo";break;
                                                    case '2': echo "Siglo XXI";break;
                                                    case '3': echo "Transferencia";break;
                                                }
                                    ?></td>
                                    <td><?php echo $row["fecha_pago"]; ?></td>
                                    
                                    <td><?php echo getMes($row["periodo"]); ?></td>
                                    <td>$ <?php echo number_format($row["monto"],2,",","."); ?></td>
                                    <td><?php echo $row["nombre"]; ?></td>
                                    <td><?php echo $row["domicilio_legal"].", ".$row["localidad"].", ".$row["provincia"]; ?></td>
                                </tr>
                            <?php $i++;}
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                        <h3 class="block-title" id="configuracion_rack_titulo">Estado del Cobro <label style="sfont-size:15px;" id="etiqueta_caja"></label></h3>
                    </div>
                    <div class="block-content">
                        <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
                            <div class="alert alert-success alert-dismissable" style="display:none;" id="rack_success" >
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h3 class="font-w300 push-15">Mensaje</h3>
                                <p id="nombre-devuelto"></p>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label>Domicilio Legal (*):</label>
                            <select id="estado_nuevo" style="margin-bottom:33px;" name="estado_nuevo" class="form-control">
                                    <option value="">Seleccione el nuevo estado para el Pago</option>
                                    <option value="1">Abonado</option>
                                    <option value="2">Anulado</option>
                                    <option value="0">Impaga</option>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <label>Medio de Pago:</label>
                            <select id="medio_pago" style="margin-bottom:33px;" name="medio_pago" class="form-control">
                                    <option value="">Seleccione el medio de Pago</option>
                                    <option value="0">Mercado Pago</option>
                                    <option value="1">Efectivo</option>
                                    <option value="2">Siglo XXI</option>
                                    <option value="3">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <label>Fecha de Pago:</label>
                            <input type="date" class="form-control" name="fecha_pago" id="fecha_pago" value="" placeholder="" value="<?php echo date("Y-m-d"); ?>">
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-default" type="button" id="cambiar_estado" >Cambiar Estado</button>
                <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cancelar</button>
            </div>
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

        var atributos = "";
        jQuery("td[id^='pendiente'").click(function(){
            atributos = $(this).attr('id').split('_');
            $("#etiqueta_caja").html("NRO. " + atributos[1] + " ACTUALMENTE " + $(this).html());
            $("#estados").modal('show');
            
        });

        jQuery("#cambiar_estado").click(function(){
            if ($("#estado_nuevo").val() != ""){
                $.ajax({
                    url: "estado_de_cuenta_post.php?medio_pago=" + $("#medio_pago").val() + "&fecha_pago=" + $("#fecha_pago").val() + "&estado_nuevo=" + $("#estado_nuevo").val() + "&estado=" + atributos[1],
                    dataType : "json"
                }).done(function(response) {
                        $("#pendiente_" + atributos[1]).html($("#estado_nuevo option:selected").text());
                        switch ($("#estado_nuevo option:selected").val()) {
                                                    case '0':   $("#pendiente_" + atributos[1]).css('color',"#FF0000");break;
                                                    case '1':   $("#pendiente_" + atributos[1]).css('color',"#01DF01");break;
                                                    case '2':   $("#pendiente_" + atributos[1]).css('color',"#F7FE2E");break;
                                                    default:    $("#pendiente_" + atributos[1]).css('color',"#646464");break;
                                                }
                        $("#estados").modal('hide');
                });
            }
        });

    });
</script>