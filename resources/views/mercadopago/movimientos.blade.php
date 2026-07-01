@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('mp_msg'))
        <div class="alert alert-success">{{ session('mp_msg') }}</div>
    @endif
    @if(session('mp_error'))
        <div class="alert alert-danger">{{ session('mp_error') }}</div>
    @endif

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Movimientos Mercado Pago</h3>
        </div>
        <div class="block-content">

            <form action="/mercadopago/movimientos" method="get" class="form-inline" style="margin-bottom:15px;">
                @if($esAdmin)
                    <label>Sucursal</label>
                    <select name="sucursal_id" class="form-control" onchange="this.form.submit()">
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id }}" {{ (int) $sucursalId === (int) $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                @endif
                <label>Desde</label>
                <input type="date" class="form-control" name="desde" value="{{ $desde->toDateString() }}">
                <label>Hasta</label>
                <input type="date" class="form-control" name="hasta" value="{{ $hasta->toDateString() }}">
                <button type="submit" class="btn btn-default">Filtrar</button>
            </form>

            @if(! $config || ! $config->tokenEnmascarado())
                <div class="alert alert-warning">
                    Esta sucursal todavía no tiene un Access Token de Mercado Pago configurado.
                    <a href="/mercadopago/configuracion">Configurar ahora</a>.
                </div>
            @else
                <form action="/mercadopago/movimientos/sincronizar/{{ $sucursalId }}" method="post" class="form-inline" style="margin-bottom:15px;">
                    @csrf
                    <input type="hidden" name="desde" value="{{ $desde->toDateString() }}">
                    <input type="hidden" name="hasta" value="{{ $hasta->toDateString() }}">
                    <button type="submit" class="btn btn-sm btn-success">Sincronizar ahora</button>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Monto neto</th>
                            <th>Estado</th>
                            <th>Medio de pago</th>
                            <th>Comprador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            <tr>
                                <td>{{ $pago->fecha->format('d/m/Y H:i') }}</td>
                                <td>${{ number_format($pago->monto, 2, ',', '.') }}</td>
                                <td>{{ $pago->monto_neto ? '$'.number_format($pago->monto_neto, 2, ',', '.') : '-' }}</td>
                                <td>
                                    <span class="label {{ $pago->estado === 'approved' ? 'label-success' : ($pago->estado === 'rejected' ? 'label-danger' : 'label-warning') }}">
                                        {{ $pago->estado }}
                                    </span>
                                </td>
                                <td>{{ $pago->medio_pago ?? '-' }}</td>
                                <td>{{ $pago->comprador ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No hay movimientos sincronizados en este rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $pagos->links() }}
            @endif

        </div>
    </div>

</div>
@endsection
