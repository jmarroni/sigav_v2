<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 2) {
    exit();
}
//Productos vendidos hoy por el usuario
$sql = "SELECT * FROM `productos`";
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
$caja = 540;
if ($resultado->num_rows > 0) {
    $total = $resultado->num_rows; 
}

$menu["ventas"] = "";
$menu["stock_sucursales"] = "active";
$menu["reportes"] = "";
$sucursal_activa = (isset($_GET["sucursal"]) &&  intval($_GET["sucursal"]))?$_GET["sucursal"]:"";
require ('header.php'); ?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Carga</h1>
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

    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <div class="col-xs-4" > 
                <h3 class="block-title">Productos En </h3>
                <select class="form-control" id="sucursal" name="sucursal">
                <?php $sql = "SELECT *
                                FROM sucursales
                                ORDER BY `nombre`";
                            $resultado = $conn->query($sql) or $conn->error;
                            $i = 0;
                                // if ($resultado->num_rows > 0) {
                                    // output data of each row
                                    while($row = $resultado->fetch_assoc()) { 
                                        if ($sucursal_activa == "" && $i == 0) $sucursal_activa = $row["id"]; $i ++;
                                        ?>
                    <option value="<?php echo $row["id"]; ?>" <?php echo ($sucursal_activa == $row["id"])?"selected='selected'":''; ?>><?php echo $row["nombre"]; ?></option>
                <?php } ?>
                </select>
            </div>
            <div class="col-xs-4" > 
                <h3 class="block-title">Filtro Producto</h3>
                <input  class="form-control" type="text" placeholder="Ingrese parte del producto" value="" id="filtro" name="filtro" />
            </div>
        </div>
        <div class="block-content">
<!-- Page Content -->
            <!-- Settings -->
                <div class="block">
                    <ul class="nav nav-tabs nav-justified push-20" style="width:100%;overflow-x:auto;" data-toggle="tabs">
                        <?php
                        $sql = "SELECT 
                        p.*,
                        CONCAT(c.nombre,', ',c.abreviatura) as categoria,
                        CONCAT(prov.nombre,', ',prov.apellido) as proveedor,
                        s.stock as stock_sucursal,
                        s.stock_minimo as stock_minimo_sucursal,
                        ip.imagen_url as imagen
                        FROM productos p 
                        Left JOIN proveedor prov
                        ON prov.id = p.proveedores_id
                        Left JOIN categorias c
                        ON c.id = p.categorias_id
                        LEFT JOIN stock s
                        ON p.id = s.productos_id and s.sucursal_id = ".$sucursal_activa." 
                        LEFT JOIN imagen_producto ip 
                        ON ip.productos_id = p.id
                        order by p.nombre ASC ";
                        $resultado = $conn->query($sql) or die($sql." -- ".$conn->error);
                        $datos = '{"data":"no data"}';
                        $contador_letra = 0;
                        if ($resultado->num_rows > 0) {
                            // output data of each row
                            while($row = $resultado->fetch_assoc()) {
                                $letra = strtoupper(substr($row["nombre"], 0,1));
                                if (isset($productos[$letra])){
                                    $contador_letra ++;
                                    // $productos[$letra] = $contador_letra; 
                                }
                                else{
                                    $contador_letra = 1;
                                    //$productos[$letra] = $contador_letra; 
                                }
                                $productos[$letra][$contador_letra] = $row;
                            }
                        }  
                        $i = 0;
                        // echo "<pre>";
                        // print_r($productos);
                        // exit();
                        foreach ($productos as $key => $char) { ?>
                            <li <?php echo ($i == 0)?'class="active"':''; $i ++; ?>>
                                <a href="#tab-bd-<?php echo $key ?>"><i class="fa fa-fw fa-pencil"></i>
                                    <?php echo $key."(".count($char).")"; ?>
                                </a>
                            </li>
                        <?php } ?> 
                    </ul>
                    <div class="block-content tab-content">
                        <!-- General Tab -->
                        <?php $i = 0; foreach ($productos as $key => $char) {
                            //  echo "<pre>"; print_r($char);exit();
                            ?>
                        <div class="tab-pane <?php echo ($i == 0)?'fade in active':''; $i ++; ?>" id="tab-bd-<?php echo $key ?>">
                            <div class="row items-push">
                            <?php foreach ($char as $row) { 
                                            $costo = $row["costo"];
                                            $precio = $row["precio_unidad"];
                                            $datos = $row;
                                ?>
                                <div class="table-responsive"  id="articulo_<?php echo strtolower($row["nombre"]); ?>">
                                    <table class="table table-hover table-vcenter">
                                        <tbody id="tablaProductos">
                                            <tr>
                                                <td class="text-center" style="width:20%">
                                                    <div style="width: 180px;">
                                                    <img class="img-responsive" src="<?php echo (isset($row["imagen"]))?$row["imagen"]:"assets/img/photos/no-image-featured-image.png"; ?>" alt="">

                                                    </div>
                                                </td>
                                                <td style="width:31%">
                                                    <h4><?php echo $row["nombre"]; ?> </h4>
                                                    <p class="remove-margin-b">Codigo: <b><?php echo $row["codigo_barras"]; ?></b></p>
                                                    <button onclick="actualizar_stock('<?php echo $row["id"]; ?>','<?php echo $sucursal_activa; ?>');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                                        <i class="fa fa-check push-5-r"></i>Actualizar stock
                                                    </button>
                                                    <p class="text-gray-dark" style="display:none" id="actualizado<?php echo "-".$sucursal_activa."-".$row["id"]; ?>">Actualizado :)</p>
                                                </td>
                                                <td>
                                                    <p class="remove-margin-b">Artesano: <span class="text-gray-dark"><?php echo $row["proveedor"];//$row["precio_unidad"]; ?></span>
                                                    </p>
                                                    <p class="remove-margin-b">Categoria: <span class="text-gray-dark"> <?php echo $row["categoria"]; ?></span></p>
                                                    <p>Stock: <span class="text-gray-dark"> <input style="width:10%;display:inline;" type="text" class="form-control" name="stock<?php echo "-".$sucursal_activa."-".$row["id"]; ?>" id="stock<?php echo "-".$sucursal_activa."-".$row["id"]; ?>" value="<?php echo (isset($row["stock_sucursal"]))?$row["stock_sucursal"]:"0"; ?>" placeholder="stock" /></span>&nbsp;&nbsp;Stock Minimo: <span class="text-gray-dark"> <input style="width:10%;display:inline;" type="text" class="form-control" name="stock_minimo<?php echo "-".$sucursal_activa."-".$row["id"]; ?>" id="stock_minimo<?php echo "-".$sucursal_activa."-".$row["id"]; ?>" value="<?php echo (isset($row["stock_minimo_sucursal"]))?$row["stock_minimo_sucursal"]:"0"; ?>" placeholder="stock" /></span></p>

                                                </td>
                                                <td class="text-center" style="width:12%">
                                                    <p class="remove-margin-b">Costo:$ <span class="text-gray-dark"> <?php echo $row["costo"]; ?></span></p>
                                                    <span class="h1 font-w700 text-success">$ <?php echo $row["precio_unidad"]; ?></p> </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                            </div>
                        </div>
                        <!-- END General Tab -->
                    <?php } ?>

                    </div>
                </div>
            <!-- END Main Content -->
        </div>
    </div>
    <!-- END Products -->
</div>    
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    var sucursal = 0;
    jQuery("document").ready(function(){
        

        $("#sucursal").change(function(){
            document.location.href = "?sucursal=" + $("#sucursal").val();
            
        });

        $("#filtro").keyup(function() {
            $("div[id^='articulo_']" ).each(function( index ) {
                $(this).hide();
            });
            $("div[id^='articulo_" + $(this).val().toLowerCase() + "']" ).each(function( index ) {
                $(this).show();
            });
        });

    });

    function actualizar_stock(producto_id, sucursal){
        var stock = $("#stock-" + sucursal + "-" + producto_id ).val();
        console.log(stock);
        var stock_minimo = $("#stock_minimo-" + sucursal + "-" + producto_id ).val();
        console.log(stock_minimo);
        datos_actualizar = "producto=" + producto_id + "&sucursal=" + sucursal + "&stock=" + stock + "&stock_minimo=" + stock_minimo;
        $.ajax({
            method: "POST",
            url: "actualizar_stock_por_sucursal.php?" + datos_actualizar,
        })
        .done(function (msg) {
            if (msg == "OK"){
                $("#actualizado-" + sucursal + "-" + producto_id).show();
                setTimeout(function(){$("#actualizado-" + sucursal + "-" + producto_id).hide();},3000);
            }
        });
    }
</script>
<script src="/assets/js/pages/carga.js"></script>
<?php require ("footer.php"); ?>