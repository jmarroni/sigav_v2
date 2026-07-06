@extends('layout.layout');
@section("body")
<link rel="stylesheet" href="/assets/css/sigav-carga.css?v=1">
<div class="sigav-carga">

    <!-- Alta / edición de proveedor -->
    <section class="sg-card sg-card--form">
        <header class="sg-card__head">
            <div class="sg-card__title">
                <span class="sg-dot"></span>
                <h3>Datos del Proveedor</h3>
            </div>
            <p class="sg-card__hint">Los campos marcados con <b>(*)</b> son obligatorios</p>
        </header>

        <div class="sg-card__body">
            <form class="sg-form form-horizontal" id="form-artesano" action="/proveedor.save" enctype="multipart/form-data" method="post">
                <input type="hidden" value="" name="id" id="id" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" value="" name="id_proveedor" id="id_proveedor"/>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Identificación</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="nombre">Nombre <span class="sg-req">*</span></label>
                            <input type="text" class="form-control letters" id="nombre" name="nombre" value="" placeholder="Nombre del proveedor" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="apellido">Apellido <span class="sg-req">*</span></label>
                            <input type="text" class="form-control letters" id="apellido" name="apellido" value="" placeholder="Apellido del proveedor" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="direccion">Dirección <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="direccion" name="direccion" value="" placeholder="Alvear 453 local 3" maxlength="200"/>
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Ubicación y contacto</span>
                    <div class="sg-grid sg-grid--3">
                        <div class="sg-field">
                            <label for="ciudad">Ciudad <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="ciudad" name="ciudad" value="" placeholder="Viedma" maxlength="70" />
                        </div>
                        <div class="sg-field">
                            <label for="provincia">Provincia <span class="sg-req">*</span></label>
                            <input type="text" class="form-control lettersNumbers" id="provincia" name="provincia" value="" placeholder="Río Negro" maxlength="70"/>
                        </div>
                        <div class="sg-field">
                            <label for="telefono">Teléfono / Celular <span class="sg-req">*</span></label>
                            <input type="text" class="form-control numbers" id="telefono" name="telefono" value="" placeholder="2920 425672" maxlength="12"/>
                        </div>
                        <div class="sg-field">
                            <label for="mail">Mail</label>
                            <input type="text" class="form-control lettersNumbers" id="mail" name="mail" value="" placeholder="mail@mail.com" onblur="validarMail();" />
                        </div>
                        <div class="sg-field">
                            <label for="sitio_web">Sitio web <i>(url)</i></label>
                            <input type="url" class="form-control lettersNumbers" id="sitio_web" name="sitio_web" value="" placeholder="www.ejemplo.com"/>
                        </div>
                    </div>
                </div>

                <div class="sg-fieldset">
                    <span class="sg-fieldset__label">Categorías de producción <span class="sg-req">*</span></span>
                    <div class="sg-grid">
                        <div class="sg-field">
                            <label for="categoria">Seleccione una o más categorías <i>(Ctrl para varias)</i></label>
                            <select class="form-control" id="categoria" multiple name="categoria[]">
                                <option value="0" selected></option>
                                @foreach($categorias as $categoria)
                                <option value="{{$categoria->id}}">{{$categoria->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sg-form__actions">
                    <button id="enviar" class="btn btn-primary sg-btn sg-btn--primary" type="submit">
                        <i class="fa fa-check"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- No se pudo eliminar -->
    <div id="erroreliminar" class="alert alert-danger text-center hidden" role="alert" style="position: fixed; bottom: 20px; width: 100%; z-index: 1050;">
        <p style="font-weight: bold; margin:0;">No se puede eliminar este proveedor <small style="font-weight: normal;">— debe eliminar todos sus productos primero</small></p>
    </div>

    <!-- Grilla de proveedores -->
    <section class="sg-card sg-card--table">
        <header class="sg-card__head sg-card__head--table">
            <div class="sg-card__title">
                <span class="sg-dot"></span>
                <h3>Proveedores</h3>
                <span class="sg-count">{{ count($proveedores) }}</span>
            </div>
        </header>

        <div class="sg-card__body sg-table-wrap">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Categorías</th>
                        <th>Ingresado por</th>
                        <th class="sg-actions-th">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($proveedores) > 0)
                        @foreach($proveedores as $proveedor)
                        <tr id="{{ $proveedor->id }}">
                            <td class="sg-strong">{{ $proveedor->nombre }} {{ $proveedor->apellido }}</td>
                            <td>
                                @if($proveedor->telefono)<div><i class="fa fa-phone sg-muted"></i> {{ $proveedor->telefono }}</div>@endif
                                @if($proveedor->mail)<div class="sg-muted"><i class="fa fa-envelope-o"></i> {{ $proveedor->mail }}</div>@endif
                                @if($proveedor->sitio_web)<div><a href="{{ $proveedor->sitio_web }}" target="_blank"><i class="fa fa-globe"></i> {{ $proveedor->sitio_web }}</a></div>@endif
                            </td>
                            <td>
                                @php $tieneCat = false; @endphp
                                @foreach($RCategoriasProveedor as $categoriaProveedor)
                                    @if($categoriaProveedor->proveedor_id == $proveedor->id)
                                        <span class="sg-tag">{{ $categoriaProveedor->nombre }}</span>
                                        @php $tieneCat = true; @endphp
                                    @endif
                                @endforeach
                                @if(!$tieneCat)<span class="sg-tag sg-tag--warn">Sin categoría</span>@endif
                            </td>
                            <td class="sg-muted">{{ $proveedor->usuario }}</td>
                            <td class="sg-actions">
                                <button type="button" title="Modificar" class="btn sg-icon-btn sg-icon-btn--edit" onclick="modificarArtesano('{{ $proveedor->id }}')"><i class="fa fa-pencil"></i></button>
                                <button type="button" title="Eliminar" class="btn sg-icon-btn sg-icon-btn--del" onclick="eliminarArtesano('{{ $proveedor->id }}')"><i class="fa fa-times"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" style="text-align:center; padding:22px; font-weight:600; color:#8a97a0;">No existen proveedores registrados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection
@section("scripts")
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">
<script src="/assets/js/proveedores/proveedores_accion.js?v=1.08"></script>
@endsection
