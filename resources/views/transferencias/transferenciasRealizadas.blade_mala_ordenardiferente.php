@extends('layout.layout');
<style>
.ui-autocomplete-loading {
    background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
}
.tabla { 
}
.tabla thead {
  cursor: pointer;
  background: #4d96d529;
}
.tabla thead tr th { 
  font-weight: bold;
  
}

.tabla thead tr th.headerSortUp,
.tabla thead tr th.headerSortDown {
  background: rgba(0, 0, 0, .2);
}
.tabla thead tr th.headerSortUp span {
  background-image: url('http://tablesorter.com/themes/blue/asc.gif');
}
.tabla thead tr th.headerSortDown span {
  background-image: url('http://tablesorter.com/themes/blue/desc.gif');
}
.tabla tbody tr td {
  text-align: center;
}

</style>
@section("body")
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push"
        style="background-image: url('/assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">
                            Transferencias</h1>
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
            <div class="col-xs-12 bg-success" style="background-color: transparent;" id="nombre-devuelto">
                <?php echo base64_decode($_GET["mensaje"]); ?></div>
        </div>
    </div>
    <?php } ?>
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Transferencias </h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-center text-center tabla" cellspacing="5" cellpadding="2" id="tablaTransferencias">
                    <thead>
                        <tr>
                            <td class="text-center">Sucursal Origen</td>
                            <td class="text-center">Sucursal Destino</td>
                            <td class="text-center">Fecha origen</td>
                            <td class="text-center">Usuario</td>
                            <td class="text-center">Productos</td>
                            <td class="text-center">Comentarios</td>
                            <td class="text-center">Estado</td>
                            <td class="text-center">Opciones</td>
                        </tr>
                    </thead>
                    <tbody id="tbody">
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
                                <div class="row" style="display:block;">
                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="<?php echo "#modal$transferencia->id";?>">
                                        <i class="fa fa-eye push-7-r"></i>
                                    </button>
                                    <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="<?php echo "#print$transferencia->id";?>" id="printEtiquetas"
                                        onclick="printEtiqueta(<?php echo $transferencia->id;?>);">
                                        <i class="fa fa-print push-7-r"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="<?php echo "modal$transferencia->id"?>" tabindex="-1"
                                    role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="exampleModalLongTitle">Productos</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div> <!-- end header -->
                                            <div class="modal-body">
                                                <table class="table table-hover table-center text-center">
                                                    <thead>
                                                        <th class="text-center">Cód Barra</th>
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
                                                                <td>{{$producto->codigo_barras}} </td>
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
                                                        <strong>Total productos:</strong><span
                                                            class="label label-primary"><?php echo $cantProductos?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Atras</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal fade" id="<?php echo "print$transferencia->id"?>" tabindex="-1"
                                    role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="exampleModalLongTitle">Vista Previa de
                                                    impresión</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div> <!-- end header -->
                                            <div class="modal-body">
                                                <div class="block-content">
                                                    <div class="table-responsive">
                                                        <iframe src=""
                                                            id="<?php echo "iframe_etiquetas$transferencia->id"?>"
                                                            style="width:100%;height:300px;">
                                                        </iframe>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if ($transferencia->sucursal_destino_id == $sucursal_activa)
                                <textarea id="comentario{{$transferencia->id}}"
                                    name="comentario">{{$transferencia->comentario}}</textarea>
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
                                    @if ($transferencia->estado_id==4)
                                    <option value="4" selected> Aceptada</option>
                                    @endif
                                    @if(count($estados)>0)
                                    @foreach ($estados as $estado)
                                    @if ($transferencia->estado_id==$estado->id)
                                    <option value="<?php echo $estado->id; ?>" selected> {{$estado->nombre}} </option>
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
                                    <option value="<?php echo $estado->id; ?>" selected> {{$estado->nombre}} </option>
                                    @else
                                    @if (($transferencia->estado_id==1 || $transferencia->estado_id==2) && ($estado->id
                                    ==2 || $estado->id ==6))
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
                                <button onclick="cambiarEstado('<?php echo $transferencia->id; ?>')"
                                    class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;"
                                    type="submit"
                                    <?php if ( $transferencia->id_estado == '4' || $transferencia->id_estado == '5' || $transferencia->id_estado == '6') {?>
                                    disabled <?php } ?>>
                                    Cambiar estado
                                </button>

                            </td>
                            @endif
                            @if ($transferencia->sucursal_origen_id == $sucursal_activa)
                            <td>
                                <button onclick="cambiarEstado('<?php echo $transferencia->id; ?>')"
                                    class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;"
                                    type="submit"
                                    <?php if ($transferencia->id_estado == '3' || $transferencia->id_estado == '4' || $transferencia->id_estado == '5' || $transferencia->id_estado == '6') {?>
                                    disabled <?php } ?>>
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
<link rel="stylesheet" href="/assets/css/core/jquery.com_ui_1.12.1.css">
<link rel="stylesheet" href="/assets/css/core/jquery.dataTables1.10.13.min.css">
<!-- <link rel="stylesheet" href="/assets/css/core/buttons.dataTables1.2.4.min.css"> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.28.14/js/jquery.tablesorter.min.js"></script>
<!-- <script src="/assets/js/jquery.dataTables.min.js"></script> -->
<!-- <script type="text/javascript" src="/assets/js/core/dataTables.buttons1.2.4.min.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.flash.min1.2.4.js"></script>
<script type="text/javascript" src="/assets/js/core/jszip.min2.5.0.js"></script>
<script type="text/javascript" src="/assets/js/core/pdfmake.min0.1.24.js"></script>
<script type="text/javascript" src="/assets/js/core/vfs_fonts0.1.24.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.html5.min1.2.4.js"></script>
<script type="text/javascript" src="/assets/js/core/buttons.print.min1.2.4.js"></script> -->
<script src="/assets/js/transferencias/transferencias_realizadas.js"></script>
<script type="text/javascript">
     $(document).ready(function(){
          $('#tablaTransferencias').tablesorter();
    });
    
function printEtiqueta(id) {
    $("#iframe_etiquetas" + id).attr("src", "/etiqueta.imprimirEtiquetasTransferencias/" + id);
}
</script>

@endsection