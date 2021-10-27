@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
@section("body")
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Reporte de notas de crédito</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp"></h2>
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
                        <div class="col-sm-5">
                        <div class="block block-rounded">
                                <div class="block-content block-content-full">
                                    Desde:&nbsp;<input type="date" class="form-control" name="reporte_desde" id="reporte_desde" value="<?php //echo $reporte_desde; ?>">
                                </div></div>
                        </div>
                        <div class="col-sm-5">
                        <div class="block block-rounded">                
                            <div class="block-content block-content-full">
                                Hasta&nbsp;<input type="date" class="form-control" name="reporte_hasta" id="reporte_hasta" value="<?php //echo $reporte_hasta; ?>">
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
                                    <td>Link</td>
                                    <td>Mail Reenvio</td>
                                </tr>
                            </thead>
                            <tbody>
                           
                            @if (count($notasCredito)>0)
                                @foreach($notasCredito as $notaCredito)
                                 <?php $i=0;?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $notaCredito->numero; ?></td>
                                        <td>$&nbsp;<?php echo number_format($notaCredito->total,2,",","."); ?></td>
                                        <td><?php echo $notaCredito->fecha; ?></td>
                                        <td><?php echo $notaCredito->usuario; ?></td>
                                        <td><?php echo $notaCredito->nombre_sucursal; ?></td>
                                        <td><a id="reenvio_pdf_<?php echo $notaCredito->numero; ?>" target="_blank" href="<?php echo $notaCredito->pdf; ?>">LINK</a></td>
                                        <td><a id="reenvio_mail_<?php echo $notaCredito->numero; ?>" href="#">REENVIAR MAIL</a></td>
                                    </tr>
                                <?php $i++;?>
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
	@endsection
	@section("scripts")
	<!-- <script src="https://code.jquery.com/jquery-1.12.4.js"></script>-->
       <!-- END Page Content -->

<link rel="stylesheet" href="/assets/css/core/jquery.com_ui_1.12.1.css">
<link rel="stylesheet" href="/assets/css/core/jquery.dataTables1.10.13.min.css">
<link rel="stylesheet" href="/assets/css/core/buttons.dataTables1.2.4.min.css">
<script src="/assets/js/core/jqueryv1.12.4.js"></script>
<script src="/assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/assets/js/core/dataTables.buttons1.2.4.min.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.flash.min1.2.4.js"></script>
<script type="text/javascript" src="/assets/js/core/jszip.min2.5.0.js"></script>
<script type="text/javascript" src="/assets/js/core/pdfmake.min0.1.24.js"></script>
<script type="text/javascript" src="/assets/js/core/vfs_fonts0.1.24.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.html5.min1.2.4.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.print.min1.2.4.js"></script>
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

	@endsection