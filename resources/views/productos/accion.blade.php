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
                    <input type="text" class="form-control lettersNumbers" name="producto" id="producto" value="" placeholder="Nombre del Producto Completo" />
                </div>
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">C&oacute;digo de Barras <i>(autogenerado si se deja en blanco)</i> </label>
                    <input type="text" class="form-control" name="codigo_de_barras" id="codigo_de_barras" value="" placeholder="Codigo de Barras" />
                </div>
                <div class="col-xs-4">
                    <label>Material</label>
                    <input type="text" class="form-control lettersNumbers" name="material" id="material" value="" placeholder="Madera, Metal, Alphaca" />
                </div>
                <div class="col-xs-4" style="display:none;">
                    <label>Stock</label>
                    <input type="text" class="form-control numbers" name="stock" id="stock" value="0" placeholder="stock" />
                </div>
                <div style="display:none;" class="col-xs-4">
                    <label>Stock M&iacute;nimo</label>
                    <input type="text" class="form-control numbers" name="stock_minimo" id="stock_minimo" value="0" placeholder="Alerta Stock Minimo" />
                </div>
                
            </div>
            <div class="form-group">
                <div class="col-xs-4">
                    <label>Proveedor (*)</label>
                    <select class="form-control" id="proveedor" name="proveedor">
                        <option value="0">Seleccione un proveedor</option>
                        @foreach($proveedores as $provedor)
                        <option value="{{$provedor->id}}">{{$provedor->nombre}} {{$provedor->apellido}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xs-4">
                    <label>Categor&iacute;a (*)</label>
                    <select class="form-control" disabled="disabled" name="categoria" id="categoria">
                        <option value="">Seleccione un rubro</option>
                    </select>
                </div>
                <div class="col-xs-4">
                    <label>Precio &Uacute;ltima Compra (*)</label>
                    <input type="text" class="form-control prices" name="costo" id="costo" value="" placeholder="Costo por unidad" />
                </div>
            </div>
            <div class="form-group">

                <div class="col-xs-4">
                    <label>Precio Minorista (*)</label>
                    <input type="text" class="form-control prices" name="precio_unidad" id="precio_unidad" value="" placeholder="Precio unidad (. para decimales 5.5)" />
                </div>
                <div class="col-xs-4">
                    <label>Precio Mayorista <i>(Solo si utiliza)</i></label>
                    <input type="text" class="form-control prices" name="precio_mayorista" id="precio_mayorista" value="" placeholder="Precio mayorista (. para decimales 5.5)" />
                </div>
                <div class="col-xs-4">
                    <label>Precio Reposici&oacute;n <i>(Solo si utiliza)</i></label>
                    <input type="text" class="form-control prices" name="precio_reposicion" id="precio_reposicion" value="" placeholder="Precio reposicion (. para decimales 5.5)" />
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
                    <textarea type="text" class="form-control lettersNumbers" name="descripcion" id="descripcion" placeholder="Describa el producto" ></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>Descripci&oacute;n Ingl&eacute;s</label>
                    <textarea type="text" class="form-control lettersNumbers" name="descripcion_en" id="descripcion_en" placeholder="Describa el producto" ></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>Descripci&oacute;n Portugu&eacute;s</label>
                    <textarea type="text" class="form-control lettersNumbers" name="descripcion_pr" id="descripcion_pr" placeholder="Describa el producto" ></textarea>
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
<!-- Filtros -->
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Filtros y Busqueda</h3>
    </div>
    <div class="block-content" style="background: #f9f9f9; padding: 15px;">
        <form method="GET" action="{{ url('carga') }}" id="formFiltros">
            <div class="row">
                <div class="col-xs-12 col-md-3">
                    <div class="form-group">
                        <label>Buscar (Nombre o Codigo)</label>
                        <input type="text" class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar producto...">
                    </div>
                </div>
                <div class="col-xs-12 col-md-2">
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select name="proveedor_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                                    {{ $prov->nombre }} {{ $prov->apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-md-2">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-md-2">
                    <div class="form-group">
                        <label>Sucursal (Stock)</label>
                        <select class="form-control" name="sucursal" id="sucursal">
                            @foreach($sucursales as $sucu)
                            <option @if($sucursal == $sucu->id) selected="selected" @endif value="{{$sucu->id}}">{{$sucu->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                            <a href="{{ url('carga') }}" class="btn btn-default">Limpiar</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Products -->
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Productos</h3>
        <div class="block-options">
            <span style="color: #666; font-size: 12px;">
                Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
            </span>
        </div>
    </div>
    <div class="block-content" style="overflow-x: auto;">
        <table class="table table-striped table-hover table-bordered" id="tabla_productos" style="font-size: 12px;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th>Codigo</th>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th>Categoria</th>
                    <th style="text-align:center;">Stock</th>
                    <th>Precio Publico</th>
                    <th>Precio Repos.</th>
                    <th>Costo</th>
                    <th style="width:100px">Accion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $producto)
                <?php $i=0; ?>
                @foreach($imagenes as $imagenp)
                @if ($producto->id==$imagenp->productos_id)
                <?php $imagen[$i]=$imagenp->imagen_url;?>
                @endif
                <?php $i=$i+1; ?>
                @endforeach

                <tr id="<?php echo $producto->id; ?>">
                    <td>{{$producto->codigo_barras}}</td>
                    <td>{{$producto->nombre}}</td>
                    @if ($producto->nombreProveedor!=null && $producto->nombreProveedor!="")
                    <td>{{$producto->nombreProveedor}} {{$producto->apellidoProveedor}}</td>
                    @else
                    <td><span class="text-warning">Sin proveedor</span></td>
                    @endif
                    @if ($producto->nombreCategoria!=null && $producto->nombreCategoria!="")
                    <td>{{$producto->nombreCategoria}}</td>
                    @else
                    <td><span class="text-warning">Sin categoria</span></td>
                    @endif
                    @if (isset($producto->stock_->stock))
                    <td style="text-align:center"><input style="width:60px" type="text" value="{{$producto->stock_->stock}}" name="stock_{{$producto->id}}" class="form-control input-sm numbers" id="stock_{{$producto->id}}" /></td>
                    @else
                    <td style="text-align:center"><input style="width:60px" type="text" value="0" name="stock_{{$producto->id}}" class="form-control input-sm numbers" id="stock_{{$producto->id}}" /></td>
                    @endif
                    <td style="text-align:right;">${{ number_format($producto->precio_unidad, 2, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($producto->precio_reposicion ?? 0, 2, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($producto->costo ?? 0, 2, ',', '.') }}</td>
                    <td class="text-center">
                        <button id="editar_{{$producto->id}}" title="Editar" class="btn btn-xs btn-default" type="button">
                            <i class="fa fa-pencil text-success"></i>
                        </button>
                        <button id="eliminar_{{$producto->id}}" title="Eliminar" class="btn btn-xs btn-default" type="button">
                            <i class="fa fa-times text-danger"></i>
                        </button>
                        <button id="actualizar_{{$producto->id}}" title="Actualizar Stock" class="btn btn-xs btn-default" type="button">
                            <i class="fa fa-check text-primary"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No se encontraron productos</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Paginacion -->
        <div class="row" style="margin-top: 15px;">
            <div class="col-xs-6">
                <p style="color: #666; padding-top: 7px;">
                    Mostrando {{ $productos->firstItem() ?? 0 }} a {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
                </p>
            </div>
            <div class="col-xs-6 text-right">
                @if ($productos->hasPages())
                    <ul class="pagination" style="margin: 0;">
                        @if ($productos->onFirstPage())
                            <li class="disabled"><span>&laquo;</span></li>
                        @else
                            <li><a href="{{ $productos->previousPageUrl() }}">&laquo;</a></li>
                        @endif

                        @foreach ($productos->getUrlRange(max(1, $productos->currentPage() - 2), min($productos->lastPage(), $productos->currentPage() + 2)) as $page => $url)
                            @if ($page == $productos->currentPage())
                                <li class="active"><span>{{ $page }}</span></li>
                            @else
                                <li><a href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        @if ($productos->hasMorePages())
                            <li><a href="{{ $productos->nextPageUrl() }}">&raquo;</a></li>
                        @else
                            <li class="disabled"><span>&raquo;</span></li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
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
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/pages/carga.js?v=1.13"></script>

<script type="text/javascript">
    $(document).ready(function(){
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