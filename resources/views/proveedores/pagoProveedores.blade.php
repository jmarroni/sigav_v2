@extends('layout.layout');
<style>
    .ui-autocomplete-loading {
        background: white url("/assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
@section("body")
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('/assets/img/photos/_photo25@2x.jpg');background-position-y:-280px;">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5" style="color:white">Pago proveedores</h1>
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

   
    <div class="block block-rounded" id="add_success_error" style="display: none;background-color: #d26a5c !important;color:white;">
        <div class="block-header">
            <div class="col-xs-12 bg-danger" id="nombre-devuelto-error"></div>
        </div>
    </div>
     <form class="form-horizontal" action="/pagoProveedores.saveFactura" method="post">
    <div class="block block-rounded">
        <div class="block-content">
             
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="detalleProductos" id="detalleProductos"/>
                <input type="hidden" name="montoTotal" id="montoTotal" value="0" />
                <div class="form-group">
                    <div class="col-xs-4">
                       <label>Proveedor (*)</label>
                       <select class="form-control" name="proveedor" id="proveedor">
                        <option value="0" selected> Seleccione</option>
                        @foreach($proveedores as $proveedor)
                        <option value={{$proveedor->id}}>{{$proveedor->nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Número factura (*)</label>
                    <input class="form-control" type="text" id="numerofactura" name="numerofactura" placeholder="1543" value="">
                </div>
                <div class="col-xs-4">
                    <label for="bd-qsettings-name">Fecha factura (*)</label>
                    <input class="form-control" type="date" id="fecha" name="fecha" placeholder="dd/mm/yyyy" value="2021-02-23">
                </div>
            </div>
       
    </div>
</div>
<div class="block block-rounded">
   <h4 class="h4 font-w700 text-white animated fadeInDown push-5">Detalle factura</h4>
   <div class="block-content">

        <div class="form-group">
            <div class="col-xs-3">
                <label for="bd-qsettings-name">Código de Barras</label>
                <input class="form-control" type="text" id="codigo-barras" name="codigo-barras" placeholder="Lea o ingrese el codigo de barras" value="">
            </div>
            <div class="col-xs-3">
                <label for="bd-qsettings-name">Nombre</label>
                <input class="form-control" type="text" id="nombre-producto" name="nombre-producto" placeholder="Ingrese parte del nombre" value="">
                <input class="form-control" type="hidden" id="producto_id" name="producto_id" placeholder="" value="">
                <input class="form-control" type="hidden" id="imagen-producto" name="imagen-producto" placeholder="" value="">
            </div>
            <div class="col-xs-1">
                <label for="bd-qsettings-name">Cantidad</label>
                <input class="form-control numbers" type="text" id="cantidad" name="cantidad" placeholder="1,2,3..." value="">
            </div>
            <div class="col-xs-1">
                <label>Precio</label>
                <input class="form-control prices" type="text" id="precio" name="precio" placeholder="0.00" value="">
            </div>
            <div class="col-xs-2">
                <label>Subtotal</label>
                <input class="form-control" type="text" id="subtotal" name="subtotal" placeholder="0.0" disabled>
            </div>
            <div class="col-xs-2">
                <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="anadir_producto" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                    <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                </button>
            </div>
        </div>
 <div style="margin-left: 500px">
        <button class="btn btn-sm btn-minw btn-rounded btn-primary" type="button" id="btnguardar" style="margin-top: 20px;margin-bottom: 20px;width: 30%;">
                <i class="fa fa-check push-5-r"></i>Guardar factura 
            </button>
             </div>
    </form>
</div>
</div>

<!-- Products -->
<div class="block block-rounded">
    <div class="block-header">
        <h3 class="block-title">Productos Total: $ <label id="totalFactura" style="font-size:15px;">0.00</label>
        </h3>
    </div>
    <div class="block-content" style="text-align: center">
       
           
              
          
        
    <div class="table-responsive">
        <table class="table table-hover table-vcenter">
            <tbody id="tablaProductos">

            </tbody>
        </table>
    </div>
</div>
<!-- END Page Content -->
@endsection
@section("scripts")
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/proveedores/pagoProveedores.js?v=1.13"></script>    
@endsection
