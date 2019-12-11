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
$menu["cargas"] = "active";
$menu["reportes"] = "";
require ('header.php'); 
?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op" id="bg-black-op">
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

    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/carga_post.php" enctype="multipart/form-data" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" name="producto" id="producto" value="" placeholder="Nombre del Producto Completo" />
                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Codigo de Barras <i>(autogenerado si se deja en blanco)</i> </label>
                        <input type="text" class="form-control" name="codigo_de_barras" id="codigo_de_barras" value="" placeholder="Codigo de Barras" />
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
                        <label>Rubro</label>
                        <select class="form-control" id="proveedor" name="proveedor">
                            <option value="0">Seleccione un rubro</option>
                       <?php $sql = "SELECT p.*
                                    FROM proveedor p
                                    ORDER BY p.`nombre`";
                                    $resultado = $conn->query($sql);
                                        if ($resultado->num_rows > 0) {
                                            // output data of each row
                                            while($row = $resultado->fetch_assoc()) { ?>
                            <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                        <?php }
                        } ?>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label>Categoria</label>
                        <select class="form-control" disabled="disabled" name="categoria" id="categoria">
                            <option value="">Seleccione un rubro</option>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label>Material</label>
                        <input type="text" class="form-control" name="material" id="material" value="" placeholder="Madera, Metal, Alphaca" />
                    </div>
                </div>
                <div class="form-group">
                <div class="col-xs-4">
                        <label>Costo</label>
                        <input type="text" class="form-control" name="costo" id="costo" value="" placeholder="Costo por unidad" />
                    </div>
                    <div class="col-xs-4">
                        <label>Precio Minorista</label>
                        <input type="text" class="form-control" name="precio_unidad" id="precio_unidad" value="" placeholder="Precio unidad (. para decimales 5.5)" />
                    </div>
                    <div class="col-xs-4">
                        <label>Precio Mayorista <i>(Solo si utiliza)</i></label>
                        <input type="text" class="form-control" name="precio_mayorista" id="precio_mayorista" value="" placeholder="Precio mayorista (. para decimales 5.5)" />
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
                        <label>Descripci&oacute;n</label>
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
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
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
            <div class="col-xs-8" > 
                <h3 class="block-title">Filtro Producto</h3>
                <input  class="form-control" type="text" placeholder="Ingrese parte del producto" value="" id="filtro" name="filtro" />
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT 
                                p.*,
                                CONCAT(c.nombre,', ',c.abreviatura) as categoria,
                                CONCAT(prov.nombre,', ',prov.apellido) as proveedor,
                                descripcion
                                FROM productos p 
                                LEFT JOIN proveedor prov
                                ON prov.id = p.proveedores_id
                                LEFT JOIN categorias c
                                ON c.id = p.categorias_id
                                order by id DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) {
                            $costo = $row["costo"];
                            $precio = $row["precio_unidad"];
                            $datos = $row;
                            ?>

                            <tr id="articulo_<?php echo strtolower($row["nombre"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                    <?php
                                        $sql = "SELECT ip.imagen_url as imagen 
                                                FROM imagen_producto ip
                                                WHERE productos_id = ".$row['id'];
                                        $resultadoImagenes = $conn->query($sql) or die($conn->error);
                                        $datosImagenes = '{"data":"no data"}';
                                        if ($resultadoImagenes->num_rows > 0) {
                                            $numero = 1;
                                            while($rowImagen = $resultadoImagenes->fetch_assoc()) {
                                                if ($numero === 1) {
                                        ?>
                                                    <div style="width: 180px;">
                                                        <img class="img-responsive" src="<?php echo (isset($rowImagen["imagen"]))?$rowImagen["imagen"]:"assets/img/photos/no-image-featured-image.png"; ?>" alt="" />
                                                    </div>
                                    <?php
                                                    $numero++;
                                                }   else {
                                    ?>   
                                                    <div style="width: 90px; float: left;">
                                                        <img class="img-responsive" src="<?php echo (isset($rowImagen["imagen"]))?$rowImagen["imagen"]:"assets/img/photos/no-image-featured-image.png"; ?>" style="max-height: 70px; min-height: 70px;" alt="" />
                                                    </div>
                                    <?php
                                                }  
                                            }
                                        }
                                    ?>
                                    </div>
                                </td>
                                <td>
                                    <h4><?php echo $row["nombre"]; ?> </h4>
                                    <p class="remove-margin-b">Codigo: <b><?php echo $row["codigo_barras"]; ?></b></p>
                                    <button onclick="modificar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i>Eliminar
                                    </button>
                                </td>
                                <td>
                                    <p class="remove-margin-b"><b>Artesano: </b><span class="text-gray-dark"><?php echo (isset($row["proveedor"]))?$row["proveedor"]:"";//$row["precio_unidad"]; ?></span>
                                    </p>
                                    <p class="remove-margin-b"><b>Categoria: </b><span class="text-gray-dark"> <?php echo (isset($row["categoria"]))?$row["categoria"]:""; ?></span></p>
                                    <p class="remove-margin-b"><b>Descripci&oacute;n:</b><br><?php echo nl2br($row["descripcion"]); ?></p>
                                </td>
                                <td class="text-center">
                                    <p class="remove-margin-b">Costo:$ <span class="text-gray-dark"> <?php echo $row["costo"]; ?></span></p>
                                    <span class="h4 font-w700 text-success">Minorista $ <?php echo $row["precio_unidad"]; ?></p> </span>
                                    <span class="h4 font-w700 text-success">Mayorista $ <?php echo ($row["precio_mayorista"] != "")?$row["precio_mayorista"]:"N/A"; ?></p> </span>
                                </td>
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No hay productos</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="assets/js/pages/carga.js?v=1.08"></script>
<?php require ("footer.php"); ?>