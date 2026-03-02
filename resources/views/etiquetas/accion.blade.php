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
						<h1 class="h1 font-w700 text-green animated fadeInDown push-5" style="color:white">Impresi&oacute;n de etiquetas</h1>
						<h2 class="h4 font-w400 text-white animated fadeInUp"></h2>
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
            <form class="form-horizontal" action="" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-6">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="nombre-producto" id="nombre-producto" value="" placeholder="Ingrese parte del nombre o codigo de barras" />
                        <input type="hidden" class="form-control" name="producto_id" id="producto_id" value="" />
                    </div>
                    <div class="col-xs-2">
                        <label for="bd-qsettings-name">Cantidad Etiquetas</label>
                        <input type="text" class="form-control numbers" name="cantidad" id="cantidad" value="" placeholder="3" />
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" id="ver_etiquetas" style="width:98%;margin-top:25px;" type="button">
                            <i class="fa fa-check push-5-r"></i>Agregar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products
    <img alt="testing" src="/librarys/barcode.php?codetype=Code39&text=28256851&print=true&size=40" />
    <img alt="testing" src="/librarys/barcode.php?codetype=Code25&text=testing&print=true&size=40" /> -->

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">C&oacute;digo QR</h3>
        </div>
        <!-- <div style="text-align: center">
            <p id="mensajeqr">Por favor seleccione un producto</p>
            <p id="sitiowebvacio" class="hidden">Este producto no tiene c&oacute;digo QR</p>
        </div> -->
        <div class="block-content">
            <div class="table-responsive">
                <iframe src="" id="iframe_qrs" style="width:100%;height:400px;"></iframe>
            </div>
        </div>
        <!-- <button class="btn btn-sm btn-minw btn-rounded btn-primary hidden" id="botonqr" onclick="printJS({ printable: 'qrcode', type: 'html', documentTitle: 'SIGAV', style: '#qrcode {display: flex; justify-content: center;}' })" style="width:98%;height:30px;margin-top:25px;" type="button">
            Imprimir QR
        </button> -->
    </div>

    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Etiquetas</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <iframe src="" id="iframe_etiquetas" style="width:100%;height:500px;"></iframe>
            </div>
        </div>
         <div class="block-content">
         </div>
    </div>
    <!-- END Products -->
</div>
@endsection
@section("scripts")
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- script QR -->
    <!-- <script type="text/javascript" src="./assets/js/qr/qrcode.js"></script> -->

    <!-- PintJS -->
    <script type="text/javascript" src="./assets/js/printJS/print.min.js"></script>
    <script type="text/javascript" src="./assets/js/etiquetas/etiquetas_accion.js"></script>
@endsection














