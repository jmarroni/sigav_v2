<?php
$menu["ventas"] = "";
$menu["cargas"] = "";
$menu["reportes"] = "active";
$proveedor_id = 0;
require_once ("conection.php");
require ('header.php');

if (getRol()!=1 && getRol()!=4 && getRol()!=5) {
    exit();
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
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Reporte de Facturas</h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp"></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Stats -->
            <div class="row text-uppercase">
                <form action="reporte.factura" method="GET" >
                    <div class="block block-rounded">
                        <div class="col-sm-5">
                        <div class="block block-rounded">
                                <div class="block-content block-content-full">
                                    Desde:&nbsp;<input type="date" class="form-control" name="reporte_desde" id="reporte_desde" value="<?php echo $reporte_desde; ?>">
                                </div></div>
                        </div>
                        <div class="col-sm-5">
                        <div class="block block-rounded">                
                            <div class="block-content block-content-full">
                                Hasta&nbsp;<input type="date" class="form-control" name="reporte_hasta" id="reporte_hasta" value="<?php echo $reporte_hasta; ?>">
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
            </div>
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Numero</td>
                                    <td>Total</td>
                                    <td>Fecha</td>
                                    <td>Usuario</td>
                                    <td>Sucursal</td>
                                    <td>CAE</td>
                                    <td>Fecha CAE</td>
                                    <td>Link</td>
                                    <td>Mail Reenvio</td>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($facturas)>0)
                                @foreach($facturas as $factura)
                                <tr>
                                    <td>{{$factura->numero}}</td>
                                    <td>{{number_format($factura->total,2,",",".")}}</td>
                                    <td>{{$factura->fecha}}</td>
                                    <td>{{$factura->usuario}}</td>
                                    <td>{{$factura->nombre_sucursal}}</td>
                                    <td>{{$factura->cae}}</td>
                                    <td>{{$factura->fechacae}}</td>
                                    <td><a id="reenvio_pdf_{{$factura->numero}}; ?>" target="_blank" href="{{$factura->pdf}}">LINK</a></td>
                                    <td><a id="reenvio_mail_{{$factura->numero}}" href="#">REENVIAR MAIL</a></td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            
        </div>
        <!-- END Page Content -->

        <div class="modal in" id="reenvio_mail-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; padding-right: 16px;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="block block-themed block-transparent remove-margin-b">
                        <div class="block-header bg-primary-dark">
                            <ul class="block-options">
                                <li>
                                    <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                                </li>
                            </ul>
                            <h3 class="block-title" id="configuracion_rack_titulo">REENVIO DE MAIL</h3>
                        </div>
                        <div class="block-content">
                        <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
                            <div class="alert alert-success alert-dismissable" style="display:none;" id="rack_success" >
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <h3 class="font-w300 push-15">Mensaje</h3>
                                <p id="nombre-devuelto"></p>
                            </div>
                        </div>
                            <form class="form-horizontal" action="/carga_post.php" method="post" >
                                <input type="hidden" value="" name="rack_columna" id="rack_columna" />
                                <input type="hidden" value="" name="rack_fila" id="rack_fila" />
                                <div class="form-group">
                                    
                                    <div class="col-xs-4" style="margin-top:3%;">
                                        <label>Ingrese el email del destinatario</label>
                                        <input type="text" class="form-control" value="" name="mail_factura" id="mail_factura" />
                                    </div>
                                    <div  id="mensaje_enviado" class="col-xs-12" style="display:none;">
                                        <p style="color:#2d62a5;font-weight: bold;font-size: 12pt;"><i>El mail fue enviado correctamente</i></p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-default" type="button" id="enviar_mail" >Enviar</button>
                        <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
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
            ],
            "order": [[ 3, "desc" ]]
        });
        var id_seleccionado = ''
        $("*[id^=reenvio_mail_]").click(function(e){
            e.preventDefault();
            console.log();
            $("#reenvio_mail-modal").modal("show");
            id_seleccionado = $(this).attr('id').split("_")[2];
        });

        jQuery("#enviar_mail").click(function(){
            if (id_seleccionado != ''){
                console.log(id_seleccionado);
                if ($("#mail_factura").val() == ""){
                    alert('Debe completar el email para realizar el envio');
                }else{
                    $.ajax({
                        method: "GET",
                        url: "enviar_por_mail.php?mail=" + $("#mail_factura").val() + "&factura=" + $("#reenvio_pdf_" + id_seleccionado).attr("href"),
                        datatype: 'json'
                    })
                    .done(function (msg) {
                        if (msg == "Message has been sent"){
                            $("#mensaje_enviado").show();
                            setTimeout(function(){
                                $("#mensaje_enviado").hide();
                                $("#reenvio_mail-modal").modal("hide");
                                $("#mail_factura").val('');
                            },3000);
                        }
                    });
                }
            }
        });
    });
</script>
