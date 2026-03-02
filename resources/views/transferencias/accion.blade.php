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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Transferencias</h1>
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
        <div class="col-lg-12">
            <!-- Simple Progress Wizard (.js-wizard-simple class is initialized in js/pages/base_forms_wizard.js) -->
            <!-- For more examples you can check out https://github.com/VinceG/twitter-bootstrap-wizard -->
            <div class="js-wizard-simple block">
                <!-- Steps Progress -->
                <div class="block-content block-content-mini block-content-full border-b">
                    <div class="wizard-progress progress active remove-margin-b">
                        <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                    </div>
                </div>
                <!-- END Steps Progress -->

                <!-- Step Tabs -->
                <ul class="nav nav-tabs nav-tabs-alt nav-justified">
                    <li class="active">
                        <a href="#simple-progress-step1" data-toggle="tab" aria-expanded="false">1. Sucursal</a>
                    </li>
                    <li class="">
                        <a href="#simple-progress-step2" data-toggle="tab" aria-expanded="false">2. Productos</a>
                    </li>
                    <li class="">
                        <a href="#simple-progress-step3" data-toggle="tab" aria-expanded="true">3. Confirmaci&oacute;n</a>
                    </li>
                </ul>
                <!-- END Step Tabs -->

                <!-- Form -->
                <form class="form-horizontal" action="" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    <input type="hidden" name="arrayproductos" id="arrayproductos"/>
                    <!-- Steps Content -->
                    <div class="block-content tab-content">
                        <!-- Step 1 -->
                        <div class="tab-pane fade fade-right push-30-t push-50 active in" id="simple-progress-step1">
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <select class="form-control" id="sucursal_origen" name="sucursal_origen" size="1">
                                            <option value="">Seleccione la sucursal de Origen</option>
                                            @if (count($sucursales)>0)
                                             @foreach($sucursales as $sucursal)
                                                  <option value="{{$sucursal->id}}">{{$sucursal->nombre}}</option>
                                             @endforeach
                                             @endif
                                        </select>
                                        <label for="sucursal_origen">Sucursal de Origen</label>

                                        <!-- Mensaje error sucursal_origen -->
                                        <div id="error_sucursal_origen" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>Ingrese una sucursal de origen</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <select class="form-control" id="sucursal_destino" name="sucursal_destino" size="1">
                                                <option value="">Seleccione la sucursal de Destino</option>
                                             @if (count($sucursales)>0)
                                             @foreach($sucursales as $sucursal)
                                                  <option value="{{$sucursal->id}}">{{$sucursal->nombre}}</option>
                                             @endforeach
                                             @endif
                                        </select>
                                        <label for="sucursal_destino">Sucursal de Destino</label>
                                        <div id="3"> </div>
                                        <!-- Mensaje error sucursal_origen -->
                                        <div id="error_sucursal_destino" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>Ingrese una sucursal de destino</p>
                                        </div>

                                        <!-- Mensaje error sucursales iguales -->
                                        <div id="error_sucursales_iguales" class="alert alert-danger hidden" style="margin: 5px;">
                                            <p>La sucursal origen y destino no pueden ser las mismas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                             <!-- <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <label for="comentarios">Comentario</label>

                                        <input class="form-control letters" type="text" id="comentario" name="comentario" placeholder="Observación sobre la transferencia">
                                    </div>
                                </div>
                            </div> -->
                        </div>
                        <!-- END Step 1 -->

                        <!-- Step 2 -->
                        <div class="tab-pane fade fade-right push-30-t push-50" id="simple-progress-step2">
                            <div class="form-group">
                                <div class="col-sm-6 col-sm-offset-1">
                                    <div class="form-material">
                                        <input class="form-control" type="text" id="producto" name="producto" placeholder="Ingrese el nombre o parte del mismo">
                                        <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="">
                                        <input class="form-control" type="hidden" id="producto_imagen" name="producto_imagen" placeholder="" value="">
                                        <label for="producto">Ingrese los productos a transferir</label>
                                    </div>

                                    <!-- Mensaje error stock a transferir -->
                                    <div id="error_producto" class="alert alert-danger hidden" style="margin: 5px;">
                                        <p>Seleccione un producto de la lista</p>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-material">
                                        <input class="form-control numbers" type="text" id="stock_a_transferir" name="stock_a_transferir" placeholder="100">
                                        <label for="stock_a_transferir">Cantidad a Transferir</label>

                                    </div>
                                     <div class="form-material">
                                        <label style="margin-top: 10px;font-size: 10px;color:blue"  for="stock_disponible">Disponible: <span style="color:black" id="stock_disponible"></span></label>
                                        <input type="hidden" name="disponible" id="cantidad_disponible" value="0">
                                    </div>
                                    <!-- Mensaje error stock a transferir -->
                                    <div id="error_stock_a_transferir" class="alert alert-danger hidden" style="margin: 5px;">
                                        <p>Seleccione un stock mayor a 0</p>
                                    </div>
                                </div>

                                <div class="col-sm-2">
                                    <div class="form-material">
                                        <button class="btn btn-success" id="add" type="button" >Agregar <i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-10 col-sm-offset-1">
                                <div class="table-responsive" id="tabla_responsive_productos">
                                    <table class="table table-striped table-vcenter">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 120px;"><i class="si si-picture"></i></th>
                                                <th>Producto</th>
                                                <th style="width: 30%;">Cantidad</th>
                                                <th class="text-center" style="width: 100px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla_add">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- END Step 2 -->

                        <!-- Step 3 -->
                        <div class="tab-pane fade fade-right push-30-t push-50" id="simple-progress-step3">
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <input class="form-control" disabled="disabled" type="text" id="sucursal_origen_label" name="simple-progress-city" placeholder="Origen">
                                        <label for="simple-progress-city">Sucursal Origen</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-8 col-sm-offset-2">
                                    <div class="form-material">
                                        <input class="form-control" disabled="disabled" type="text" id="sucursal_destino_label" name="simple-progress-city" placeholder="Destino">
                                        <label for="simple-progress-city">Sucursal Destino</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-10 col-sm-offset-1" id="contenedor_confirmacion">
                                
                            </div>
                        </div>
                        <!-- END Step 3 -->
                    </div>
                    <!-- END Steps Content -->

                    <!-- Steps Navigation -->
                    <div class="block-content block-content-mini block-content-full border-t">
                        <div class="row">
                            <div class="col-xs-6">
                                <button class="wizard-prev btn btn-warning" type="button"><i class="fa fa-arrow-circle-o-left"></i> Anterior</button>
                            </div>
                            <div class="col-xs-6 text-right">
                                <button class="wizard-next btn btn-success disabled" type="button" style="display: none;">Siguiente <i class="fa fa-arrow-circle-o-right"></i></button>
                                <button class="wizard-finish btn btn-primary" id="btn_guardar" type="button" style="display: inline-block;"><i class="fa fa-check-circle-o"></i> Guardar</button>
                            </div>
                        </div>
                    </div>
                    <!-- END Steps Navigation -->
                </form>
                <!-- END Form -->
            </div>
            <!-- END Simple Progress Wizard -->
        </div>
    </div>
    <!-- END Products -->
</div>
    @endsection
    @section("scripts")
    <!-- <script src="https://code.jquery.com/jquery-1.12.4.js"></script>-->
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> 
    
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> -->
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/plugins/bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
<script src="/assets/js/pages/base_forms_wizard.js"></script>
<script src="/assets/js/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="/assets/js/transferencias/transferencias_accion.js?v=<?php echo rand(); ?>"></script>

    @endsection