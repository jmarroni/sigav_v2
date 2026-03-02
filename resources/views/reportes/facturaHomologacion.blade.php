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
                <div class="bg-warning-op">
                    <div class="content">
                        <div class="block block-transparent block-themed text-center">
                            <div class="block-content">
                                <h1 class="h1 font-w700 text-white animated fadeInDown push-5">
                                    <i class="fa fa-flask"></i> Facturas de Homologacion (Pruebas)
                                </h1>
                                <h2 class="h4 font-w400 text-white-op animated fadeInUp">
                                    Total: {{ $facturas->total() }} facturas de prueba
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END Section -->

            <!-- Aviso -->
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Importante:</strong> Estas facturas fueron emitidas en modo HOMOLOGACION y NO tienen validez fiscal.
                Son exclusivamente para pruebas del sistema.
            </div>

            <!-- Filtros -->
            <div class="block block-rounded">
                <div class="block-header">
                    <h3 class="block-title">Filtros</h3>
                </div>
                <div class="block-content" style="background: #f9f9f9; padding: 15px;">
                    <form method="GET" action="{{ url('reporte.facturaHomologacion') }}" id="formFiltros">
                        <div class="row">
                            <div class="col-xs-12 col-md-3">
                                <div class="form-group">
                                    <label>Fecha Desde</label>
                                    <input type="date" class="form-control" name="reporte_desde" value="{{ request('reporte_desde') }}">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-3">
                                <div class="form-group">
                                    <label>Fecha Hasta</label>
                                    <input type="date" class="form-control" name="reporte_hasta" value="{{ request('reporte_hasta') }}">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-3">
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
                            <div class="col-xs-12 col-md-3">
                                <div class="form-group">
                                    <label>Usuario</label>
                                    <input type="text" class="form-control" name="usuario" value="{{ request('usuario') }}" placeholder="Usuario...">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 text-right">
                                <input type="hidden" name="order_by" value="{{ request('order_by', 'factura.fecha') }}">
                                <input type="hidden" name="order_dir" value="{{ request('order_dir', 'desc') }}">
                                <a href="{{ url('reporte.facturaHomologacion') }}" class="btn btn-default">Limpiar Filtros</a>
                                <button type="submit" class="btn btn-warning">
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
                    <h3 class="block-title">Facturas de Prueba</h3>
                    <div class="block-options">
                        <span style="color: #666; font-size: 12px;">
                            Mostrando {{ $facturas->firstItem() ?? 0 }} - {{ $facturas->lastItem() ?? 0 }} de {{ $facturas->total() }}
                        </span>
                    </div>
                </div>
                <div class="block-content" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered" style="font-size: 12px;">
                        <thead>
                            <tr style="background-color: #fcf8e3;">
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
                                <th>Sucursal</th>
                                <th>CAE (Prueba)</th>
                                <th>Link PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facturas as $factura)
                            <tr>
                                <td>
                                    <span class="label label-warning">PRUEBA</span>
                                    {{ $factura->numero }}
                                </td>
                                <td>{{ number_format($factura->total, 2, ",", ".") }}</td>
                                <td>{{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $factura->usuario }}</td>
                                <td>{{ $factura->nombre_sucursal }}</td>
                                <td><small>{{ $factura->cae }}</small></td>
                                <td>
                                    @if($factura->pdf)
                                        <a target="_blank" href="{{ $factura->pdf }}">Ver PDF</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron facturas de homologacion</td>
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

<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<?php require ("footer.php"); ?>
<script type="text/javascript">
    $(document).ready(function(){
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
    });
</script>
