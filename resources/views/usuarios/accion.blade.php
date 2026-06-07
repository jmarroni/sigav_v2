@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
    .user-avatar {
        display: inline-flex; align-items: center; justify-content: center;
        width: 54px; height: 54px; border-radius: 50%;
        color: #fff; font-size: 20px; font-weight: 700; letter-spacing: .5px;
        box-shadow: 0 4px 10px rgba(0,0,0,.18);
    }
    .user-handle {
        display: inline-block;
        font-family: Menlo, Consolas, monospace; font-size: 12px; line-height: 18px;
        color: #fff; background: #647581; border-radius: 10px; padding: 0 8px;
    }
    /* Filas flex: tarjetas de igual alto pero ajustadas al contenido (sin height:100%). */
    #lista-usuarios { display: flex; flex-wrap: wrap; }
    .usuario-card { display: flex; margin-bottom: 18px; }
    .usuario-card .block {
        width: 100%; margin-bottom: 0;
        display: flex; flex-direction: column;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .usuario-card .block-content {
        padding: 15px 15px 12px;
        display: flex; flex-direction: column; flex: 1 1 auto;
    }
    .usuario-card .block:hover {
        box-shadow: 0 10px 26px rgba(0,0,0,.13); transform: translateY(-3px);
    }
    #filtro-usuarios { width: 220px; display: inline-block; }
</style>
@section("body")
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Usuarios</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp"></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Section -->

    @if($errors->any())
        <div class="alert alert-danger">
            <h4 class="font-w600" style="margin-top:0;"><i class="fa fa-exclamation-triangle push-5-r"></i>No se pudo guardar</h4>
            <ul style="margin-bottom:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <?php if (isset($_GET["mensaje"])){ ?>
        <div class="block block-rounded" id="add_success" style="background-color: #46c37b !important;color:white;">
            <div class="block-header">
                <div class="col-xs-12 bg-success" id="nombre-devuelto"><?php echo base64_decode($_GET["mensaje"]); ?></div>
            </div>
        </div>
    <?php } ?>

    <!-- Usuarios -->
    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" id="form-usuario" action="/usuario.save" enctype="multipart/form-data" method="post" >
                <input type="hidden" value="" name="id_usuario" id="id_usuario" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Usuario (*)</label>
                        <input type="text" class="form-control lettersNumbers" name="usuario" id="usuario" value="" placeholder="Sin espacios" />
                    </div>
                    <div class="col-xs-4">
                        <label>Clave (*)</label>
                        <input type="password" class="form-control" name="clave" id="clave" value="" placeholder="Sin espacios" />
                    </div>
                    <div class="col-xs-4">
                        <label>Rol (*)</label>
                        <select class="form-control" name="rol" id="rol" >
                            <option value="0" selected>Seleccione un rol</option>
                            @if (count($roles)>0)
                            @foreach($roles as $rol)
                            <option value="{{$rol->id}}">{{$rol->nombre}}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                </div><div  class="form-group">
                    <div class="col-xs-6">
                        <label>Nombre Completo (*)</label>
                        <input type="text" class="form-control letters" name="nombre" id="nombre" value="" placeholder="Juan Pablo" />
                    </div>
                    <div class="col-xs-6">
                        <label>Apellido (*)</label>
                        <input type="text" class="form-control letters" name="apellido" id="apellido" value="" placeholder="Marroni" />
                    </div>
                    
                </div>
                <div class="form-group">

                    <div class="col-xs-4">
                        <label>Teléfono (*)</label>
                        <input type="text" class="form-control numbers" name="telefono" placeholder="+5492920535353" id="telefono" value="">

                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Sucursal (*)</label>
                        <select class="form-control" name="sucursales" id="sucursales" >
                            <option value="0" selected>Seleccione una sucursal</option>
                            @if (count($sucursales)>0)
                            @foreach($sucursales as $sucursal)
                            <option value="{{$sucursal->id}}">{{$sucursal->nombre}}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" id="enviar" style="width:98%;margin-top:25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Listado de usuarios -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">
                <i class="fa fa-users text-muted push-5-r"></i> Usuarios
                <span class="label label-primary" style="margin-left:6px;">{{ count($usuarios) }}</span>
            </h3>
            <div class="block-options">
                <input type="text" id="filtro-usuarios" class="form-control input-sm" placeholder="Filtrar por login, nombre…">
            </div>
        </div>
        <div class="block-content">
            @if (count($usuarios) > 0)
            <div class="row" id="lista-usuarios">
                @foreach($usuarios as $usuario)
                    @php
                        $ini = strtoupper(mb_substr(trim($usuario->nombre ?? ''), 0, 1).mb_substr(trim($usuario->apellido ?? ''), 0, 1));
                        if ($ini === '') { $ini = strtoupper(mb_substr(trim($usuario->usuario ?? '?'), 0, 2)); }
                        $paleta = ['#5c80d1','#46c37b','#44b6ae','#e9573f','#f0ad4e','#70b9eb','#d26a5c','#7a6fbe','#3aa99e'];
                        $color = $paleta[$usuario->id % count($paleta)];
                        $nombreCompleto = trim(($usuario->nombre ?? '').' '.($usuario->apellido ?? '')) ?: 'Sin nombre';
                    @endphp
                    <div class="col-xs-12 col-sm-6 col-lg-4 usuario-card" id="usuario_{{$usuario->id}}"
                         data-buscar="{{ strtolower($usuario->usuario.' '.$usuario->nombre.' '.$usuario->apellido) }}">
                        <div class="block block-rounded block-bordered">
                            <div class="block-content">
                                <div class="media">
                                    <div class="media-left">
                                        <span class="user-avatar" style="background:{{ $color }};">{{ $ini }}</span>
                                    </div>
                                    <div class="media-body">
                                        <h4 class="font-w600" style="margin:0 0 4px;">{{ $nombreCompleto }}</h4>
                                        <span class="user-handle">{{ '@'.($usuario->usuario ?: 's/login') }}</span>
                                        @if($usuario->nombre_rol)
                                            <span class="label" style="background:{{ $color }};margin-left:6px;">{{ $usuario->nombre_rol }}</span>
                                        @else
                                            <span class="label label-default" style="margin-left:6px;">Sin rol</span>
                                        @endif
                                    </div>
                                </div>

                                <hr style="margin:10px 0;">

                                <p class="remove-margin-b">
                                    <i class="fa fa-building-o fa-fw text-muted push-5-r"></i>{{ $usuario->nombre_sucursal ?: 'Sin sucursal asignada' }}
                                </p>
                                <p class="remove-margin-b" style="margin-top:4px;">
                                    <i class="fa fa-phone fa-fw text-muted push-5-r"></i>{{ $usuario->telefono ?: '—' }}
                                </p>

                                <div style="margin-top:auto;padding-top:12px;">
                                    <button onclick="modificarUsuario('{{$usuario->id}}');" class="btn btn-xs btn-rounded btn-primary" type="button">
                                        <i class="fa fa-pencil push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminarUsuario('{{$usuario->id}}');" class="btn btn-xs btn-rounded btn-danger" type="button">
                                        <i class="fa fa-trash push-5-r"></i>Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div id="sin-resultados" class="text-center push-30 text-muted" style="display:none;">
                <i class="fa fa-search fa-2x push-10"></i>
                <p class="font-w600">Ningún usuario coincide con el filtro</p>
            </div>
            @else
            <div class="text-center push-30 text-muted">
                <i class="fa fa-user-times fa-3x push-10"></i>
                <p class="font-w600">No hay usuarios registrados</p>
            </div>
            @endif
        </div>
    </div>
</div>
<!-- END Usuarios -->
</div>
@endsection
@section("scripts")
<script src="/assets/js/usuarios/usuarios_accion.js?v=1.08"></script>
<script>
    (function () {
        var input = document.getElementById('filtro-usuarios');
        if (!input) return;
        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var visibles = 0;
            document.querySelectorAll('#lista-usuarios .usuario-card').forEach(function (card) {
                var match = card.getAttribute('data-buscar').indexOf(q) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) visibles++;
            });
            var vacio = document.getElementById('sin-resultados');
            if (vacio) vacio.style.display = visibles === 0 ? '' : 'none';
        });
    })();
</script>
@endsection