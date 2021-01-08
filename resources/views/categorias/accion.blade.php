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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">&Aacute;rea Carga</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Se cargaron <?php echo $total; ?> productos</h2>
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
			<form class="form-horizontal" id="form-categoria" action="/categoria.save" enctype="multipart/form-data" method="post" >
				<input type="hidden" value="" name="id_categoria" id="id_categoria"/>
				<input type="hidden" name="_token" value="{{ csrf_token() }}"/>
				<div class="form-group">
					<div class="col-xs-4">
						<label for="bd-qsettings-name">Nombre (*)</label>
						<input type="text" class="form-control lettersNumbers" name="nombre" id="nombre" value="" placeholder="Artesania Lana" />
					</div>
					<div class="col-xs-4">
						<label>Abreviatura (*)</label>
						<input type="text" class="form-control lettersNumbers" name="abreviatura" id="abreviatura" value="" placeholder="ALAN" />
					</div>
				</div>
				<div class="form-group">
					<div class="col-xs-4">
						<button class="btn btn-sm btn-minw btn-rounded btn-primary" id="enviar" style="width: 100%;margin-top: 7%;" type="submit">
							<i class="fa fa-check push-5-r"></i>Guardar
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<!-- Mensaje no se pudo eliminar categoría -->
	<div id="erroreliminar" class="alert alert-danger text-center hidden" role="alert" style="position: fixed; bottom: 20px; width: 100%;">
		<p style="font-weight: bold;">No se puede eliminar esta categoría <small style="font-weight: normal;">Existen productos y/o proveedores asociados a esta</small></p>
	</div>
	<!-- Products -->
	<div class="block block-rounded">
		<div class="block-header">
			<h3 class="block-title">Categorizaci&oacute;n</h3>
		</div>
		<div class="block-content">
			<div class="table-responsive">
				<table class="table table-hover table-vcenter">
					<tbody>
						@if (count($categorias)>0)
						@foreach($categorias as $categoria)
						<tr id="<?php echo $categoria->id; ?>">
							<td class="text-center">
								<div style="width: 180px;">
									<img class="img-responsive" src="/assets/img/photos/no-image-featured-image.png" alt="">
								</div>
							</td>
							<td>
								<h4>{{$categoria->nombre}}
									@if ($categoria->habilitada == 1)
									(<a id="status_{{$categoria->id}}" href="javascript:void();" onclick="cambiarStatus('<?php echo $categoria->id; ?>')">Deshabilitar</a>, <a href="javascript:void();" onclick="modificarCategoria('<?php echo $categoria->id; ?>')">Modificar</a>, <a href="javascript:void();" onclick="eliminarCategoria('<?php echo $categoria->id; ?>')">Eliminar</a>)</h4>
									@else
									(<a id="status_{{$categoria->id}}" href="javascript:void();" onclick="cambiarStatus('<?php echo $categoria->id; ?>')">Habilitar</a>, <a href="javascript:void();" onclick="modificarCategoria('<?php echo $categoria->id; ?>')">Modificar</a>, <a href="javascript:void();" onclick="eliminarCategoria('<?php echo $categoria->id; ?>')">Eliminar</a>)</h4>
									@endif
									<p class="remove-margin-b">Ingresado por {{$categoria->usuario}} </p>
								</td>
								<td>
									<p class="remove-margin-b">Abreviatura: <span class="text-gray-dark">{{$categoria->abreviatura}} </span></p>
								</td>
									@if ($categoria->habilitada == 1)
								<td class="text-center">
									<span id="spanHabilitada_{{$categoria->id}}" class="h3 font-w700 text-success">Habilitado <?php echo ($categoria->habilitada == 1)?"Si":"No"; ?></span>
								</td>
								@else
								<td class="text-center">
									<span id="spanHabilitada_{{$categoria->id}}" class="h3 font-w700 text-success" style='color:red;'>Habilitado <?php echo ($categoria->habilitada == 1)?"Si":"No"; ?></span>
								</td>
								@endif
							</tr>
							@endforeach
							@else
							<tr>
								<td>
									<label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay categorías registradas</label>
								</td>
							</tr>
							@endif
						</tbody>
					</table>
				</div> <!-- table responsive -->
			</div> <!-- content -->
		</div> <!-- block rounded -->
		<!-- END Categorias -->
	</div> <!-- content-boxed -->
	@endsection
	@section("scripts")
	<script src="/assets/js/categorias/categorias_accion.js?v=1.08"></script>  
	@endsection