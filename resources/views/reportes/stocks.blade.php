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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Reporte de stock</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp"></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Section -->
    
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Reporte de stock</h3>
        </div>
        <div class="block-content">
           <div class="row text-uppercase">
            <form action="">
                <div class="block block-rounded">

                    <div class="col-sm-4">
                        <div class="block block-rounded">
                            <div class="block-content block-content-full">
                                <label>Sucursal</label>
                                <select class="form-control" name="sucursal" id="sucursal" >
                                    <option value="0">Todas</option> 
                                    @foreach($sucursales as $sucu)
                                    <option @if($sucursal == $sucu->id) selected="selected" @endif value="{{$sucu->id}}">{{utf8_decode($sucu->nombre)}}</option>
                                    @endforeach
                                </select>
                            </div></div>
                        </div>
                        <div class="col-sm-2">
                            <div class="block block-rounded">               
                                <div class="block-content block-content-full" style="padding-top: 40px;">
                                    <button class="btn btn-primary" type="button" style="width: 100%;" id="btnBuscar">Filtrar</button>
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
                                    <td>Nº</td>
                                    <td>Sucursal</td>
                                    <td>Barra</td>
                                    <td>Nombre Producto</td>
                                    <td>Precio</td>
                                    <td>Costo</td>
                                    <td>Proveedor</td>
                                    <td>Stock </td>
                                    <td>Stock Mínimo</td>                              
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                  @if (count($productos)>0)
                                <?php  $i = 1; ?>
                                @foreach($productos as $producto)
                                <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                    <td><?php echo $i ?></td>
                                    <!-- <td><?php echo $producto->id ?></td> -->
                                    <td>{{$producto->sucursal}}</td>
                                    <td>{{$producto->codigo_barras}}</td>
                                    <td>{{$producto->nombre}}</td>
                                    <td>{{$producto->precio_unidad}}</td>
                                    <td>{{$producto->costo}}</td>
                                    <td>{{$producto->nombreProveedor}} {{$producto->apellidoProveedor}}</td>
                                    <td>{{$producto->stockactual}}</td>
                                    <td>{{$producto->stockminimoActual}}</td>
                                </tr>
                                <?php $i++; ?>
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
    <script type="text/javascript" src="/assets/js/reportes/reporteStocks.js"></script>
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