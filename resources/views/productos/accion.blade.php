@extends('layout.layout');
@section("body")
<div class="sigav-carga">

    <!-- Alta de producto -->
    <section class="sg-card sg-card--form">
        <header class="sg-card__head">
            <div class="sg-card__title">
                <span class="sg-dot"></span>
                <h3>Datos del Producto</h3>
            </div>
            <p class="sg-card__hint">Los campos marcados con <b>(*)</b> son obligatorios</p>
        </header>

        <div class="sg-card__body">
            <form class="sg-form form-horizontal" action="/carga" enctype="multipart/form-data" method="post">
                <input type="hidden" value="" name="id" id="id" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Identificación</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="producto">Nombre <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" name="producto" id="producto" value="" placeholder="Nombre del producto completo" />
                        </div>
                        <div class="sg-field">
                            <label for="codigo_de_barras">Código de Barras <i>(autogenerado si se deja vacío)</i></label>
                            <input type="text" class="form-control" name="codigo_de_barras" id="codigo_de_barras" value="" placeholder="Código de barras" />
                        </div>
                        <div class="sg-field">
                            <label for="material">Material</label>
                            <input type="text" class="form-control lettersNumbers" name="material" id="material" value="" placeholder="Madera, metal, alpaca…" />
                        </div>
                    </div>
                    <div style="display:none;">
                        <input type="text" class="form-control numbers" name="stock" id="stock" value="0" />
                        <input type="text" class="form-control numbers" name="stock_minimo" id="stock_minimo" value="0" />
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Clasificación y costo</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="proveedor">Proveedor <span class="sg-req">*</span></label>
                            <select class="form-control" id="proveedor" name="proveedor">
                                <option value="0">Seleccione un proveedor</option>
                                @foreach($proveedores as $provedor)
                                <option value="{{$provedor->id}}">{{$provedor->nombre}} {{$provedor->apellido}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sg-field">
                            <label for="categoria">Categoría <span class="sg-req">*</span></label>
                            <select class="form-control" disabled="disabled" name="categoria" id="categoria">
                                <option value="">Seleccione un rubro</option>
                            </select>
                        </div>
                        <div class="sg-field">
                            <label for="costo">Precio Última Compra <span class="sg-req">*</span></label>
                            <input type="text" class="form-control prices" name="costo" id="costo" value="" placeholder="Costo por unidad" />
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Precios de venta</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="precio_unidad">Precio Minorista <span class="sg-req">*</span></label>
                            <input type="text" class="form-control prices" name="precio_unidad" id="precio_unidad" value="" placeholder="Use . para decimales (5.5)" />
                        </div>
                        <div class="sg-field">
                            <label for="precio_mayorista">Precio Mayorista <i>(opcional)</i></label>
                            <input type="text" class="form-control prices" name="precio_mayorista" id="precio_mayorista" value="" placeholder="Use . para decimales (5.5)" />
                        </div>
                        <div class="sg-field">
                            <label for="precio_reposicion">Precio Reposición <i>(opcional)</i></label>
                            <input type="text" class="form-control prices" name="precio_reposicion" id="precio_reposicion" value="" placeholder="Use . para decimales (5.5)" />
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Imágenes</span>
                    <div class="sg-grid sg-grid--imgs">
                        <div class="sg-field sg-field--file">
                            <label for="imagen1">Imagen 1</label>
                            <input type="file" class="form-control" name="imagen1" id="imagen1" />
                        </div>
                        <div class="sg-field sg-field--file">
                            <label for="imagen2">Imagen 2</label>
                            <input type="file" class="form-control" name="imagen2" id="imagen2" />
                        </div>
                        <div class="sg-field sg-field--file">
                            <label for="imagen3">Imagen 3</label>
                            <input type="file" class="form-control" name="imagen3" id="imagen3" />
                        </div>
                        <div class="sg-field sg-field--file">
                            <label for="imagen4">Imagen 4</label>
                            <input type="file" class="form-control" name="imagen4" id="imagen4" />
                        </div>
                        <div class="sg-field sg-field--file">
                            <label for="imagen5">Imagen 5</label>
                            <input type="file" class="form-control" name="imagen5" id="imagen5" />
                        </div>
                        <div class="sg-field sg-field--file">
                            <label for="imagen6">Imagen 6</label>
                            <input type="file" class="form-control" name="imagen6" id="imagen6" />
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Descripciones</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="descripcion">Descripción <span class="sg-req">*</span></label>
                            <textarea class="form-control lettersNumbers" name="descripcion" id="descripcion" placeholder="Describa el producto"></textarea>
                        </div>
                        <div class="sg-field">
                            <label for="descripcion_en">Descripción Inglés</label>
                            <textarea class="form-control lettersNumbers" name="descripcion_en" id="descripcion_en" placeholder="Product description"></textarea>
                        </div>
                        <div class="sg-field">
                            <label for="descripcion_pr">Descripción Portugués</label>
                            <textarea class="form-control lettersNumbers" name="descripcion_pr" id="descripcion_pr" placeholder="Descrição do produto"></textarea>
                        </div>
                    </div>
                </div>

                <div class="sg-form__actions">
                    <button id="anadir" class="btn btn-primary sg-btn sg-btn--primary" type="submit">
                        <i class="fa fa-check"></i> Añadir producto
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Grilla de productos -->
    <section class="sg-card sg-card--table">
        <header class="sg-card__head sg-card__head--table">
            <div class="sg-card__title">
                <span class="sg-dot"></span>
                <h3>Productos</h3>
                <span class="sg-count">{{ $totalProductos }}</span>
            </div>
            <div class="sg-field sg-field--inline">
                <label for="sucursal">Sucursal</label>
                <select class="form-control" name="sucursal" id="sucursal">
                    <option value="0">Seleccione una sucursal</option>
                    @foreach($sucursales as $sucu)
                    <option @if($sucursal == $sucu->id) selected="selected" @endif value="{{$sucu->id}}">{{utf8_decode($sucu->nombre)}}</option>
                    @endforeach
                </select>
            </div>
        </header>

        <div class="sg-card__body sg-table-wrap">
            <table id="tabla_productos" class="sg-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Categoría</th>
                        <th class="sg-num">Stock Actual</th>
                        <th class="sg-num">Precio al Público</th>
                        <th class="sg-num">Precio Reposición</th>
                        <th class="sg-num">Costo</th>
                        <th class="sg-actions-th">Acción</th>
                    </tr>
                </thead>
                <!-- Las filas se cargan por AJAX (paginación server-side, ver ProductoController@datatable) -->
                <tbody></tbody>
            </table>
        </div>
    </section>

</div>
@endsection
@section("scripts")
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="/assets/js/pages/carga.js?v=1.12"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/jquery.dataTables.min.js"></script>

<!-- Tipografía de la consola de inventario -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

<script type="text/javascript">
    $(document).ready(function(){
        // Paginación / búsqueda / orden server-side. Las filas vienen del endpoint
        // ProductoController@datatable. Se expone la instancia para que carga.js
        // pueda refrescar la grilla tras eliminar un producto.
        window.tablaProductos = $('#tabla_productos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/productos/datatable',
                type: 'GET',
                data: function (d) {
                    d.sucursal = $('#sucursal').val();
                }
            },
            language: {
                url: '/assets/language/Spanish.json'
            },
            order: [[1, 'asc']],
            columns: [
                { data: 0, className: 'sg-mono sg-muted', orderable: false },
                { data: 1, className: 'sg-strong' },
                { data: 2, orderable: false },
                { data: 3, orderable: false },
                { data: 4, className: 'sg-num', orderable: false },
                { data: 5, className: 'sg-num sg-mono' },
                { data: 6, className: 'sg-num sg-mono' },
                { data: 7, className: 'sg-num sg-mono sg-muted', orderable: false },
                { data: 8, className: 'sg-actions', orderable: false }
            ]
        });
    });
</script>

<style>
/* ============================================================
   SIGAV · Consola de inventario  (/carga)
   Estilos scopeados a .sigav-carga — no afectan el resto del sistema.
   ============================================================ */
.sigav-carga{
    --sg-ink:        #16212e;
    --sg-ink-2:      #38495a;
    --sg-muted:      #76889a;
    --sg-line:       #e6ebf0;
    --sg-line-2:     #eef2f6;
    --sg-surface:    #ffffff;
    --sg-surface-2:  #f6f9fb;
    --sg-surface-3:  #f0f5f3;
    --sg-accent:     #0e9f6e;
    --sg-accent-700: #0b7e58;
    --sg-accent-soft:#e6f6ef;
    --sg-warn:       #b7791f;
    --sg-warn-soft:  #fdf3e1;
    --sg-danger:     #d64545;
    --sg-danger-soft:#fbeaea;
    --sg-edit:       #2563c9;
    --sg-edit-soft:  #e7eefb;
    --sg-r:          14px;
    --sg-r-sm:       9px;
    --sg-shadow:     0 1px 2px rgba(20,33,46,.04), 0 8px 24px -12px rgba(20,33,46,.18);
    --sg-shadow-sm:  0 1px 2px rgba(20,33,46,.06);
    --sg-ring:       0 0 0 3px rgba(14,159,110,.18);
    --sg-sans: 'IBM Plex Sans', 'Source Sans Pro', -apple-system, sans-serif;
    --sg-mono: 'IBM Plex Mono', ui-monospace, 'SFMono-Regular', Menlo, monospace;

    font-family: var(--sg-sans);
    color: var(--sg-ink);
    font-size: 14px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
}
.sigav-carga *{ box-sizing: border-box; }

/* ---------- Tarjetas ---------- */
.sigav-carga .sg-card{
    background: var(--sg-surface);
    border: 1px solid var(--sg-line);
    border-radius: var(--sg-r);
    box-shadow: var(--sg-shadow);
    margin-bottom: 26px;
    overflow: hidden;
    opacity: 0;
    transform: translateY(10px);
    animation: sgRise .55s cubic-bezier(.16,1,.3,1) forwards;
}
.sigav-carga .sg-card--table{ animation-delay: .08s; }
@keyframes sgRise{ to{ opacity:1; transform:none; } }

.sigav-carga .sg-card__head{
    display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
    padding: 18px 24px;
    border-bottom: 1px solid var(--sg-line-2);
    background: linear-gradient(180deg, var(--sg-surface) 0%, var(--sg-surface-2) 100%);
}
.sigav-carga .sg-card__title{ display:flex; align-items:center; gap:10px; }
.sigav-carga .sg-card__title h3{
    margin:0; font-size:16px; font-weight:700; letter-spacing:-.01em; color:var(--sg-ink);
}
.sigav-carga .sg-dot{
    width:9px; height:9px; border-radius:50%;
    background: var(--sg-accent);
    box-shadow: 0 0 0 4px var(--sg-accent-soft);
}
.sigav-carga .sg-card__hint{ margin:0; font-size:12.5px; color:var(--sg-muted); }
.sigav-carga .sg-card__hint b{ color:var(--sg-ink-2); }
.sigav-carga .sg-count{
    font-family: var(--sg-mono); font-size:12px; font-weight:600;
    color: var(--sg-accent-700); background: var(--sg-accent-soft);
    padding: 2px 9px; border-radius: 999px; line-height:1.6;
}
.sigav-carga .sg-card__body{ padding: 24px; }

/* ---------- Formulario ---------- */
.sigav-carga .sg-fieldset{ margin-bottom: 22px; }
.sigav-carga .sg-fieldset:last-of-type{ margin-bottom: 4px; }
.sigav-carga .sg-fieldset__label{
    display:block; font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.09em; color:var(--sg-muted);
    padding-bottom:10px; margin-bottom:14px;
    border-bottom:1px dashed var(--sg-line);
}
.sigav-carga .sg-grid{ display:grid; gap:16px 18px; }
.sigav-carga .sg-grid--3{ grid-template-columns: repeat(3, 1fr); }
.sigav-carga .sg-grid--imgs{ grid-template-columns: repeat(6, 1fr); }
@media (max-width: 991px){
    .sigav-carga .sg-grid--3{ grid-template-columns: repeat(2, 1fr); }
    .sigav-carga .sg-grid--imgs{ grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 600px){
    .sigav-carga .sg-grid--3,
    .sigav-carga .sg-grid--imgs{ grid-template-columns: 1fr; }
}
.sigav-carga .sg-field{ display:flex; flex-direction:column; gap:6px; min-width:0; }
.sigav-carga .sg-field label{
    font-size:12.5px; font-weight:600; color:var(--sg-ink-2); margin:0;
}
.sigav-carga .sg-field label i{ font-weight:400; color:var(--sg-muted); font-size:11.5px; }
.sigav-carga .sg-req{ color:var(--sg-accent-700); font-weight:700; }

/* Inputs / selects / textarea (override OneUI dentro del scope) */
.sigav-carga .form-control{
    width:100%; height:auto; min-height:40px;
    padding:9px 12px; font-size:14px; font-family:var(--sg-sans);
    color:var(--sg-ink); background:var(--sg-surface);
    border:1px solid var(--sg-line); border-radius:var(--sg-r-sm);
    box-shadow:var(--sg-shadow-sm);
    transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.sigav-carga textarea.form-control{ min-height:74px; resize:vertical; line-height:1.55; }
.sigav-carga .form-control::placeholder{ color:#a9b6c2; }
.sigav-carga .form-control:hover{ border-color:#cfdae3; }
.sigav-carga .form-control:focus{
    outline:none; border-color:var(--sg-accent); background:#fff; box-shadow:var(--sg-ring);
}
.sigav-carga select.form-control{
    appearance:none; -webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2376889a' d='M6 8 0 0h12z'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 12px center; padding-right:32px;
    cursor:pointer;
}
.sigav-carga .form-control[disabled]{ background:var(--sg-surface-2); color:var(--sg-muted); cursor:not-allowed; }
.sigav-carga .sg-field--file input[type=file]{
    padding:7px 10px; font-size:12px; background:var(--sg-surface-2); cursor:pointer;
}

.sigav-carga .sg-form__actions{ margin-top:24px; }

/* ---------- Botones ---------- */
.sigav-carga .sg-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    font-family:var(--sg-sans); font-size:14px; font-weight:600; letter-spacing:.01em;
    border:1px solid transparent; border-radius:var(--sg-r-sm); cursor:pointer;
    padding:11px 22px; transition: transform .12s ease, box-shadow .15s ease, background .15s ease;
}
.sigav-carga .sg-btn--primary{
    width:100%; color:#fff; background:var(--sg-accent);
    box-shadow:0 6px 16px -6px rgba(14,159,110,.55);
}
.sigav-carga .sg-btn--primary:hover{ background:var(--sg-accent-700); transform:translateY(-1px); }
.sigav-carga .sg-btn--primary:active{ transform:translateY(0); box-shadow:0 3px 8px -4px rgba(14,159,110,.5); }
.sigav-carga .sg-btn--primary:focus{ outline:none; box-shadow:var(--sg-ring), 0 6px 16px -6px rgba(14,159,110,.55); }
.sigav-carga .sg-btn .fa{ font-size:13px; }

/* ---------- Tabla / cabecera ---------- */
.sigav-carga .sg-card__head--table{ row-gap:14px; }
.sigav-carga .sg-field--inline{
    flex-direction:row; align-items:center; gap:10px;
}
.sigav-carga .sg-field--inline label{ white-space:nowrap; color:var(--sg-muted); text-transform:uppercase; font-size:11px; letter-spacing:.08em; }
.sigav-carga .sg-field--inline select.form-control{ min-width:220px; height:38px; min-height:38px; }

.sigav-carga .sg-table-wrap{ padding:8px 12px 18px; overflow-x:auto; }

.sigav-carga table.sg-table{ width:100%; border-collapse:separate; border-spacing:0; }
.sigav-carga table.sg-table thead th{
    position:sticky; top:0; z-index:2;
    text-align:left; white-space:nowrap;
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em;
    color:var(--sg-muted); background:var(--sg-surface);
    padding:12px 14px; border-bottom:2px solid var(--sg-line);
}
.sigav-carga table.sg-table thead th.sg-num{ text-align:right; }
.sigav-carga table.sg-table thead th.sg-actions-th{ text-align:right; width:120px; }
.sigav-carga table.sg-table tbody td{
    padding:11px 14px; font-size:13.5px; color:var(--sg-ink-2);
    border-bottom:1px solid var(--sg-line-2); vertical-align:middle;
}
.sigav-carga table.sg-table tbody tr{ transition: background .12s ease; }
.sigav-carga table.sg-table tbody tr:hover{ background:var(--sg-surface-2); }
.sigav-carga .sg-strong{ font-weight:600; color:var(--sg-ink); }
.sigav-carga .sg-muted{ color:var(--sg-muted); }
.sigav-carga .sg-mono{ font-family:var(--sg-mono); font-size:12.5px; }
.sigav-carga td.sg-num{ text-align:right; font-variant-numeric:tabular-nums; }
.sigav-carga td.sg-num.sg-mono::before{ content:"$"; color:var(--sg-muted); margin-right:2px; }
.sigav-carga td.sg-num.sg-mono.sg-muted::before{ color:#b6c2cd; }

/* Etiquetas (categoría / proveedor) */
.sigav-carga .sg-tag{
    display:inline-block; font-size:11.5px; font-weight:500; line-height:1.4;
    color:var(--sg-ink-2); background:var(--sg-surface-3);
    border:1px solid #dceae3; border-radius:7px; padding:3px 9px; max-width:100%;
}
.sigav-carga .sg-tag--warn{ color:var(--sg-warn); background:var(--sg-warn-soft); border-color:#f3e2c0; font-style:italic; }

/* Input de stock en la grilla */
.sigav-carga input.sg-stock{
    width:64px; text-align:center; font-family:var(--sg-mono); font-weight:600;
    padding:6px 8px; min-height:34px; font-size:13px;
    border:1px solid var(--sg-line); border-radius:8px; background:var(--sg-surface);
    color:var(--sg-ink); box-shadow:var(--sg-shadow-sm);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.sigav-carga input.sg-stock:hover{ border-color:#cfdae3; }
.sigav-carga input.sg-stock:focus{ outline:none; border-color:var(--sg-accent); box-shadow:var(--sg-ring); }

/* Botones de acción por fila */
.sigav-carga td.sg-actions{ text-align:right; white-space:nowrap; }
.sigav-carga .sg-icon-btn{
    display:inline-flex; align-items:center; justify-content:center;
    width:32px; height:32px; padding:0; margin-left:5px;
    border:1px solid var(--sg-line); border-radius:8px; background:var(--sg-surface);
    color:var(--sg-muted); cursor:pointer; box-shadow:var(--sg-shadow-sm);
    transition: transform .12s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.sigav-carga .sg-icon-btn:hover{ transform:translateY(-1px); }
.sigav-carga .sg-icon-btn:active{ transform:translateY(0); }
.sigav-carga .sg-icon-btn--edit:hover{ color:var(--sg-edit); background:var(--sg-edit-soft); border-color:#caddf7; }
.sigav-carga .sg-icon-btn--del:hover{ color:var(--sg-danger); background:var(--sg-danger-soft); border-color:#f3cccc; }
.sigav-carga .sg-icon-btn--ok:hover{ color:var(--sg-accent-700); background:var(--sg-accent-soft); border-color:#bfe7d5; }
.sigav-carga .sg-icon-btn .fa{ font-size:13px; }

/* ---------- Integración con DataTables ---------- */
.sigav-carga .dataTables_wrapper{ padding-top:6px; }
.sigav-carga .dt-buttons{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.sigav-carga .dt-button{
    font-family:var(--sg-sans) !important; font-size:12.5px !important; font-weight:600 !important;
    color:var(--sg-ink-2) !important; background:var(--sg-surface) !important;
    border:1px solid var(--sg-line) !important; border-radius:8px !important;
    padding:7px 14px !important; margin:0 !important; box-shadow:var(--sg-shadow-sm) !important;
    transition: all .15s ease !important;
}
.sigav-carga .dt-button:hover{
    color:var(--sg-accent-700) !important; background:var(--sg-accent-soft) !important;
    border-color:#bfe7d5 !important; transform:translateY(-1px);
}
.sigav-carga .dataTables_filter{ margin-bottom:14px; }
.sigav-carga .dataTables_filter label{
    font-size:12.5px; color:var(--sg-muted); font-weight:600;
    display:flex; align-items:center; gap:8px;
}
.sigav-carga .dataTables_filter input{
    min-height:38px; padding:8px 12px; font-size:13.5px; font-family:var(--sg-sans);
    border:1px solid var(--sg-line) !important; border-radius:var(--sg-r-sm);
    box-shadow:var(--sg-shadow-sm); margin-left:4px; min-width:240px;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.sigav-carga .dataTables_filter input:focus{ outline:none; border-color:var(--sg-accent) !important; box-shadow:var(--sg-ring); }
.sigav-carga .dataTables_info{ font-size:12.5px; color:var(--sg-muted); padding-top:14px; }
.sigav-carga .dataTables_paginate{ padding-top:12px; }
.sigav-carga .dataTables_paginate .paginate_button{
    padding:6px 12px !important; margin-left:4px; font-size:13px;
    border:1px solid var(--sg-line) !important; border-radius:8px !important;
    background:var(--sg-surface) !important; color:var(--sg-ink-2) !important;
    box-shadow:var(--sg-shadow-sm); cursor:pointer;
}
.sigav-carga .dataTables_paginate .paginate_button.current,
.sigav-carga .dataTables_paginate .paginate_button.current:hover{
    background:var(--sg-accent) !important; border-color:var(--sg-accent) !important;
    color:#fff !important; box-shadow:0 4px 10px -4px rgba(14,159,110,.5);
}
.sigav-carga .dataTables_paginate .paginate_button:hover{
    background:var(--sg-accent-soft) !important; border-color:#bfe7d5 !important; color:var(--sg-accent-700) !important;
}
.sigav-carga table.sg-table thead th{ outline:none; }
.sigav-carga table.dataTable thead .sorting,
.sigav-carga table.dataTable thead .sorting_asc,
.sigav-carga table.dataTable thead .sorting_desc{ cursor:pointer; }
</style>
@endsection
