<?php
$menu["ventas"] = "";
$menu["cargas"] = "";
$menu["reportes"] = "active";
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
        .sortable { cursor: pointer; }
        .sortable:hover { background-color: #f5f5f5; }
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
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">
                                    Total: {{ $facturas->total() }} facturas
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Filtros -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Filtros</h3>
                </div>
                <div class="block-content" style="background: #f9f9f9; padding: 15px;">
                    <form method="GET" action="{{ url('reporte.factura') }}" id="formFiltros">
                        <div class="row">
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>Fecha Desde</label>
                                    <input type="date" class="form-control" name="reporte_desde" value="{{ request('reporte_desde') }}">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>Fecha Hasta</label>
                                    <input type="date" class="form-control" name="reporte_hasta" value="{{ request('reporte_hasta') }}">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>Sucursal</label>
                                    <select name="sucursal_id" class="form-control">
                                        <option value="">Todas</option>
                                        @foreach($sucursales as $suc)
                                            <option value="{{ $suc->id }}" {{ request('sucursal_id') == $suc->id ? 'selected' : '' }}>
                                                {{ $suc->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>Usuario</label>
                                    <input type="text" class="form-control" name="usuario" value="{{ request('usuario') }}" placeholder="Usuario...">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>N&ordm; Factura</label>
                                    <input type="text" class="form-control" name="numero" value="{{ request('numero') }}" placeholder="Numero...">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <div class="form-group">
                                    <label>Cliente (Nombre/CUIT)</label>
                                    <input type="text" class="form-control" name="cliente" value="{{ request('cliente') }}" placeholder="Cliente...">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <input type="hidden" name="order_by" value="{{ request('order_by', 'factura.fecha') }}">
                                <input type="hidden" name="order_dir" value="{{ request('order_dir', 'desc') }}">
                                <a href="{{ url('reporte.factura') }}" class="btn btn-default">Limpiar Filtros</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Facturas</h3>
                    <div class="block-options">
                        <span style="color: #666; font-size: 12px;">
                            Mostrando {{ $facturas->firstItem() ?? 0 }} - {{ $facturas->lastItem() ?? 0 }} de {{ $facturas->total() }}
                        </span>
                    </div>
                </div>
                <div class="block-content" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered" style="font-size: 12px;">
                        <thead>
                            <tr>
                                @php
                                    $currentOrder = request('order_by', 'factura.fecha');
                                    $currentDir = request('order_dir', 'desc');
                                @endphp
                                <th class="sortable" data-column="factura.numero" style="cursor:pointer;">
                                    Numero
                                    @if($currentOrder == 'factura.numero')
                                        <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="sortable" data-column="factura.total" style="cursor:pointer;">
                                    Total
                                    @if($currentOrder == 'factura.total')
                                        <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="sortable" data-column="factura.fecha" style="cursor:pointer;">
                                    Fecha
                                    @if($currentOrder == 'factura.fecha')
                                        <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="sortable" data-column="factura.usuario" style="cursor:pointer;">
                                    Usuario
                                    @if($currentOrder == 'factura.usuario')
                                        <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="sortable" data-column="sucursales.nombre" style="cursor:pointer;">
                                    Sucursal
                                    @if($currentOrder == 'sucursales.nombre')
                                        <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>CAE</th>
                                <th>Fecha CAE</th>
                                <th>Link</th>
                                <th>Mail Reenvio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facturas as $factura)
                            <tr>
                                <td>{{ $factura->numero }}</td>
                                <td>{{ number_format($factura->total, 2, ",", ".") }}</td>
                                <td>{{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $factura->usuario }}</td>
                                <td>{{ $factura->nombre_sucursal }}</td>
                                <td>{{ $factura->cae }}</td>
                                <td>{{ $factura->fechacae ? \Carbon\Carbon::parse($factura->fechacae)->format('d/m/Y') : '' }}</td>
                                <td><a id="reenvio_pdf_{{ $factura->numero }}" target="_blank" href="{{ $factura->pdf }}">LINK</a></td>
                                <td><a id="reenvio_mail_{{ $factura->numero }}" href="#">REENVIAR MAIL</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron facturas</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Paginacion -->
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-xs-6">
                            <p style="color: #666; padding-top: 7px;">
                                Mostrando {{ $facturas->firstItem() ?? 0 }} a {{ $facturas->lastItem() ?? 0 }} de {{ $facturas->total() }} registros
                            </p>
                        </div>
                        <div class="col-xs-6 text-right">
                            @if ($facturas->hasPages())
                                <ul class="pagination" style="margin: 0;">
                                    @if ($facturas->onFirstPage())
                                        <li class="disabled"><span>&laquo;</span></li>
                                    @else
                                        <li><a href="{{ $facturas->previousPageUrl() }}">&laquo;</a></li>
                                    @endif

                                    @foreach ($facturas->getUrlRange(max(1, $facturas->currentPage() - 2), min($facturas->lastPage(), $facturas->currentPage() + 2)) as $page => $url)
                                        @if ($page == $facturas->currentPage())
                                            <li class="active"><span>{{ $page }}</span></li>
                                        @else
                                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                                        @endif
                                    @endforeach

                                    @if ($facturas->hasMorePages())
                                        <li><a href="{{ $facturas->nextPageUrl() }}">&raquo;</a></li>
                                    @else
                                        <li class="disabled"><span>&raquo;</span></li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                    </div>
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
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<?php require ("footer.php"); ?>
<script type="text/javascript">
    $(document).ready(function(){
        // Ordenamiento al hacer clic en las columnas
        $('.sortable').on('click', function(){
            var column = $(this).data('column');
            var currentOrder = $('input[name="order_by"]').val();
            var currentDir = $('input[name="order_dir"]').val();

            if (currentOrder === column) {
                $('input[name="order_dir"]').val(currentDir === 'asc' ? 'desc' : 'asc');
            } else {
                $('input[name="order_by"]').val(column);
                $('input[name="order_dir"]').val('desc');
            }

            $('#formFiltros').submit();
        });

        var id_seleccionado = ''
        $("*[id^=reenvio_mail_]").click(function(e){
            e.preventDefault();
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
