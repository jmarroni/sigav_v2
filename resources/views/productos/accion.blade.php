@extends('layout.layout');
@section("body")
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Datos del Producto</h3>
    </div>
    <div class="block-content" id="block-content">  
        <form class="form-horizontal" action="/carga" enctype="multipart/form-data" method="post" >
            <input type="hidden" value="" name="id" id="id" />
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="form-group">
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Nombre (*)</label>
                    <input type="text" class="form-control" name="producto" id="producto" value="" placeholder="Nombre del Producto Completo" />
                </div>
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Codigo de Barras <i>(autogenerado si se deja en blanco)</i> </label>
                    <input type="text" class="form-control" name="codigo_de_barras" id="codigo_de_barras" value="" placeholder="Codigo de Barras" />
                </div>
                <div class="col-xs-4">
                    <label>Material</label>
                    <input type="text" class="form-control" name="material" id="material" value="" placeholder="Madera, Metal, Alphaca" />
                </div>
                <div class="col-xs-4" style="display:none;">
                    <label>Stock</label>
                    <input type="text" class="form-control" name="stock" id="stock" value="0" placeholder="stock" />
                </div>
                <div style="display:none;" class="col-xs-4">
                    <label>Stock Minimo</label>
                    <input type="text" class="form-control" name="stock_minimo" id="stock_minimo" value="0" placeholder="Alerta Stock Minimo" />
                </div>
                
            </div>
            <div class="form-group">
                <div class="col-xs-4">
                    <label>Proveedor (*)</label>
                    <select class="form-control" id="proveedor" name="proveedor">
                        <option value="0">Seleccione un rubro</option>
                        @foreach($proveedores as $provedor)
                            <option value="{{$provedor->id}}">{{$provedor->nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xs-4">
                    <label>Categoria (*)</label>
                    <select class="form-control" disabled="disabled" name="categoria" id="categoria">
                        <option value="">Seleccione un rubro</option>
                    </select>
                </div>
                <div class="col-xs-4">
                    <label>Precio Ultima Compra (*)</label>
                    <input type="text" class="form-control" name="costo" id="costo" value="" placeholder="Costo por unidad" />
                </div>
            </div>
            <div class="form-group">
                
                <div class="col-xs-4">
                    <label>Precio Minorista (*)</label>
                    <input type="text" class="form-control" name="precio_unidad" id="precio_unidad" value="" placeholder="Precio unidad (. para decimales 5.5)" />
                </div>
                <div class="col-xs-4">
                    <label>Precio Mayorista <i>(Solo si utiliza)</i></label>
                    <input type="text" class="form-control" name="precio_mayorista" id="precio_mayorista" value="" placeholder="Precio mayorista (. para decimales 5.5)" />
                </div>
                <div class="col-xs-4">
                    <label>Precio Reposicion <i>(Solo si utiliza)</i></label>
                    <input type="text" class="form-control" name="precio_reposicion" id="precio_reposicion" value="" placeholder="Precio reposicion (. para decimales 5.5)" />
                </div>
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen1" id="imagen1" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen2" id="imagen2" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen3" id="imagen3" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen4" id="imagen4" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen5" id="imagen5" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="col-xs-4">
                <label for="bd-qsettings-name">Cargar Imagen</label>
                <input type="file" class="form-control" readonly name="imagen6" id="imagen6" value="" placeholder="Codigo de Barras" />
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>Descripci&oacute;n (*)</label>
                    <textarea type="text" class="form-control" name="descripcion" id="descripcion" placeholder="Describa el producto" ></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>Descripci&oacute;n Ingles</label>
                    <textarea type="text" class="form-control" name="descripcion_en" id="descripcion_en" placeholder="Describa el producto" ></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>Descripci&oacute;n Portugues</label>
                    <textarea type="text" class="form-control" name="descripcion_pr" id="descripcion_pr" placeholder="Describa el producto" ></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-8 col-xs-offset-2">
                    <button id="anadir" class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
                        <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Products -->
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Productos</h3>
        <div class="col-xs-6" style="padding-left: 0px;padding-top: 20px;">
            <label>Sucursal</label>
            <select class="form-control" name="sucursal" id="sucursal" >
                <option value="0">Seleccione una Sucursal</option> 
                    @foreach($sucursales as $sucu)
                        <option @if($sucursal == $sucu->id) selected="selected" @endif value="{{$sucu->id}}">{{utf8_decode($sucu->nombre)}}</option>
                    @endforeach
                </select>
        </div>
    </div>
    <div class="block-content">
            <table id="tabla_productos">
                <thead>
                    <tr>
                        <th style="width:10%">Imagen</th>
                        <th>Producto</th>
                        <th style="wisth:50px">Stock Actual</th>
                        <th style="wisth:50px">Stock M&iacute;nimo</th>
                        <th>Precio al P&uacute;blico</th>
                        <th>Precio Reposici&oacute;n</th>
                        <th>Costo</th>
                        <th style="width:100px">Acci&oacute;n</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $producto)
                    <tr id="<?php echo $producto->id; ?>">
                        <td style="text-align:center">
                            <div class="block">
                                <div class="block-content">
                                    <!-- Slider with dots -->
                                    <div class="js-slider" data-slider-dots="true" style="width:150px;">
                                        @for($i = 0;$i < 6;$i ++)
                                            @if (isset($producto->imagenes[$i]))
                                                <div style="width:50px;height:50px;float:left;"><img id="{{$producto->id}}_{{$i}}" onclick="viewImage('{{$producto->id}}_{{$i}}')" style="float:left;" class="img-responsive" src="{{str_replace('/'.$producto->id.'/','/'.$producto->id.'/thumb_300x300_',$producto->imagenes[$i]->imagen_url)}}" /></div>
                                            @else
                                                <div style="width:50px;height:50px;float:left;"><img style="float:left;" class="img-responsive" src="/assets/img/photos/no-image-featured-image.png" /></div>
                                            @endif
                                        @endfor
                                    </div>
                                    <!-- END Slider with dots -->
                                </div>
                            </div>
                        </td>
                        <td>{{$producto->nombre}}</td>
                        @if (isset($producto->stock_->stock))
                            <td style="text-align:center"><input style="width:50px" type="text" value="{{$producto->stock_->stock}}" name="stock_{{$producto->id}}" id="stock_{{$producto->id}}" /></td>
                        @else
                            <td style="text-align:center"><input style="width:50px" type="text" value="0" name="stock_{{$producto->id}}" id="stock_{{$producto->id}}" /></td>
                        @endif
                        @if (isset($producto->stock_->stock_minimo))
                            <td style="text-align:center"><input style="width:50px" type="text" value="{{$producto->stock_->stock_minimo}}" name="stock_minimo_{{$producto->id}}" id="stock_minimo_{{$producto->id}}" /></td>
                        @else
                        <td style="text-align:center"><input style="width:50px" type="text" value="0" name="stock_minimo_{{$producto->id}}" id="stock_minimo_{{$producto->id}}" /></td>
                        @endif
                        <td>{{$producto->precio_unidad}}</td>
                        <td>{{$producto->precio_reposicion}}</td>
                        <td>{{$producto->costo}}</td>
                        <td class="text-right">
                            <button id="editar_{{$producto->id}}" title="Editar" class="btn btn-xs btn-default" type="button">
                                <i class="fa fa-pencil text-success"></i>
                            </button>
                            <button id="eliminar_{{$producto->id}}" title="Eliminar" class="btn btn-xs btn-default" type="button">
                                <i class="fa fa-times text-danger"></i>
                            </button>
                            <button id="actualizar_{{$producto->id}}" title="Actualizar" class="btn btn-xs btn-default" type="button">
                                <i class="fa fa-check text-primary"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
    </div>
</div>

<!-- The Modal -->
<div id="myModal" class="modal">

  <!-- The Close Button -->
  <span class="close">&times;</span>

  <!-- Modal Content (The Image) -->
  <img class="modal-content" id="img01">

  <!-- Modal Caption (Image Text) -->
  <div id="caption"></div>
</div>

<!-- END Products -->
@endsection
@section("scripts")
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/vfs_fonts.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(){
        $('#tabla_productos').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            dom: 'Bfrtip',
            "order": [[1, 'asc']],
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            columnDefs: [
                { targets: [0,2,3,7], "orderable": false,}
            ]
        });

        $(".close").click(function(){
            $("#myModal").hide();
            $("#img01").attr("src","/assets/img/photos/no-image-featured-image.png");
        });
        
    });

    function viewImage(id){
        $("#img01").attr("src",$("#" + id).attr("src"));
        $("#myModal").show();
    }


</script>
<style>
 /* Style the Image Used to Trigger the Modal */
 #myImg {
  border-radius: 5px;
  cursor: pointer;
  transition: 0.3s;
}

#myImg:hover {opacity: 0.7;}

/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
}

/* Modal Content (Image) */
.modal-content {
  margin: auto;
  display: block;
  width: 80%;
  max-width: 700px;
}

/* Caption of Modal Image (Image Text) - Same Width as the Image */
#caption {
  margin: auto;
  display: block;
  width: 80%;
  max-width: 700px;
  text-align: center;
  color: #ccc;
  padding: 10px 0;
  height: 150px;
}

/* Add Animation - Zoom in the Modal */
.modal-content, #caption {
  animation-name: zoom;
  animation-duration: 0.6s;
}

@keyframes zoom {
  from {transform:scale(0)}
  to {transform:scale(1)}
}

/* The Close Button */
.close {
  position: absolute;
  top: 85px;
  right: 35px;
  color: #f1f1f1;
  font-size: 80px;
  font-weight: bold;
  transition: 0.3s;
}

.close:hover,
.close:focus {
  color: #bbb;
  text-decoration: none;
  cursor: pointer;
}

/* 100% Image Width on Smaller Screens */
@media only screen and (max-width: 700px){
  .modal-content {
    width: 100%;
  }
} 
</style>
@endsection