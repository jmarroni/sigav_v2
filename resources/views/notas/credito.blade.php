@extends('layout.layout')

<style>
    .sigav-app .nc-amount { font-variant-numeric: tabular-nums; font-weight: 600; }
    .sigav-app #filtro-nc { max-width: 260px; }
</style>

@section('body')
<div class="content content-boxed sigav-app">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Comprobantes</p>
        <h1>Notas de crédito</h1>
    </div>

    <!-- Filtros -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3>Filtros</h3></div>
        </header>
        <div class="sg-card__body">
            <form class="sg-filters" action="/notas/credito" method="get">
                <div class="sg-field">
                    <label for="desde">Desde</label>
                    <input type="date" name="desde" id="desde" class="form-control" value="{{ $desde }}">
                </div>
                <div class="sg-field">
                    <label for="hasta">Hasta</label>
                    <input type="date" name="hasta" id="hasta" class="form-control" value="{{ $hasta }}">
                </div>
                <div class="sg-filter-actions">
                    <button type="submit" class="sg-btn sg-btn--primary"><i class="fa fa-filter"></i> Filtrar</button>
                    @if($desde || $hasta)
                        <a href="/notas/credito" class="sg-btn sg-btn--ghost">Limpiar</a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Resultados -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title">
                <span class="sg-dot"></span>
                <h3>Notas de crédito</h3>
                <span class="sg-count">{{ count($notas) }}</span>
            </div>
            <div class="sg-field sg-field--inline">
                <input type="text" id="filtro-nc" class="form-control" placeholder="Filtrar por número, usuario…">
            </div>
        </header>
        <div class="sg-card__body sg-table-wrap">
            @if(count($notas) > 0)
            <table class="sg-table" id="tabla-nc">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th class="sg-num">Total</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Sucursal</th>
                        <th class="sg-num">PDF</th>
                        <th class="sg-num">Mail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notas as $nc)
                        <tr data-buscar="{{ strtolower($nc->numero.' '.$nc->usuario.' '.$nc->nombre_sucursal) }}">
                            <td class="sg-mono sg-strong">#{{ $nc->numero }}</td>
                            <td class="sg-num nc-amount">$ {{ number_format((float) $nc->total, 2, ',', '.') }}</td>
                            <td class="sg-muted">{{ \Illuminate\Support\Str::limit($nc->fecha, 16, '') }}</td>
                            <td>{{ $nc->usuario }}</td>
                            <td>{{ $nc->nombre_sucursal ?: '—' }}</td>
                            <td class="sg-num">
                                @if($nc->pdf)
                                    <a href="{{ $nc->pdf }}" target="_blank" class="sg-btn sg-btn--ghost"><i class="fa fa-file-pdf-o"></i> Ver</a>
                                @else
                                    <span class="sg-muted">—</span>
                                @endif
                            </td>
                            <td class="sg-num">
                                <button type="button" class="sg-btn sg-btn--ghost js-reenviar" data-pdf="{{ $nc->pdf }}" data-num="{{ $nc->numero }}">
                                    <i class="fa fa-envelope-o"></i> Reenviar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="nc-sin-resultados" class="sg-note" style="display:none; text-align:center;">
                Ninguna nota coincide con el filtro.
            </div>
            @else
            <div class="sg-note" style="text-align:center;">
                No hay notas de crédito en el período seleccionado.
            </div>
            @endif
        </div>
    </section>
</div>

{{-- Modal reenvío de mail --}}
<div class="modal" id="modal-reenvio" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="block block-themed remove-margin-b">
                <div class="block-header bg-primary">
                    <ul class="block-options"><li><button data-dismiss="modal" type="button"><i class="si si-close"></i></button></li></ul>
                    <h3 class="block-title">Reenviar nota de crédito <span id="reenvio-num" class="sg-mono"></span></h3>
                </div>
                <div class="block-content">
                    <div class="alert alert-success" id="reenvio-ok" style="display:none;">Mail enviado correctamente.</div>
                    <div class="alert alert-danger" id="reenvio-err" style="display:none;">No se pudo enviar el mail.</div>
                    <div class="form-group">
                        <label>Email del destinatario</label>
                        <input type="email" class="form-control" id="reenvio-mail" placeholder="cliente@ejemplo.com">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-primary" type="button" id="reenvio-enviar">Enviar</button>
                <button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        // Filtro en vivo
        var input = document.getElementById('filtro-nc');
        if (input) {
            input.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase(), vis = 0;
                document.querySelectorAll('#tabla-nc tbody tr').forEach(function (tr) {
                    var ok = tr.getAttribute('data-buscar').indexOf(q) !== -1;
                    tr.style.display = ok ? '' : 'none';
                    if (ok) vis++;
                });
                var vacio = document.getElementById('nc-sin-resultados');
                if (vacio) vacio.style.display = vis === 0 ? '' : 'none';
            });
        }

        // Reenvío de mail (reusa el endpoint legacy /enviar_por_mail.php)
        var pdfActual = '';
        document.querySelectorAll('.js-reenviar').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pdfActual = this.getAttribute('data-pdf') || '';
                document.getElementById('reenvio-num').textContent = '#' + this.getAttribute('data-num');
                document.getElementById('reenvio-ok').style.display = 'none';
                document.getElementById('reenvio-err').style.display = 'none';
                document.getElementById('reenvio-mail').value = '';
                jQuery('#modal-reenvio').modal('show');
            });
        });
        document.getElementById('reenvio-enviar').addEventListener('click', function () {
            var mail = document.getElementById('reenvio-mail').value.trim();
            if (!mail) { document.getElementById('reenvio-err').textContent = 'Ingresá un email.'; document.getElementById('reenvio-err').style.display = ''; return; }
            jQuery.get('/enviar_por_mail.php', { mail: mail, factura: pdfActual })
                .done(function (msg) {
                    var ok = (typeof msg === 'string' && msg.indexOf('sent') !== -1);
                    document.getElementById(ok ? 'reenvio-ok' : 'reenvio-err').style.display = '';
                    if (ok) setTimeout(function () { jQuery('#modal-reenvio').modal('hide'); }, 1500);
                })
                .fail(function () { document.getElementById('reenvio-err').style.display = ''; });
        });
    })();
</script>
@endsection
