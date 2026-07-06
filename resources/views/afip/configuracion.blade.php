@extends('layout.layout')

<style>
    .sigav-app .afip-badge { font-size:11px; font-weight:600; padding:2px 9px; border-radius:999px; margin-left:8px; vertical-align:middle; }
    .sigav-app .afip-badge--ok { color:#0b7a4b; background:#d9f2e6; }
    .sigav-app .afip-badge--no { color:#8a97a0; background:#eef2f4; }
    .sigav-app .afip-check { display:flex; align-items:center; gap:8px; font-weight:500; }
    .sigav-app .afip-check input { width:auto; }
</style>

@section('body')
<div class="content content-boxed sigav-app">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Facturación electrónica</p>
        <h1>Configuración de AFIP</h1>
    </div>

    @if(session('afip_msg'))
        <div class="sg-alert sg-alert--ok">{{ session('afip_msg') }}</div>
    @endif
    @if(session('afip_error'))
        <div class="sg-alert sg-alert--err">{{ session('afip_error') }}</div>
    @endif

    <!-- Entorno activo -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Entorno activo</h3></div>
            <p class="sg-card__hint">Es el que usa la facturación en ventas</p>
        </header>
        <div class="sg-card__body">
            <p style="margin-bottom:14px;">
                Entorno usado en ventas:
                <strong style="color:{{ optional($activo)->entorno === 'prod' ? '#0b7a4b' : '#b8860b' }};">
                    {{ optional($activo)->entorno === 'prod' ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN' }}
                </strong>
            </p>
            <form action="/afip/activar" method="post" class="sg-filters">
                @csrf
                <div class="sg-field">
                    <label for="entorno">Cambiar a</label>
                    <select name="entorno" id="entorno" class="form-control">
                        <option value="homo" {{ optional($activo)->entorno === 'homo' ? 'selected' : '' }}>Homologación</option>
                        <option value="prod" {{ optional($activo)->entorno === 'prod' ? 'selected' : '' }}>Producción</option>
                    </select>
                </div>
                <div class="sg-filter-actions">
                    <button type="submit" class="sg-btn sg-btn--primary">Cambiar entorno activo</button>
                </div>
            </form>
        </div>
    </section>

    @foreach(['homo' => 'Homologación', 'prod' => 'Producción'] as $key => $titulo)
        @php $cfg = $entornos[$key]; @endphp
        <section class="sg-card">
            <header class="sg-card__head">
                <div class="sg-card__title">
                    <span class="sg-dot {{ $key === 'prod' ? '' : 'sg-dot--warn' }}"></span>
                    <h3>{{ $titulo }}</h3>
                    @if($cfg->tieneCredenciales())
                        <span class="afip-badge afip-badge--ok">credenciales cargadas</span>
                    @else
                        <span class="afip-badge afip-badge--no">sin credenciales</span>
                    @endif
                </div>
            </header>
            <div class="sg-card__body">

                {{-- Credenciales --}}
                <form action="/afip/credenciales/{{ $key }}" method="post">
                    @csrf
                    <div class="sg-fieldset">
                        <span class="sg-fieldset__label">Credenciales</span>
                        <div class="sg-grid sg-grid--2">
                            <div class="sg-field">
                                <label>Clave Privada (key)</label>
                                <textarea class="form-control sg-mono" name="key" rows="4" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                            </div>
                            <div class="sg-field">
                                <label>Certificado (crt)</label>
                                <textarea class="form-control sg-mono" name="cert" rows="4" placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                            </div>
                        </div>
                        <div class="sg-form__actions">
                            <button type="submit" class="sg-btn sg-btn--ghost">Guardar credenciales {{ $titulo }}</button>
                        </div>
                    </div>
                </form>

                {{-- Datos fiscales --}}
                <form action="/afip/configuracion/{{ $key }}" method="post">
                    @csrf
                    <div class="sg-fieldset">
                        <span class="sg-fieldset__label">Datos fiscales</span>
                        <div class="sg-grid sg-grid--4">
                            <div class="sg-field">
                                <label>CUIT</label>
                                <input type="text" class="form-control sg-mono" name="cuit" value="{{ $cfg->cuit }}">
                            </div>
                            <div class="sg-field">
                                <label>Punto de Venta</label>
                                <input type="text" class="form-control" name="ptovta" value="{{ $cfg->ptovta }}">
                            </div>
                            <div class="sg-field">
                                <label>Tipo Comprobante</label>
                                <select class="form-control" name="comprobante">
                                    <option value="">--</option>
                                    @foreach($tipos[$key] as $t)
                                        <option value="{{ $t->Id }}" {{ (string) $t->Id === (string) $cfg->comprobante ? 'selected' : '' }}>{{ $t->Desc }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sg-field">
                                <label>Ing. Brutos</label>
                                <input type="text" class="form-control" name="ingresos_brutos" value="{{ $cfg->ingresos_brutos }}">
                            </div>
                            <div class="sg-field">
                                <label>Inicio de Actividades</label>
                                <input type="date" class="form-control" name="inicio_actividades" value="{{ $cfg->inicio_actividades }}">
                            </div>
                            <div class="sg-field">
                                <label>Condición frente al IVA</label>
                                <input type="text" class="form-control" name="condicion_iva" value="{{ $cfg->condicion_iva }}">
                            </div>
                            <div class="sg-field">
                                <label>&nbsp;</label>
                                <label class="afip-check"><input type="checkbox" name="emitir" value="1" {{ $cfg->emitir ? 'checked' : '' }}> Emitir siempre FE</label>
                            </div>
                            <div class="sg-field">
                                <label>&nbsp;</label>
                                <label class="afip-check"><input type="checkbox" name="solicitar_datos" value="1" {{ $cfg->solicitar_datos ? 'checked' : '' }}> Solicitar datos al comprador</label>
                            </div>
                        </div>
                        <div class="sg-form__actions">
                            <button type="submit" class="sg-btn sg-btn--primary">Guardar datos {{ $titulo }}</button>
                        </div>
                    </div>
                </form>

                {{-- Probar conexión (form separado, no anidado) --}}
                <form action="/afip/probar/{{ $key }}" method="post" class="sg-form__actions" style="margin-top:0;">
                    @csrf
                    <button type="submit" class="sg-btn sg-btn--ghost">Probar conexión {{ $titulo }}</button>
                </form>

            </div>
        </section>
    @endforeach

</div>
@endsection
