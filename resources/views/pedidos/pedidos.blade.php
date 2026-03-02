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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Pedidos</h1>
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

    <div class="block block-rounded" id="add_success" style="display: none;background-color: #46c37b !important;color:white;">
        <div class="block-header">
            <div class="col-xs-12 bg-success" id="nombre-devuelto"></div>
        </div>
    </div>
    <div class="block block-rounded" id="add_success_error" style="display: none;background-color: #d26a5c !important;color:white;">
        <div class="block-header">
            <div class="col-xs-12 bg-danger" id="nombre-devuelto-error"></div>
        </div>
    </div>
    <form class="form-horizontal" id="pedido-form" method="post" >
      <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
      <input type="hidden" name="detalleProductos" id="detalleProductos"/>
      <input type="hidden" name="montoTotal" id="montoTotal" value="0" />
      <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <div class="block-title" >
                Datos del cliente
            </div>
            <div class="block-content" id="block-content">

                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">   
                    <div class="col-xs-12">
                    <label>Tipo de documento(*):</label>  
                    </div>             
                <div class="col-xs-4">
                    <label class="radio-inline" for="example-inline-efectivo">
                        <input type="radio" name="tipodocumento"  id="tipodocumento"  value="1"> Nota de Pedido
                    </label>
                    <label class="radio-inline" for="example-inline-debito">
                        <input type="radio" name="tipodocumento" id="tipodocumento" value="2"> Presupuesto
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Paciente (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="paciente" autocomplete="false" id="paciente" value="" placeholder="Empresa S.A." />
                    <input class="form-control" type="hidden" id="paciente_id" name="paciente_id" placeholder="" value="">
                </div>
                <div class="col-xs-4">
                    <label>Domicilio (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="domicilio" id="domicilio" value="" placeholder="Calle altura, piso ..." />
                </div>
                <div class="col-xs-4">
                    <label>Tel&eacute;fono (*):</label>
                    <input type="phone" class="form-control numbers" name="telefono" id="telefono" value="" placeholder="+54 9 2920 534323" />
                </div>
                <div class="col-xs-4">
                    <label>Doctor (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="doctor" id="doctor" value="" placeholder="Rio Negro" />
                </div>
                <div class="col-xs-4">
                    <label>Obra social (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="obra_social" id="obra_social" value="" placeholder="Rio Negro" />
                </div>
                <div class="col-xs-4">
                    <label>Número de asociado (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="numero_asociado" id="numero_asociado" value="" placeholder="Rio Negro" />
                </div>
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Fecha R/P (*):</label>
                    <input class="form-control" type="date" name="fecha_recepcion" id="fecha_recepcion" placeholder="dd/mm/yyyy" value="">
                </div>
                <div class="col-xs-4">
                    <label>Pedido (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="pedido" id="pedido" value="" placeholder="Viedma" />
                </div>
                <div class="col-xs-4">
                    <label>Retira (*):</label>
                    <input type="text" class="form-control lettersNumbers" name="retira" id="retira" value="" placeholder="Viedma" />
                </div>
            </div>
        </div>
    </div>
</div>
<div class="block block-rounded">
    <div class="block-content" style="align-content:center; ">
        <div class="block-title" >
            Datos del pedido
        </div>
        <br>
        <table border="3" style="margin:auto;" class="table table-hover table-vcenter" id="tablaDetalle">
            <thead>

             <tr>
                <th style="width: 40px;"></th>
                <th style="width: 40px;"></th>
                <th style="width:100px;">ESF.</th>
                <th style="width:100px;">CIL.</th>
                <th style="width: 100px;">EJE</th>
                <th style="width: 100px;">D.I.P</th>
                <th style="width: 100px;">PRODUCTO</th>
                <th style="width: 100px;">ARMAZON</th>
            </tr>
        </thead> 
        <tbody> 
            <tr>
                <td rowspan="2">L</td>
                <td>D</td>
                <td contenteditable='true' id="l_d_esf" name="l_d_esf" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_d_cil" name="l_d_cil" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_d_eje" name="l_d_eje" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_d_dip" name="l_d_dip" style="text-transform: uppercase;"></td>
                <td contenteditable='true' rowspan="2" id="l_producto" name="l_producto" style="text-transform: uppercase;"></td>
                <td contenteditable='true' rowspan="2" id="l_armazon" name="l_armazon" style="text-transform: uppercase;"></td>
            </tr>
            <tr>
                <td>I</td>
                <td contenteditable='true' id="l_i_esf" name="l_i_esf" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_i_cil" name="l_i_cil" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_i_eje" name="l_i_eje" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="l_i_dip" name="l_i_dip" style="text-transform: uppercase;"></td>
            </tr>
            <tr>
                <td rowspan="2">C</td>
                <td>D</td>
                <td contenteditable='true' id="c_d_esf" name="c_d_esf" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_d_cil" name="c_d_cil" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_d_eje" name="c_d_eje" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_d_dip" name="c_d_dip" style="text-transform: uppercase;"></td>
                <td contenteditable='true' rowspan="2" id="c_producto" name="c_producto" style="text-transform: uppercase;"></td>
                <td contenteditable='true' rowspan="2" id="c_armazon" name="c_armazon" style="text-transform: uppercase;"></td>
            </tr>
            <tr>
                <td>I</td>
                <td contenteditable='true' id="c_i_esf" name="c_i_esf" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_i_cil" name="c_i_cil" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_i_eje" name="c_i_eje" style="text-transform: uppercase;"></td>
                <td contenteditable='true' id="c_i_dip" name="c_i_dip" style="text-transform: uppercase;"></td>
            </tr>

        </tbody> 
    </table>
    <br>
</div>
</div>

<div class="block block-rounded">
    <div class="block-content">
        <div class="block-title" >
            Importe del trabajo(*)
        </div>
         <h3 class="block-title">Total: $ <label id="totalFactura" style="font-size:15px;">0.00</label>
        </h3>
        <br>
        <div class="form-group">
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Item</label>
                <input class="form-control lettersNumbers" type="text" id="item" name="item" placeholder="ARM" value="">
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Costo</label>
                <input class="form-control prices" type="text" id="costo" name="costo" placeholder="200" value="">
            </div>
            <div class="col-xs-2">
                <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="anadir_producto" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                    <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-vcenter">
                <tbody id="tablaItems">

                </tbody>
            </table>
        </div>
    </div>

</div>
<div class="block block-rounded">
    <div class="block-content">
        <div class="block-title" >
            Observación
        </div>
        <div class="form-group">
            <div class="col-xs-4">
                <!-- <label for="bd-qsettings-name">Observación (*):</label> -->
                <textarea name="observacion" autocomplete="false" id="observacion" rows="4" cols="70" class="form-control lettersNumbers" value="">
                </textarea>
                <!--  <input type="" class="form-control" name="observacion" autocomplete="false" id="observacion" value="" placeholder="" /> -->
            </div>
        </div>
        <div style="margin-left: 500px">
            <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="submit" id="enviar" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                <i class="fa fa-check push-5-r"></i>Guardar
            </button>
        </div>
    </div>

</div>

</form>
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Remito Pedido</h3>
    </div>
    <div class="block-content">
        <div class="table-responsive">
            <iframe src="" id="iframeComprobante" style="width:100%;height:400px;"></iframe>
        </div>
    </div>
</div>
<p></p>
</div>

</div>
<!-- END Page Content -->

<div class="modal in" id="estados" tabindex="-1" role="dialog" aria-hidden="true" style="display: none; padding-right: 16px;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="block block-themed block-transparent remove-margin-b">
                <div class="block-header bg-primary-dark">
                    <ul class="block-options">
                        <li>
                            <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                        </li>
                    </ul>
                    <h3 class="block-title" id="configuracion_rack_titulo">Estado del Pedido <label style="sfont-size:15px;" id="etiqueta_caja"></label></h3>
                </div>
                <div class="block-content">
                    <div class="block block-rounded" style="background-color: #46c37b !important;color:white;">
                        <div class="alert alert-success alert-dismissable" style="display:none;" id="rack_success" >
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h3 class="font-w300 push-15">Mensaje</h3>
                            <p id="nombre-devuelto"></p>
                        </div>
                    </div>
                    <select id="estado_nuevo" style="margin-bottom:33px;" name="estado_nuevo" class="form-control">
                        <option value="">Seleccione el nuevo estado para el pedido</option>
                        <option value="1">En Mostrador</option>
                        <option value="2">En taller</option>
                        <option value="3">En transito</option>
                        <option value="4">Finalizado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-default" type="button" id="cambiar_estado" >Cambiar Estado</button>
                <button class="btn btn-sm btn-default" type="button" id="close" data-dismiss="modal" >Cancelar</button>
            </div>
        </div>
    </div>
</div>


<!-- END Clientes -->
@endsection
@section("scripts")
<!-- <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> -->
<link rel="stylesheet" href="/assets/css/core/jquery.com_ui_1.12.1.css">
<!-- <script src="https://code.jquery.com/jquery-1.12.4.js"></script> -->
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> -->
<script src="/assets/js/core/jqueryv1.12.4.js"></script>
<script src="/assets/js/pedidos/pedidos.js?v=<?php echo rand(); ?>"></script>
@endsection
