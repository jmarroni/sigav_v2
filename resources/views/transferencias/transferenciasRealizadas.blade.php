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
                <div class="col-xs-12 bg-success" style="background-color: transparent;" id="nombre-devuelto"><?php echo base64_decode($_GET["mensaje"]); ?></div>
            </div>
        </div>
    <?php } ?>
        <div class="block block-rounded">
        <div class="block-header"> 
            <h3 class="block-title">Transferencias </h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-center text-center">
                    <thead>
                        <th class="text-center">Sucursal Origen</th>
                        <th class="text-center">Sucursal Destino</th>
                        <th class="text-center">Fecha origen</th>
                        <th class="text-center">Usuario</th>
                        <th class="text-center">Productos</th>
                        <th class="text-center">Comentarios</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Opciones</th>
                    </thead>
                    <tbody>
                        @if (count($transferenciasRealizadas)>0)
                            @foreach ($transferenciasRealizadas as $transferencia) 
                                <tr>
                                    <td>
                                        {{$transferencia->sucursal_origen_nombre}}
                                    </td>
                                    <td>
                                         {{$transferencia->sucursal_destino_nombre}}
                                    </td>
                                    <td>
                                        <?php echo date("d/m/Y h:m:s", strtotime($transferencia->fecha))?>
                                    </td>
                                    <td>
                                         {{$transferencia->usuario}}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="<?php echo "#modal$transferencia->id";?>">
                                          Mostrar productos
                                        </button>

                                        <div class="modal fade" id="<?php echo "modal$transferencia->id"?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                          <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h4 class="modal-title" id="exampleModalLongTitle">Productos</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>  <!-- end header -->
                                              <div class="modal-body">
                                                <table class="table table-hover table-center text-center">
                                                        <thead>
                                                          <th class="text-center">Producto</th>
                                                          <th class="text-center">Cantidad</th>
                                                       </thead>
                                                        <tbody>
                                                            <?php $cantProductos=0;?>
                                                     @if (count($productos)>0)
                                                      
                                                         @foreach ($productos as $producto)
                                                            
                                                            @if ($producto->tranferencia_id==$transferencia->id)
                                                             <?php $cantProductos=$cantProductos+$producto->cantidad;?>
                                                            <div class="row">
                                                                @if(count($imagenes)>0)
                                                                   @foreach ($imagenes as $imagen)
                                                                     @if ($imagen->producto_id==$producto->id)

                                                            
                                                                <!-- <div style="float: left; width: 48%;">
                                                                    <img style="width: 50px; margin-top: 5px;" src="<?php echo $imagen->imagen_url; ?>" >
                                                                </div> -->
                                                                     @endif
                                                                    @endforeach
                                                                 @endif
                                                                <tr>
                                                                <td>{{$producto->nombre}} </td>
                                                                <td>{{$producto->cantidad}} </td>
                                                                <tr>
                                                            </div>
                                                               @endif
                                                        @endforeach
                                                        </tbody>
                                                    @endif
                                                    </table>
                                                    <div class="panel panel-default">
                                                        <div class="panel-body">
                                                        <strong>Total productos:</strong><span class="label label-primary"><?php echo $cantProductos?></span>  
                                                    </div>
                                                     </div>


                                                </ul>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Atras</button>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                    </td>

                                    <td>
                                        @if ($transferencia->sucursal_destino_id == $sucursal_activa)
                                            <textarea id="comentario{{$transferencia->id}}" name="comentario">{{$transferencia->comentario}}</textarea>
                                        @else
                                            <p>{{$transferencia->comentario}}</p>
                                        @endif
                                    </td>
                                    <td class="
                                            <?php
                                                switch ($transferencia->estado_id) {
                                                    case 1:
                                                        echo "active";
                                                        break;
                                                    case 2:
                                                        echo "info";
                                                        break;
                                                    case 3:
                                                        echo "warning";
                                                        break;
                                                    case 4:
                                                        echo "success";
                                                        break;
                                                    case 5:
                                                        echo "danger";
                                                        break;
                                                }
                                            ?>
                                        ">
                                       
                                             @if ($transferencia->sucursal_destino_id == $sucursal_activa)
                                        
                                            <select name="estado" id="estado{{$transferencia->id}}">
                                                @if(count($estados)>0)
                                                    @foreach ($estados as $estado)
                                                        @if ($transferencia->estado_id==$estado->id)
                                                            <option value="<?php echo $estado->id; ?>" selected > {{$estado->nombre}} </option>
                                                        @else
                                                             @if ($estado->id !=2 && $estado->id !=1)
                                                        <option value="<?php echo $estado->id; ?>"> {{$estado->nombre}} </option>
                                                             @endif
                                                        @endif
                                                    @endforeach

                                                @endif
                                            </select>
                                                
                                            @endif
                                            @if ($transferencia->sucursal_origen_id == $sucursal_activa)
                                                    <select name="estado" id="estado{{$transferencia->id}}">
                                                @if(count($estados)>0)
                                                    @foreach ($estados as $estado)
                                                        @if ($transferencia->estado_id==$estado->id)
                                                            <option value="<?php echo $estado->id; ?>" selected > {{$estado->nombre}} </option>
                                                        @else
                                                             @if (($transferencia->estado_id==1 || $transferencia->estado_id==2) && ($estado->id ==2 || $estado->id ==6))
                                                        <option value="<?php echo $estado->id; ?>"> {{$estado->nombre}} </option>
                                                             @endif
                                                        @endif
                                                    @endforeach

                                                @endif
                                            </select>
                                            @endif
                                    </td>
                                  
                                       @if ($transferencia->sucursal_destino_id == $sucursal_activa)
                                    
                                        <td>
                                          
                                            <button onclick="cambiarEstado('<?php echo $transferencia->id; ?>')" class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;" type="submit"
                                                <?php if ( $transferencia->id_estado == '4' || $transferencia->id_estado == '5' || $transferencia->id_estado == '6') {?>
                                                    disabled
                                                <?php } ?>
                                                >
                                                Cambiar estado
                                            </button>
                                       
                                        </td>
                                         @endif
                                          @if ($transferencia->sucursal_origen_id == $sucursal_activa)
                                        <td>
                                            <button onclick="cambiarEstado('<?php echo $transferencia->id; ?>')" class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;" type="submit"
                                                 <?php if ($transferencia->id_estado == '3' || $transferencia->id_estado == '4' || $transferencia->id_estado == '5' || $transferencia->id_estado == '6') {?>
                                                    disabled
                                                <?php } ?>
                                                >
                                                Cambiar estado
                                            </button>
                                       
                                        </td>
                                         @endif 
                                </tr>
                        @endforeach
                        @else
                            <div class="alert alert-info">
                                <p>No ha realizado ninguna transferencia</p>
                            </div>
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- END Main Content -->
        </div>
    </div>
    <!-- END Products -->
</div>    
@endsection
@section("scripts")
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/transferencias/transferencias_accion.js"></script>
@endsection