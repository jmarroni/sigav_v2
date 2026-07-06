@extends('layout.layout')

@section('body')
<div class="content content-boxed sigav-app">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Comprobantes</p>
        <h1>Nota de débito</h1>
    </div>

    <div class="sg-alert sg-alert--err" id="nd-error" style="display:none;"></div>

    <!-- Formulario -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Datos de la nota de débito</h3></div>
            <p class="sg-card__hint">Se genera a partir de una nota de crédito existente</p>
        </header>
        <div class="sg-card__body">
            @if(count($notasCredito) === 0)
                <div class="sg-note" style="text-align:center;">
                    No hay notas de crédito disponibles para generar un débito.
                </div>
            @else
            <form class="sg-form" onsubmit="return false;">
                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Comprobante</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="factura">Nota de crédito asociada <span class="sg-req">*</span></label>
                            <select class="form-control" id="factura">
                                <option value="">Seleccione una nota de crédito</option>
                                @foreach($notasCredito as $nc)
                                    <option value="{{ $nc->id }}">N° {{ $nc->numero }} — {{ \Illuminate\Support\Str::limit($nc->fecha, 10, '') }} — $ {{ number_format((float) $nc->total, 2, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sg-field">
                            <label for="observacion">Observación</label>
                            <input class="form-control" type="text" id="observacion" placeholder="Motivo / observación">
                        </div>
                        <div class="sg-field">
                            <label for="precio">Monto</label>
                            <input class="form-control sg-mono" type="text" id="precio" placeholder="Se completa al elegir la NC" readonly>
                        </div>
                    </div>
                </div>
                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Emisión</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="fecha">Fecha</label>
                            <input class="form-control" type="date" id="fecha" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
                <div class="sg-form__actions">
                    <button class="sg-btn sg-btn--primary" type="button" id="concretar">
                        <i class="fa fa-check"></i> Generar Nota de Débito
                    </button>
                </div>
            </form>
            @endif
        </div>
    </section>

    <!-- Resultado -->
    <section class="sg-card" id="nd-resultado" style="display:none;">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Comprobante generado</h3></div>
        </header>
        <div class="sg-card__body" style="text-align:center;">
            <iframe id="nd-iframe" style="width:98%;height:380px;border:0;"></iframe>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var $factura = jQuery('#factura'), $precio = jQuery('#precio'),
            $fecha = jQuery('#fecha'), $obs = jQuery('#observacion'),
            $err = jQuery('#nd-error');

        // Al elegir una NC, traer total y fecha (endpoint legacy)
        $factura.on('change', function () {
            if (!this.value) { $precio.val(''); return; }
            jQuery.post('/get_nota_credito.php', { id: this.value })
                .done(function (msg) {
                    $precio.val(msg.total);
                    if (msg.fecha) $fecha.val(String(msg.fecha).substr(0, 10));
                });
        });

        // Emitir (procesador legacy probado)
        jQuery('#concretar').on('click', function () {
            $err.hide();
            if (!$factura.val()) { $err.text('Seleccioná una nota de crédito.').show(); return; }
            var $btn = jQuery(this).prop('disabled', true);
            jQuery.post('/nota_de_debito.php', { id: $factura.val(), observaciones: $obs.val() })
                .done(function (msg) {
                    if (msg && msg.factura) {
                        jQuery('#nd-resultado').show();
                        jQuery('#nd-iframe').attr('src', msg.factura);
                        setTimeout(function () { try { document.getElementById('nd-iframe').contentWindow.print(); } catch (e) {} }, 1500);
                    } else {
                        var m = (msg && msg.mensaje) ? msg.mensaje : 'Error desconocido';
                        $err.text('AFIP no emitió el comprobante: ' + m).show();
                    }
                })
                .fail(function () { $err.text('Error de comunicación al emitir la nota de débito.').show(); })
                .always(function () { $btn.prop('disabled', false); });
        });
    })();
</script>
@endsection
