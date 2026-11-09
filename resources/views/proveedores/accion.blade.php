@extends('layout.layout')
@section("body")
@php
    // Solo se enlazan URLs http(s): evita href="javascript:..." cargados en sitio_web.
    $urlSegura = function ($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://'.$url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    };
    $categoriasElegidas = array_map('intval', (array) old('categoria', []));
@endphp
<div class="content content-boxed sigav-app" id="abm-proveedores">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Proveedores</p>
        <h1>Proveedores</h1>
        <p>Artesanos y proveedores con su ficha pública. <b>{{ count($proveedores) }}</b> cargados.</p>
    </div>

    @if (!empty($aviso))
        <div class="sg-alert sg-alert--ok" id="add_success"><i class="fa fa-check-circle"></i>{{ $aviso }}</div>
    @endif
    @if ($errors->any())
        <div class="sg-alert sg-alert--err" role="alert">
            <i class="fa fa-exclamation-circle"></i>
            <span>@foreach ($errors->all() as $error){{ $error }} @endforeach</span>
        </div>
    @endif

    <!-- Formulario alta / modificación -->
    <section class="sg-card" id="card-form">
        <header class="sg-card__head">
            <div class="sg-card__title"><span class="sg-dot"></span><h3 id="form-titulo">Nuevo proveedor</h3></div>
            <p class="sg-card__hint">Los campos con <b>*</b> son obligatorios.</p>
        </header>
        <div class="sg-card__body">
            <form id="form-artesano" action="/proveedor.save" enctype="multipart/form-data" method="post" novalidate>
                @csrf
                <input type="hidden" value="{{ old('id_proveedor') }}" name="id_proveedor" id="id_proveedor"/>

                <div class="sg-fieldset">
                    <p class="sg-fieldset__label">Datos del proveedor</p>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="nombre">Nombre <span class="sg-req">*</span></label>
                            <input type="text" class="form-control letters" id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="apellido">Apellido <span class="sg-req">*</span></label>
                            <input type="text" class="form-control letters" id="apellido" name="apellido" value="{{ old('apellido') }}" placeholder="Apellido o razón social" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="telefono">Teléfono / Celular <span class="sg-req">*</span></label>
                            <input type="text" class="form-control numbers sg-mono-input" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="2920425672" maxlength="12" inputmode="numeric" />
                        </div>
                        <div class="sg-field">
                            <label for="direccion">Dirección <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="direccion" name="direccion" value="{{ old('direccion') }}" placeholder="Alvear 453 local 3" maxlength="200"/>
                        </div>
                        <div class="sg-field">
                            <label for="ciudad">Ciudad <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="ciudad" name="ciudad" value="{{ old('ciudad') }}" placeholder="Viedma" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="provincia">Provincia <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="provincia" name="provincia" value="{{ old('provincia') }}" placeholder="Río Negro" maxlength="70"/>
                        </div>
                        <div class="sg-field">
                            <label for="mail">Mail</label>
                            <input type="email" class="form-control" id="mail" name="mail" value="{{ old('mail') }}" placeholder="mail@ejemplo.com" maxlength="100" />
                        </div>
                        <div class="sg-field sg-span-2">
                            <label for="sitio_web">Sitio web <i>(url)</i></label>
                            <input type="text" class="form-control" id="sitio_web" name="sitio_web" value="{{ old('sitio_web') }}" placeholder="www.ejemplo.com" maxlength="200"/>
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <p class="sg-fieldset__label">Categorías de producción <span class="sg-req">*</span></p>
                    <div class="sg-checks" id="categorias">
                        @forelse($categorias as $categoria)
                            <label class="sg-check {{ $categoria->habilitada == 1 ? '' : 'is-off' }}" @if($categoria->habilitada != 1) title="Categoría deshabilitada" @endif>
                                <input type="checkbox" name="categoria[]" value="{{ $categoria->id }}" id="cat_{{ $categoria->id }}"
                                       @if(in_array((int) $categoria->id, $categoriasElegidas, true)) checked @endif />
                                {{ $categoria->nombre }}
                            </label>
                        @empty
                            <span class="sg-note">No hay categorías cargadas. <a href="/categoria">Crear una</a>.</span>
                        @endforelse
                    </div>
                </div>

                <div class="sg-fieldset">
                    <p class="sg-fieldset__label">Ficha pública</p>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field sg-span-all">
                            <label for="titulo">Título <span class="sg-req">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" value="{{ old('titulo') }}" placeholder="Ej: Tejidos en telar de la Patagonia" maxlength="150" />
                        </div>
                        <div class="sg-field sg-span-all">
                            <label for="descripcion">Descripción <span class="sg-req">*</span></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="2000" placeholder="Historia, técnica y productos del artesano">{{ old('descripcion') }}</textarea>
                        </div>
                        @for ($i = 1; $i <= 3; $i++)
                        <div class="sg-field">
                            <label for="imagen{{ $i }}">Imagen {{ $i }} <i>(opcional)</i></label>
                            <label class="sg-slot" id="slot_imagen{{ $i }}">
                                <span class="sg-slot__preview" id="preview_imagen{{ $i }}"></span>
                                <i class="fa fa-camera"></i>
                                <span class="sg-slot__label">Elegir imagen</span>
                                <input type="file" id="imagen{{ $i }}" name="imagen{{ $i }}" accept="image/jpeg,image/png,image/gif,image/bmp" />
                            </label>
                        </div>
                        @endfor
                    </div>
                    <p class="sg-note" style="margin-top:10px;">Subir una imagen en un lugar ocupado la reemplaza; los demás lugares no cambian.</p>
                </div>

                <div class="sg-form-actions">
                    <span class="sg-note" id="form-nota"></span>
                    <button type="button" class="sg-btn sg-btn--ghost sg-hidden" id="cancelar"><i class="fa fa-times"></i> Cancelar edición</button>
                    <button type="submit" class="sg-btn sg-btn--primary" id="enviar"><i class="fa fa-check"></i> <span>Guardar proveedor</span></button>
                </div>
            </form>
        </div>
    </section>

    <!-- Grilla -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title">
                <span class="sg-dot"></span><h3>Proveedores cargados</h3>
                <span class="sg-count" id="contador">{{ count($proveedores) }}</span>
            </div>
            <div class="sg-search">
                <i class="fa fa-search"></i>
                <input type="search" class="form-control" id="buscar" placeholder="Buscar por nombre, ciudad o categoría" aria-label="Buscar proveedor" />
            </div>
        </header>
        <div class="sg-card__body sg-table-wrap">
            <table class="sg-table" id="tabla-proveedores">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Categorías</th>
                        <th class="sg-num">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($proveedores as $proveedor)
                    @php
                        $nombreCompleto = trim($proveedor->nombre.' '.$proveedor->apellido);
                        $susCategorias = $categoriasPorProveedor->get($proveedor->id, collect())->pluck('nombre');
                        $imagen = optional($proveedor->imagenes->first())->imagen_url;
                        $web = $urlSegura($proveedor->sitio_web);
                    @endphp
                    <tr id="{{ $proveedor->id }}"
                        data-buscar="{{ mb_strtolower($nombreCompleto.' '.$proveedor->titulo.' '.$proveedor->ciudad.' '.$susCategorias->implode(' ')) }}">
                        <td>
                            <div class="sg-ident">
                                <span class="sg-thumb" aria-hidden="true">
                                    @if ($imagen)
                                        <img src="{{ $imagen }}" alt="" loading="lazy" width="48" height="48">
                                    @else
                                        {{ mb_strtoupper(mb_substr($proveedor->nombre, 0, 1)) }}
                                    @endif
                                </span>
                                <div>
                                    <div class="sg-cell-title">{{ $nombreCompleto }}</div>
                                    @if ($proveedor->titulo)
                                        <div class="sg-cell-sub">{{ $proveedor->titulo }}</div>
                                    @endif
                                    <div class="sg-cell-sub">
                                        {{ collect([$proveedor->ciudad, $proveedor->provincia])->filter()->implode(', ') ?: 'Sin ubicación' }}
                                        · cargado por {{ $proveedor->usuario ?: '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="sg-contact">
                                @if ($proveedor->telefono)<span><i class="fa fa-phone"></i>{{ $proveedor->telefono }}</span>@endif
                                @if ($proveedor->mail)<span><i class="fa fa-envelope-o"></i><a href="mailto:{{ $proveedor->mail }}">{{ $proveedor->mail }}</a></span>@endif
                                @if ($web)<span><i class="fa fa-globe"></i><a href="{{ $web }}" target="_blank" rel="noopener noreferrer">{{ $proveedor->sitio_web }}</a></span>@endif
                                @if (!$proveedor->telefono && !$proveedor->mail && !$web)<span class="sg-muted">Sin datos de contacto</span>@endif
                            </div>
                        </td>
                        <td>
                            <div class="sg-chips">
                                {{-- Muchos proveedores tienen decenas de categorías: se muestran 4 y el resto en el tooltip. --}}
                                @forelse ($susCategorias->take(4) as $nombreCategoria)
                                    <span class="sg-chip">{{ $nombreCategoria }}</span>
                                @empty
                                    <span class="sg-muted">—</span>
                                @endforelse
                                @if ($susCategorias->count() > 4)
                                    <span class="sg-chip sg-chip--more" title="{{ $susCategorias->slice(4)->implode(', ') }}">+{{ $susCategorias->count() - 4 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="sg-row-actions">
                                <button type="button" class="sg-icon-btn sg-icon-btn--edit" data-accion="editar" data-id="{{ $proveedor->id }}" title="Modificar" aria-label="Modificar {{ $nombreCompleto }}"><i class="fa fa-pencil"></i></button>
                                <button type="button" class="sg-icon-btn sg-icon-btn--del" data-accion="eliminar" data-id="{{ $proveedor->id }}" data-nombre="{{ $nombreCompleto }}" title="Eliminar" aria-label="Eliminar {{ $nombreCompleto }}"><i class="fa fa-trash-o"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="sg-empty-row"><td colspan="4"><div class="sg-empty"><i class="fa fa-users"></i>Todavía no hay proveedores cargados.</div></td></tr>
                @endforelse
                    <tr class="sg-hidden" id="sin-resultados"><td colspan="4"><div class="sg-empty"><i class="fa fa-search"></i>Ningún proveedor coincide con la búsqueda.</div></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
@section("scripts")
<script src="/assets/js/abm_comun.js?v=1.00"></script>
<script src="/assets/js/proveedores/proveedores_accion.js?v=1.11"></script>
@endsection
