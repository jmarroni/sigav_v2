@extends('layout.layout')

<style>
    .nc-amount { font-variant-numeric: tabular-nums; font-weight: 600; }
    .nc-num { font-family: Menlo, Consolas, monospace; }
    #tabla-nc tbody tr { transition: background-color .15s ease; }
    #filtro-nc { width: 240px; display: inline-block; }
</style>

@section('body')
<div class="content content-boxed">

    {{-- Hero --}}
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Notas de Crédito</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Comprobantes emitidos</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtro por fecha --}}
    <div class="block block-rounded">
        <div class="block-content">
            <form action="/notas/credito" method="get" class="form-inline">
                <div class="form-group">
                    <label class="push-10-r">Desde</label>
                    <input type="date" name="desde" class="form-control" value="{{ $desde }}">
                </div>
                <div class="form-group" style="margin-left:12px;">
                    <label class="push-10-r">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="{{ $hasta }}">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left:12px;">
                    <i class="fa fa-filter push-5-r"></i>Filtrar
                </button>
                @if($desde || $hasta)
                    <a href="/notas/credito" class="btn btn-default" style="margin-left:6px;">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Listado --}}
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">
                <i class="fa fa-file-text-o text-muted push-5-r"></i> Notas de Crédito
                <span class="label label-primary" style="margin-left:6px;">{{ count($notas) }}</span>
            </h3>
            <div class="block-options">
                <input type="text" id="filtro-nc" class="form-control input-sm" placeholder="Filtrar por número, usuario…">
            </div>
        </div>
        <div class="block-content">
            @if(count($notas) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-vcenter" id="tabla-nc">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th class="text-right">Total</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Sucursal</th>
                            <th class="text-center">PDF</th>
                            <th class="text-center">Mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notas as $nc)
                            <tr data-buscar="{{ strtolower($nc->numero.' '.$nc->usuario.' '.$nc->nombre_sucursal) }}">
                                <td><span class="nc-num">#{{ $nc->numero }}</span></td>
                                <td class="text-right nc-amount">$ {{ number_format((float) $nc->total, 2, ',', '.') }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($nc->fecha, 16, '') }}</td>
                                <td>{{ $nc->usuario }}</td>
                                <td>{{ $nc->nombre_sucursal ?: '—' }}</td>
                                <td class="text-center">
                                    @if($nc->pdf)
                                        <a href="{{ $nc->pdf }}" target="_blank" class="btn btn-xs btn-rounded btn-default">
                                            <i class="fa fa-file-pdf-o push-5-r"></i>Ver
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-rounded btn-default js-reenviar"
                                            data-pdf="{{ $nc->pdf }}" data-num="{{ $nc->numero }}">
                                        <i class="fa fa-envelope-o push-5-r"></i>Reenviar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="nc-sin-resultados" class="text-center push-30 text-muted" style="display:none;">
                <i class="fa fa-search fa-2x push-10"></i>
                <p class="font-w600">Ninguna nota coincide con el filtro</p>
            </div>
            @else
            <div class="text-center push-30 text-muted">
                <i class="fa fa-inbox fa-3x push-10"></i>
                <p class="font-w600">No hay notas de crédito en el período seleccionado</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal reenvío de mail --}}
<div class="modal" id="modal-reenvio" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="block block-themed remove-margin-b">
                <div class="block-header bg-primary">
                    <ul class="block-options"><li><button data-dismiss="modal" type="button"><i class="si si-close"></i></button></li></ul>
                    <h3 class="block-title">Reenviar nota de crédito <span id="reenvio-num" class="nc-num"></span></h3>
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
