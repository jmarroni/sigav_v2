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
						<h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Roles</h1>
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
			<form class="form-horizontal" id="form-rol" action="/rol.save" method="post" >
				<input type="hidden" value="" name="id" id="id" />
				<input type="hidden" name="_token" value="{{ csrf_token() }}"/>
				<div class="form-group">
					<div class="col-xs-4">
						<label for="bd-qsettings-name">Rol</label>
						<input type="text" class="form-control letters" name="rol" id="rol" value="" placeholder="Administrador" />
					</div>
					<div class="col-xs-3 col-xs-offest-1" style="margin-top:2%;">
						<label class="css-input switch switch-success">
							<input type="checkbox" class="form-control" id="habilitado" name="habilitado" ><span></span> Habilitado
						</label>
					</div>
					<div class="col-xs-12" style="margin-top:20px;">
						@if (count($secciones)>0)
						@foreach($secciones as $seccion)
						<div class="col-xs-2">
							<input style="width:20%;display:inline;float:right" type="checkbox" name="secciones[]" id="secciones_{{$seccion->id}}" class="form-control" value="{{$seccion->id}}" ><label style="float:left;margin-top:10px;"><?php echo $seccion->nombre; ?>&nbsp;</label>
						</div>
						@endforeach
						@endif
					</div>
					<div class="col-xs-4 col-xs-offset-4">
						<button class="btn btn-sm btn-minw btn-rounded btn-primary" id="enviar" style="width:98%;margin-top:25px;" type="submit">
							<i class="fa fa-check push-5-r"></i>Guardar
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<!-- Products -->
	<div class="block block-rounded">
		<div class="block-header">
			<div class="col-xs-8" > 
				<h3 class="block-title">ROLES</h3>
			</div>
		</div>
		<div class="block-content">
			<div class="table-responsive">
				<table class="table table-hover table-vcenter">
					<tbody id="tablaProductos">
						@if (count($roles)>0)
						@foreach($roles as $rol)
						<tr id="articulo_{{$rol->id}}">
							<td class="text-center">
								<div style="width: 180px;">
									<img class="img-responsive" src="/assets/img/photos/no-image-featured-image.png"
									alt="">
								</div>
							</td>
							<td>
								<h4>Rol: {{$rol->nombre}} </h4>
								<p class="remove-margin-b">Secciones:
									<?php 
                                    $seccion_id = "";?>
									@if (count($relacionesSeccionRol)>0)
										@foreach($relacionesSeccionRol as $relacionSeccionRol) 
											@if ($relacionSeccionRol->roles_id==$rol->id) 
												{{$relacionSeccionRol->nombre}} -
												<?php $seccion_id=$seccion_id.$relacionSeccionRol->id."|"; ?>
											@endif
										@endforeach
									@endif
								</p>
								<input type="hidden" value="<?php echo $seccion_id ?>" name="seccion_{{$rol->id}}" id="seccion_{{$rol->id}}" />
								<button onclick="modificarRol('{{$rol->id}}','{{$rol->nombre}}','{{$rol->habilitado}}');"  class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
									<i class="fa fa-check push-5-r"></i>Modificar
								</button>
								<button onclick="eliminarRol('{{$rol->id}}');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
									<i class="fa fa-times push-5-r"></i>Eliminar
								</button>
							</td>                                
						</tr>
						@endforeach
						@else
								<tr>
								<td>
									<label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay roles registrados</label>
								</td>
							</tr>
						@endif
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!-- END Products -->
	</div>
	@endsection
	@section("scripts")
	<script src="/assets/js/roles/roles_accion.js?v=1.08"></script>  
	<script >
	// jQuery("document").ready(function() {
	// 	$("#nombre-producto").click(function(event){
	// 		alert("sirve");
	// 	});

	// });
</script>

@endsection














