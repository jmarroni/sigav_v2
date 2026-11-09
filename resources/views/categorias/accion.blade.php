@extends('layout.layout')
@section("body")
@php
    $habilitadas = $categorias->where('habilitada', 1)->count();
@endphp
<div class="content content-boxed sigav-app" id="abm-categorias">

    <!-- Hero -->
    <div class="sg-hero">
        <p class="sg-hero__eyebrow">Proveedores</p>
        <h1>Categorías</h1>
        <p>Clasificación de productos y de la producción de cada proveedor. <b>{{ $habilitadas }}</b> habilitadas de <b>{{ count($categorias) }}</b>.</p>
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
            <div class="sg-card__title"><span class="sg-dot"></span><h3 id="form-titulo">Nueva categoría</h3></div>
            <p class="sg-card__hint">Los campos con <b>*</b> son obligatorios.</p>
        </header>
        <div class="sg-card__body">
            <form id="form-categoria" action="/categoria.save" method="post" novalidate>
                @csrf
                <input type="hidden" name="id_categoria" id="id_categoria" value="{{ old('id_categoria') }}"/>
                <div class="sg-grid sg-grid--3">
                    <div class="sg-field sg-span-2">
                        <label for="nombre">Nombre <span class="sg-req">*</span></label>
                        <input type="text" class="form-control lettersNumbers" name="nombre" id="nombre" value="{{ old('nombre') }}" placeholder="Ej: Artesanía en lana" maxlength="100" autocomplete="off" />
                    </div>
                    <div class="sg-field">
                        <label for="abreviatura">Abreviatura <span class="sg-req">*</span> <i>(se usa en los códigos)</i></label>
                        <input type="text" class="form-control sg-mono-input lettersNumbers" name="abreviatura" id="abreviatura" value="{{ old('abreviatura') }}" placeholder="ALAN" maxlength="20" autocomplete="off" />
                    </div>
                </div>
                <div class="sg-form-actions">
                    <span class="sg-note" id="form-nota"></span>
                    <button type="button" class="sg-btn sg-btn--ghost sg-hidden" id="cancelar"><i class="fa fa-times"></i> Cancelar edición</button>
                    <button type="submit" class="sg-btn sg-btn--primary" id="enviar"><i class="fa fa-check"></i> <span>Guardar categoría</span></button>
                </div>
            </form>
        </div>
    </section>

    <!-- Grilla -->
    <section class="sg-card">
        <header class="sg-card__head">
            <div class="sg-card__title">
                <span class="sg-dot"></span><h3>Categorías cargadas</h3>
                <span class="sg-count" id="contador">{{ count($categorias) }}</span>
            </div>
            <div class="sg-search">
                <i class="fa fa-search"></i>
                <input type="search" class="form-control" id="buscar" placeholder="Buscar por nombre o abreviatura" aria-label="Buscar categoría" />
            </div>
        </header>
        <div class="sg-card__body sg-table-wrap">
            <table class="sg-table" id="tabla-categorias">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Abreviatura</th>
                        <th>Estado</th>
                        <th class="sg-num">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($categorias as $categoria)
                    <tr id="{{ $categoria->id }}" class="{{ $categoria->habilitada == 1 ? '' : 'is-off' }}"
                        data-buscar="{{ mb_strtolower($categoria->nombre.' '.$categoria->abreviatura) }}">
                        <td>
                            <div class="sg-ident">
                                <span class="sg-thumb" aria-hidden="true">{{ mb_strtoupper(mb_substr($categoria->nombre, 0, 1)) }}</span>
                                <div>
                                    <div class="sg-cell-title">{{ $categoria->nombre }}</div>
                                    <div class="sg-cell-sub">Cargada por {{ $categoria->usuario ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="sg-chip sg-chip--code">{{ $categoria->abreviatura }}</span></td>
                        <td>
                            <button type="button" class="sg-pill {{ $categoria->habilitada == 1 ? 'sg-pill--on' : 'sg-pill--off' }}"
                                    id="status_{{ $categoria->id }}" data-accion="estado" data-id="{{ $categoria->id }}"
                                    title="Click para {{ $categoria->habilitada == 1 ? 'deshabilitar' : 'habilitar' }}">
                                {{ $categoria->habilitada == 1 ? 'Habilitada' : 'Deshabilitada' }}
                            </button>
                        </td>
                        <td>
                            <div class="sg-row-actions">
                                <button type="button" class="sg-icon-btn sg-icon-btn--edit" data-accion="editar" data-id="{{ $categoria->id }}" title="Modificar" aria-label="Modificar {{ $categoria->nombre }}"><i class="fa fa-pencil"></i></button>
                                <button type="button" class="sg-icon-btn sg-icon-btn--del" data-accion="eliminar" data-id="{{ $categoria->id }}" data-nombre="{{ $categoria->nombre }}" title="Eliminar" aria-label="Eliminar {{ $categoria->nombre }}"><i class="fa fa-trash-o"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="sg-empty-row"><td colspan="4"><div class="sg-empty"><i class="fa fa-tags"></i>Todavía no hay categorías cargadas.</div></td></tr>
                @endforelse
                    <tr class="sg-hidden" id="sin-resultados"><td colspan="4"><div class="sg-empty"><i class="fa fa-search"></i>Ninguna categoría coincide con la búsqueda.</div></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
@section("scripts")
<script src="/assets/js/abm_comun.js?v=1.00"></script>
<script src="/assets/js/categorias/categorias_accion.js?v=1.09"></script>
@endsection
