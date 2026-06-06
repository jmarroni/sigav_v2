@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    @if(session('afip_msg'))
        <div class="alert alert-success">{{ session('afip_msg') }}</div>
    @endif
    @if(session('afip_error'))
        <div class="alert alert-danger">{{ session('afip_error') }}</div>
    @endif

    {{-- Switch maestro: entorno activo --}}
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Facturación AFIP — Entorno activo</h3>
        </div>
        <div class="block-content">
            <p>
                Entorno usado en ventas:
                <strong class="{{ optional($activo)->entorno === 'prod' ? 'text-success' : 'text-warning' }}">
                    {{ optional($activo)->entorno === 'prod' ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN' }}
                </strong>
            </p>
            <form action="/afip/activar" method="post" class="form-inline">
                @csrf
                <select name="entorno" class="form-control">
                    <option value="homo" {{ optional($activo)->entorno === 'homo' ? 'selected' : '' }}>Homologación</option>
                    <option value="prod" {{ optional($activo)->entorno === 'prod' ? 'selected' : '' }}>Producción</option>
                </select>
                <button type="submit" class="btn btn-primary">Cambiar entorno activo</button>
            </form>
        </div>
    </div>

    {{-- Acordeón: dos secciones --}}
    <div class="block block-rounded">
        <div class="block-content">
            @foreach(['homo' => 'Homologación', 'prod' => 'Producción'] as $key => $titulo)
                @php $cfg = $entornos[$key]; @endphp
                <div class="block block-bordered">
                    <div class="block-header">
                        <h3 class="block-title">
                            {{ $titulo }}
                            @if($cfg->tieneCredenciales())
                                <span class="label label-success">credenciales cargadas</span>
                            @else
                                <span class="label label-default">sin credenciales</span>
                            @endif
                        </h3>
                    </div>
                    <div class="block-content">

                        {{-- Credenciales --}}
                        <form action="/afip/credenciales/{{ $key }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Clave Privada (key)</label>
                                    <textarea class="form-control" name="key" rows="4" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                                </div>
                                <div class="col-xs-6">
                                    <label>Certificado (crt)</label>
                                    <textarea class="form-control" name="cert" rows="4" placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-default" style="margin-top:10px;">Guardar credenciales {{ $titulo }}</button>
                        </form>

                        <hr>

                        {{-- Datos --}}
                        <form action="/afip/configuracion/{{ $key }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-xs-3">
                                    <label>CUIT</label>
                                    <input type="text" class="form-control" name="cuit" value="{{ $cfg->cuit }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Punto de Venta</label>
                                    <input type="text" class="form-control" name="ptovta" value="{{ $cfg->ptovta }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Tipo Comprobante</label>
                                    <select class="form-control" name="comprobante">
                                        <option value="">--</option>
                                        @foreach($tipos[$key] as $t)
                                            <option value="{{ $t->Id }}" {{ (string) $t->Id === (string) $cfg->comprobante ? 'selected' : '' }}>{{ $t->Desc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xs-3">
                                    <label>Ing. Brutos</label>
                                    <input type="text" class="form-control" name="ingresos_brutos" value="{{ $cfg->ingresos_brutos }}">
                                </div>
                            </div>
                            <div class="row" style="margin-top:10px;">
                                <div class="col-xs-3">
                                    <label>Inicio de Actividades</label>
                                    <input type="date" class="form-control" name="inicio_actividades" value="{{ $cfg->inicio_actividades }}">
                                </div>
                                <div class="col-xs-3">
                                    <label>Condición frente al IVA</label>
                                    <input type="text" class="form-control" name="condicion_iva" value="{{ $cfg->condicion_iva }}">
                                </div>
                                <div class="col-xs-3">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="emitir" value="1" {{ $cfg->emitir ? 'checked' : '' }}><span></span> Emitir siempre FE
                                    </label>
                                </div>
                                <div class="col-xs-3">
                                    <label class="css-input switch switch-success" style="margin-top:25px;">
                                        <input type="checkbox" name="solicitar_datos" value="1" {{ $cfg->solicitar_datos ? 'checked' : '' }}><span></span> Solicitar datos al comprador
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:10px;">Guardar datos {{ $titulo }}</button>
                        </form>

                        <form action="/afip/probar/{{ $key }}" method="post" style="display:inline-block; margin-top:10px;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Probar conexión {{ $titulo }}</button>
                        </form>

                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
