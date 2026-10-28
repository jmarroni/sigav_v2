@extends('layout.layout')
@section('body')
<div class="sigav-carga">
    <h2>Ventas e-commerce</h2>
    <p class="text-muted">Pedidos recibidos por el web service. El stock ya se descontó; falta facturar cada pedido.</p>

    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Pedido</th>
                <th>Renglones</th>
                <th>Comprador</th>
                <th class="text-right">Total</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($pedidos as $p)
            <tr>
                <td>{{ $p['fecha'] }}</td>
                <td>{{ $p['referencia'] }}</td>
                <td>@foreach($p['renglones'] as $r)<div>{{ $r }}</div>@endforeach</td>
                <td>{{ $p['cliente'] }}</td>
                <td class="text-right">$ {{ $p['total'] }}</td>
                <td>
                    @if($p['facturado'])
                        <span class="label label-success">Facturado</span>
                    @elseif($p['bloqueado'])
                        <span class="label label-warning">Bloqueado: verificar en AFIP</span>
                    @else
                        <span class="label label-default">Sin facturar</span>
                    @endif
                </td>
                <td>
                    @if(! $p['facturado'])
                        <form method="POST" action="/ventas-ecommerce/{{ $p['id'] }}/facturar" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm" @if($p['bloqueado']) disabled @endif>Facturar</button>
                        </form>
                        @if($p['bloqueado'])
                            <form method="POST" action="/ventas-ecommerce/{{ $p['id'] }}/liberar" style="display:inline"
                                  onsubmit="return confirm('Confirmá en AFIP que el comprobante NO fue emitido antes de liberar.');">
                                @csrf
                                <button type="submit" class="btn btn-default btn-sm">Liberar bloqueo</button>
                            </form>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No hay pedidos del e-commerce.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
