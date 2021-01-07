@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">&Aacute;rea Carga</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se cargaron <?php echo count($productos); ?> productos</h2>
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

    <div class="block block-rounded">
        <div class="block-content">
            <form class="form-horizontal" id="form-artesano" action="/proveedor.save" enctype="multipart/form-data" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" value="" name="id_proveedor" id="id_proveedor"/>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Nombre (*)</label>
                        <input type="text" class="form-control letters" id="nombre" name="nombre" value="" placeholder="Nombre del Proveedor" maxlength="70" />
                    </div>
                    <div class="col-xs-4">
                        <label>Apellido (*)</label>
                        <input type="text" class="form-control letters" id="apellido" name="apellido" value="" placeholder="Apelllido del Proveedor" maxlength="70" />
                    </div>
                    <div class="col-xs-4">
                        <label>Direcci&oacute;n (*)</label>
                        <input type="text" class="form-control lettersNumbers" id="direccion" name="direccion" value="" placeholder="Alvear 453 local 3" maxlength="200"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label>Ciudad (*)</label>
                        <input type="text" class="form-control lettersNumbers" id="ciudad" name="ciudad" value="" placeholder="Viedma" maxlength="70" />
                    </div>
                    <div class="col-xs-4">
                        <label>Provincia (*)</label>
                        <input type="text" class="form-control lettersNumbers" id="provincia" name="provincia" value="" placeholder="Rio Negro" maxlength="70"/>
                    </div>
                    <div class="col-xs-4">
                        <label>Tel&eacute;fono / Celular (*)</label>
                        <input type="text" class="form-control numbers" id="telefono" name="telefono" value="" placeholder="2920 425672" maxlength="12"/>
                    </div>

                </div>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label>Categor&iacute;as de Producci&oacute;n</label>
                         <select class="form-control" id="categoria" multiple name="categoria[]">
                            @foreach($categorias as $categoria)
                            <option value={{$categoria->id}}>{{$categoria->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label>Mail</label>
                        <input type="text" class="form-control lettersNumbers" id="mail" name="mail" value="" placeholder="mail@mail.com" onblur="validarMail();" />
                    </div>
                    <div class="col-xs-4">
                        <label>Sitio web <small>(url)</small></label>
                        <input type="url" class="form-control lettersNumbers" id="sitio_web" name="sitio_web" value="" placeholder="www.ejemplo.com"/>
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" id="enviar" style="width: 100%;margin-top: 7%;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensaje no se pudo eliminar artesano -->
    <div id="erroreliminar" class="alert alert-danger text-center hidden" role="alert" style="position: fixed; bottom: 20px; width: 100%;">
        <p style="font-weight: bold;">No se puede eliminar este proveedor <small style="font-weight: normal;">Debe eliminar todos sus productos primero</small></p>
    </div>

<!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Proveedores</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    @foreach($proveedores as $proveedor)
                    @if (count($proveedores)>0)
                    <tr id="<?php echo $proveedor->id; ?>">
                        <td class="text-center">
                            <div style="width: 180px;">
                                <img class="img-responsive" src="/assets/img/photos/no-image-featured-image.png" alt="">
                            </div>
                        </td>
                        <td>
                            <h4>{{$proveedor->nombre}} {{$proveedor->apellido}} (<a href="javascript:void();" onclick="eliminarArtesano('<?php echo $proveedor->id; ?>')">Eliminar</a> , <a href="#" onclick="modificarArtesano('<?php echo $proveedor->id; ?>')">Modificar</a>)

                             <p class="remove-margin-b">Ingresado por: {{$proveedor->usuario}} </p>
                         </td>
                         <td>
                            <p class="remove-margin-b">Tel&eacute;fono: <span class="text-gray-dark"><?php echo $proveedor->telefono ?></span></p>
                            <p>Mail: <span class="text-gray-dark"><?php echo $proveedor->mail;?></span></p>
                            <p>Sitio web: <a href="<?php echo $proveedor->sitio_web; ?>" target="_blank" class="text-gray-dark">{{$proveedor->sitio_web}} </a></p> 
                        </td>
                        <td class="text-center">
                            <span class="text-gray-dark" >Categor&iacute;as:<br>
                             @if (count($RCategoriasProveedor)>0)
                                 @foreach($RCategoriasProveedor as $categoriaProveedor) 
                                  @if ($categoriaProveedor->proveedor_id==$proveedor->id)                     
                                  {{ $categoriaProveedor->nombre}}
                                   @endif
                                 @endforeach
                             @endif
                         </span>
                     </td>
                 </tr>


                 @else
                 <tr>
                    <td>
                        <label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay ventas en el d&iacute;a de hoy</label>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- END Proveedores -->
</div>
<!-- END Proveedores -->
@endsection
@section("scripts")
<script src="/assets/js/proveedores/proveedores_accion.js?v=1.08"></script>    
@endsection
