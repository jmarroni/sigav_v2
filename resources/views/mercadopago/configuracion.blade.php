@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('mp_msg'))
        <div class="alert alert-success">{{ session('mp_msg') }}</div>
    @endif
    @if(session('mp_error'))
        <div class="alert alert-danger">{{ session('mp_error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo guardar:</strong>
            <ul style="margin:6px 0 0;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Mercado Pago — cuentas por sucursal</h3>
        </div>
        <div class="block-content">
            @foreach($sucursales as $sucursal)
                @php $cfg = $configs[$sucursal->id]; @endphp
                <div class="block block-bordered">
                    <div class="block-header">
                        <h3 class="block-title">
                            {{ $sucursal->nombre }}
                            @if($cfg->tokenEnmascarado())
                                <span class="label label-success">token configurado ({{ $cfg->tokenEnmascarado() }})</span>
                            @else
                                <span class="label label-default">sin configurar</span>
                            @endif
                        </h3>
                    </div>
                    <div class="block-content">
                        <form action="/mercadopago/configuracion/{{ $sucursal->id }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-5">
                                    <label>Access Token</label>
                                    <input type="password" class="form-control" name="access_token"
                                        placeholder="{{ $cfg->tokenEnmascarado() ? 'Dejar vacío para no cambiarlo' : 'APP_USR-...' }}">
                                </div>
                                <div class="col-xs-5">
                                    <label>Public Key</label>
                                    <input type="text" class="form-control" name="public_key" value="{{ $cfg->public_key }}">
                                </div>
                                <div class="col-xs-2">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="activo" value="1" {{ $cfg->activo ? 'checked' : '' }}><span></span> Activo
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:10px;">Guardar</button>
                        </form>

                        <form action="/mercadopago/probar/{{ $sucursal->id }}" method="post" style="display:inline-block; margin-top:10px;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Probar conexión</button>
                        </form>

                        <a href="/mercadopago/movimientos?sucursal_id={{ $sucursal->id }}" class="btn btn-sm btn-default" style="margin-top:10px;">Ver movimientos</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
