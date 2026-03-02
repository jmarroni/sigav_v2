@extends('layout.layout')
@section("body")
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Logs Productos Stock</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">
                            Total: {{ $productos->total() }} registros
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
            <form method="GET" action="{{ url('logsProductos') }}" id="formFiltros">
                <div class="row">
                    <div class="col-xs-12 col-md-2">
                        <div class="form-group">
                            <label>Buscar (Producto/Codigo)</label>
                            <input type="text" class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o codigo...">
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
                            <label>Tipo Operacion</label>
                            <select name="tipo_operacion" class="form-control">
                                <option value="">Todas</option>
                                @foreach($tiposOperacion as $tipo)
                                    <option value="{{ $tipo }}" {{ request('tipo_operacion') == $tipo ? 'selected' : '' }}>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-2">
                        <div class="form-group">
                            <label>Fecha Desde</label>
                            <input type="date" class="form-control" name="fecha_desde" value="{{ request('fecha_desde') }}">
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-2">
                        <div class="form-group">
                            <label>Fecha Hasta</label>
                            <input type="date" class="form-control" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 text-right">
                        <input type="hidden" name="order_by" value="{{ request('order_by', 'stock_logs.id') }}">
                        <input type="hidden" name="order_dir" value="{{ request('order_dir', 'desc') }}">
                        <a href="{{ url('logsProductos') }}" class="btn btn-default">Limpiar Filtros</a>
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
            <h3 class="block-title">Operaciones con Productos</h3>
            <div class="block-options">
                <span style="color: #666; font-size: 12px;">
                    Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }}
                </span>
            </div>
        </div>
        <div class="block-content" style="overflow-x: auto;">
            <table class="table table-striped table-hover table-bordered" style="font-size: 12px;">
                <thead>
                    <tr>
                        @php
                            $currentOrder = request('order_by', 'stock_logs.id');
                            $currentDir = request('order_dir', 'desc');
                        @endphp
                        <th class="sortable" data-column="stock_logs.id" style="cursor:pointer;">
                            N&ordm;
                            @if($currentOrder == 'stock_logs.id')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="sortable" data-column="sucursales.nombre" style="cursor:pointer;">
                            Sucursal
                            @if($currentOrder == 'sucursales.nombre')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="sortable" data-column="stock_logs.usuario" style="cursor:pointer;">
                            Usuario
                            @if($currentOrder == 'stock_logs.usuario')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>ID Prod</th>
                        <th>Barra</th>
                        <th class="sortable" data-column="productos.nombre" style="cursor:pointer;">
                            Nombre
                            @if($currentOrder == 'productos.nombre')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Stock</th>
                        <th>Stock Ant</th>
                        <th>Stock Min</th>
                        <th>Stock Min Ant</th>
                        <th class="sortable" data-column="stock_logs.created_at" style="cursor:pointer;">
                            Fecha
                            @if($currentOrder == 'stock_logs.created_at')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="sortable" data-column="stock_logs.tipo_operacion" style="cursor:pointer;">
                            Tipo Op
                            @if($currentOrder == 'stock_logs.tipo_operacion')
                                <i class="fa fa-sort-{{ $currentDir == 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                        <tr>
                            <td>{{ $producto->id }}</td>
                            <td>{{ $producto->sucursal_id == 0 ? 'Todas' : $producto->sucursal }}</td>
                            <td>{{ $producto->usuario }}</td>
                            <td>{{ $producto->productos_id }}</td>
                            <td>{{ $producto->codigo_barras ?? $producto->barra }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td>{{ $producto->stock }}</td>
                            <td>{{ $producto->stock_anterior }}</td>
                            <td>{{ $producto->stock_minimo }}</td>
                            <td>{{ $producto->stock_minimo_anterior }}</td>
                            <td>{{ $producto->updated_at }}</td>
                            <td>{{ str_replace("?", "O", $producto->tipo_operacion) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center">No se encontraron registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Paginacion -->
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-6">
                    <p style="color: #666; padding-top: 7px;">
                        Mostrando {{ $productos->firstItem() ?? 0 }} a {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} registros
                    </p>
                </div>
                <div class="col-xs-6 text-right">
                    @if ($productos->hasPages())
                        <ul class="pagination" style="margin: 0;">
                            @if ($productos->onFirstPage())
                                <li class="disabled"><span>&laquo;</span></li>
                            @else
                                <li><a href="{{ $productos->previousPageUrl() }}">&laquo;</a></li>
                            @endif

                            @foreach ($productos->getUrlRange(max(1, $productos->currentPage() - 2), min($productos->lastPage(), $productos->currentPage() + 2)) as $page => $url)
                                @if ($page == $productos->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($productos->hasMorePages())
                                <li><a href="{{ $productos->nextPageUrl() }}">&raquo;</a></li>
                            @else
                                <li class="disabled"><span>&raquo;</span></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section("scripts")
<script src="/assets/js/core/jqueryv1.12.4.js"></script>
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
@endsection
