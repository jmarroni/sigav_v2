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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Clientes</h1>
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

    <div class="block block-rounded">
    <div class="block-content" id="block-content">
        <div class="block-title" >
            Alta de Clientes
        </div>
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/cliente.save" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Raz&oacute;n Social (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="razon_social" id="razon_social" value="" placeholder="Empresa S.A." />
                    </div>
                    <div class="col-xs-4">
                        <label>Domicilio Legal (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="domicilio_legal" id="domicilio_legal" value="" placeholder="Calle altura, piso ..." />
                    </div>
                    <div class="col-xs-4">
                        <label>C&oacute;digo Postal (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="codigo_postal" id="codigo_postal" value="" placeholder="8500" />
                    </div>
                    <div class="col-xs-4">
                        <label>Tel&eacute;fono (*):</label>
                        <input type="phone" class="form-control numbers" name="telefono" id="telefono" value="" placeholder="+54 9 2920 534323" />
                    </div>
                    <div class="col-xs-4">
                        <label>Provincia (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="provincia" id="provincia" value="" placeholder="Rio Negro" />
                    </div>
                    <div class="col-xs-4">
                        <label>Localidad (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="localidad" id="localidad" value="" placeholder="Viedma" />
                    </div>
                    <div class="col-xs-4">
                        <label>CUIT (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="cuit" id="cuit" value="" placeholder="23282568519" />
                    </div>
                    <div class="col-xs-4">
                        <label>Condicion ante el IVA (*):</label>
                        <select class="form-control" name="condicion_iva" id="condicion_iva" >
                            <option value="0">Seleccione una opci&oacute;n</option>
                            <option value="1">Resp. Inscripto</option>
                            <option value="2">Monotributista</option>
                            <option value="3">Excento</option>
                            <option value="4">Cons. Final</option>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label>Representante Legal (*):</label>
                        <input type="text" class="form-control lettersNumbers" name="representante" id="representante" value="" placeholder="Juan Garay" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email (*):</label>
                        <input type="mail" class="form-control lettersNumbers" name="email_representante" id="email_representante" value="" placeholder="mail@mail.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Responsable de contrataci&oacute;n:</label>
                        <input type="text" class="form-control lettersNumbers" name="responsable_contratacion" id="responsable_contratacion" value="" placeholder="Juan Perez" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email:</label>
                        <input type="mail" class="form-control lettersNumbers" name="email_constratacion" id="email_constratacion" value="" placeholder="mail@responsable.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Responsable de Pagos:</label>
                        <input type="text" class="form-control lettersNumbers" name="responsable_pagos" id="responsable_pagos" value="" placeholder="Juan Gonzalez" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email:</label>
                        <input type="text" class="form-control lettersNumbers" name="email_pagos" id="email_pagos" value="" placeholder="email@pagos.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Horario de consulta pago a proveedores:</label>
                        <input type="text" class="form-control lettersNumbers" name="consulta_proveedores" id="consulta_proveedores" value="" placeholder="Martes - Viernes 8:30 a 12:00hs." />
                    </div>
                    <div class="col-xs-4">
                        <label>Horario de entregas y retiros:</label>
                        <input type="text" class="form-control lettersNumbers" name="entrega_retiros" id="entrega_retiros" value="" placeholder="Lunes, Miercoles y Viernes de 9 a 12hs." />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-8 col-xs-offset-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit" id="enviar">
                            <i class="fa fa-check push-5-r"></i>Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
     <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Clientes</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    @if (count($clientes)>0)
                    @foreach($clientes as $cliente)               
                    <tr id="<?php echo $cliente->id; ?>">
                        <td class="text-center">
                            <div style="width: 50px;">
                                <img class="img-responsive" src="/assets/img/photos/cliente.png" alt="">
                            </div>
                        </td>
                        <td>
                            <h4>{{$cliente->razon_social}} (<a href="javascript:void();" onclick="eliminarCliente('<?php echo $cliente->id; ?>')">Eliminar</a> , <a href="#" onclick="modificarCliente('<?php echo $cliente->id; ?>')">Modificar</a>)

                         </td>
                         <td>
                            <p class="remove-margin-b">Tel&eacute;fono: <span class="text-gray-dark"><?php echo $cliente->telefono ?></span></p>
                            <p>Mail: <span class="text-gray-dark"><?php echo $cliente->email_representante;?></span></p>
                        </td>
                 </tr>
               
                @endforeach
                 @else
                 <tr>
                    <td>
                        <label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No existen clientes registrados</label>
                    </td>
                </tr>
                 @endif
            </tbody>
        </table>
    </div>
</div>

<!-- END Clientes -->
</div>
<!-- END Clientes -->
@endsection
@section("scripts")
<script src="/assets/js/clientes/clientes_accion.js?v=<?php echo rand(); ?>"></script>    
@endsection
