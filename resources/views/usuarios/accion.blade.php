@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
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
    <!-- Usuarios -->
    <div class="block block-rounded">
        <div class="block-header">
            <div class="col-xs-8" > 
                <h3 class="block-title">USUARIOS</h3>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos">
                        @if (count($usuarios)>0)
                        @foreach($usuarios as $usuario)
                        <tr id="usuario_{{$usuario->id}}">
                            <td class="text-center">
                                <div style="width: 180px;">
                                    <img class="img-responsive" src="/assets/img/photos/no-image-featured-image.png"
                                    alt="">
                                </div>
                            </td>
                            <td>
                                <h4>Usuario: {{$usuario->nombre}} </h4>
                                <p class="remove-margin-b">Nombre y Apellido: <b><?php echo $usuario->nombre.", ".$usuario->apellido; ?></b></p>
                                <button onclick="modificarUsuario('{{$usuario->id}}');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                    <i class="fa fa-check push-5-r"></i>Modificar
                                </button>
                                <button onclick="eliminarUsuario('{{$usuario->id}}');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                    <i class="fa fa-times push-5-r"></i>Eliminar
                                </button>
                            </td>
                            <td>
                                <p class="remove-margin-b">Tel: <span class="text-gray-dark">{{$usuario->telefono}}</span>
                                </p>
                                <p class="remove-margin-b">Sucursal: <span class="text-gray-dark">{{$usuario->nombre_sucursal}}</span></p>
                                <p class="remove-margin-b">Rol: <span class="text-gray-dark">{{$usuario->nombre_rol}}</span>
                            </p>
                        </td>                       
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td>
                            <label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay usuarios registrados</label>
                        </td>
                    </tr>
                    @endif

                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- END Usuarios -->
</div>
@endsection
@section("scripts")
<script src="/assets/js/usuarios/usuarios_accion.js?v=1.08"></script>  
@endsection