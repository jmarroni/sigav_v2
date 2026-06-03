@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
@section("body")
<div class="content content-boxed sigav-app">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Reportes</p>
        <h1>Reporte de Pagos de Proveedores</h1>
    </div>

    <!-- Filtros -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Filtros</h3></div>
        </header>
        <div class="sg-card__body">
            <form class="sg-filters" action="">
                <div class="sg-field">
                    <label for="sucursal">Sucursal</label>
                    <select class="form-control" name="sucursal" id="sucursal">
                        <option value="0">Todas</option>
                        @foreach($sucursales as $sucu)
                        <option @if($sucursal == $sucu->id) selected="selected" @endif value="{{$sucu->id}}">{{utf8_decode($sucu->nombre)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sg-filter-actions">
                    <button class="sg-btn sg-btn--primary" type="button" id="btnBuscar"><i class="fa fa-filter"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Resultados -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Resultados</h3></div>
        </header>
        <div class="sg-card__body sg-table-wrap">
            <table id="tabla_compras" class="sg-table">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Sucursal</th>
                        <th>Nombre Proveedor</th>
                        <th>Fecha</th>
                        <th>Nº Factura</th>
                        <th class="sg-num">Monto</th>
                        <th>Usuario</th>
                        <th>Factura</th>
                        <th>Remito</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                      @if (count($facturas)>0)
                    <?php  $i = 1; ?>
                    @foreach($facturas as $factura)
                    <tr>
                        <td><?php echo $i ?></td>
                        <td>{{$factura->sucursal}}</td>
                        <td class="sg-strong">{{$factura->nombreProveedor}} {{$factura->apellidoProveedor}}</td>
                        <td>{{$factura->fecha}}</td>
                        <td>{{$factura->numero_factura}}</td>
                        <td class="sg-num sg-mono">{{$factura->monto}}</td>
                        <td>{{$factura->usuario}}</td>
                        @if ($factura->ruta_archivo!=null)
                        <td><a href="<?php echo Storage::url($factura->ruta_archivo)?>" target="blank">Ver</a></td>
                        @else
                        <td>No cargada</td>
                        @endif
                        <td><a href="{{$factura->archivo}}" target="blank">Ver</a></td>
                    </tr>
                    <?php $i++; ?>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>

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
    <script type="text/javascript" src="/assets/js/reportes/reportePagoProveedores.js?v=1"></script>
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