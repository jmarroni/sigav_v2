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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Cierre de caja</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp"></h2>
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
    <div class="block-header">
            <h3 class="block-title">INGRESE CANTIDAD DE BILLETES</h3>
        </div>
        <div class="block-content">
            <form class="form-horizontal" id="form-caja" action="/cierreCajaAccion" method="post" >
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <div class="form-group">
                    <div class="col-xs-1">
                        <label>100</label>
                        <input type="text" class="form-control numbers" name="cien" id="cien" value="" placeholder="12" />
                    </div>
                    <div class="col-xs-1">
                        <label>50</label>
                        <input type="text" class="form-control numbers" name="cincuenta" id="cincuenta" value="" placeholder="11" />
                    </div>
                    <div class="col-xs-1">
                        <label>20</label>
                        <input type="text" class="form-control numbers" name="veinte" id="veinte" value="" placeholder="5" />
                    </div>
                    <div class="col-xs-1">
                        <label>10</label>
                        <input type="text" class="form-control numbers" name="diez"  id="diez"value="" placeholder="12" />
                    </div>
                    <div class="col-xs-1">
                        <label>5</label>
                        <input type="text" class="form-control numbers" name="cinco" id="cinco" value="" placeholder="5" />
                    </div>
                    <div class="col-xs-2">
                        <label>Operaci&oacute;n</label>
                        <select class="form-control" name="operacion">
                            <option value="0">Cierre</option>
                            <option value="1">Apertura</option>
                            <option value="2">Extracci&oacute;n</option>
                        </select>
                    </div>
                    <div class="col-xs-3">
                        <label>Observaci&oacute;n</label>
                        <input type="text" class="form-control lettersNumbers" name="observacion" value="" placeholder="nota" />
                    </div>
                    <div class="col-xs-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" id="enviar" style="margin-top: 25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Cierre/Apertura
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Productos</h3>
        </div>
        <div class="block-content">
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>Id</td>
                                    <td>Sucursal</td>
                                    <td>Usuario</td>
                                    <td>Cien</td>
                                    <td>Cincuenta</td>
                                    <td>Veinte</td>
                                    <td>Diez</td>
                                    <td>Cinco</td>
                                    <td>Operacion</td>
                                    <td>Fecha</td>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($cierres)>0)
                                     @foreach($cierres as $cierre)
                                        <?php $i = 1; ?>
                                             <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                                <td><?php echo $cierre->id; ?></td>
                                                <td><?php echo $cierre->sucursal_nombre; ?></td>
                                                <td><?php echo $cierre->usuario; ?></td>
                                                <td><?php echo $cierre->cien; ?></td>
                                                <td><?php echo $cierre->cincuenta; ?></td>
                                                <td><?php echo $cierre->veinte; ?></td>
                                                <td><?php echo $cierre->diez; ?></td>
                                                <td><?php echo $cierre->cinco; ?></td>
                                                <td title="<?php echo $cierre->observacion; ?>"><?php
                                                switch ($cierre->operacion) {
                                                    case '0':
                                                        echo "Cierre";
                                                        break;
                                                    case '1':
                                                        echo "Apertura";
                                                        break;
                                                    case '2':
                                                        echo "Extracci&oacute;n";
                                                        break;
                                                }; ?></td>
                                                <td><?php echo $cierre->fecha; ?></td>
                                            </tr>
                                 <?php $i++;?>
                                 @endforeach
                                 @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
    </div>
    <!-- END Products -->
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
<script type="text/javascript" src="/assets/js/reportes/cajas.js"></script>
<script type="text/javascript">
    $(document).ready(function(){

        <?php if ($_COOKIE["kiosco"] == "jmarroni"){ ?>
        $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
        <?php }else{?>
            $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            }
        });
        <?php } ?>
    });
</script>

	@endsection