@extends('layout.layout')

@section('body')
<div class="content content-boxed">

    {{-- Hero --}}
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Nota de Débito</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Generar a partir de una nota de crédito</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger" id="nd-error" style="display:none;"></div>

    {{-- Formulario --}}
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-file-text-o text-muted push-5-r"></i> Datos de la nota de débito</h3>
        </div>
        <div class="block-content">
            @if(count($notasCredito) === 0)
                <div class="text-center push-30 text-muted">
                    <i class="fa fa-inbox fa-3x push-10"></i>
                    <p class="font-w600">No hay notas de crédito disponibles para generar un débito</p>
                </div>
            @else
            <form class="form-horizontal" onsubmit="return false;">
                <div class="row">
                    <div class="col-sm-5">
                        <label>Nota de crédito asociada</label>
                        <select class="form-control" id="factura">
                            <option value="">Seleccione una nota de crédito</option>
                            @foreach($notasCredito as $nc)
                                <option value="{{ $nc->id }}">
                                    N° {{ $nc->numero }} — {{ \Illuminate\Support\Str::limit($nc->fecha, 10, '') }} — $ {{ number_format((float) $nc->total, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label>Observación</label>
                        <input class="form-control" type="text" id="observacion" placeholder="Motivo / observación">
                    </div>
                    <div class="col-sm-3">
                        <label>Monto</label>
                        <input class="form-control" type="text" id="precio" placeholder="Se completa al elegir la NC" readonly>
                    </div>
                </div>
                <div class="row" style="margin-top:12px;">
                    <div class="col-sm-3">
                        <label>Fecha</label>
                        <input class="form-control" type="date" id="fecha" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-sm-9 text-right" style="margin-top:25px;">
                        <button class="btn btn-rounded btn-primary" type="button" id="concretar">
                            <i class="fa fa-check push-5-r"></i>Generar Nota de Débito
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Resultado --}}
    <div class="block block-rounded" id="nd-resultado" style="display:none;">
        <div class="block-header block-header-default">
            <h3 class="block-title">Comprobante generado</h3>
        </div>
        <div class="block-content text-center">
            <iframe id="nd-iframe" style="width:98%;height:380px;border:0;"></iframe>
        </div>
    </div>
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
