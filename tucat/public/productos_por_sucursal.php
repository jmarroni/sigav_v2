<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 2) {
    exit();
}

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

$sucursal_activa = (getSucursal($_COOKIE["sucursal"]) !== null)?getSucursal($_COOKIE["sucursal"]):"";

if ($sucursal_activa == "") {
    exit();
}

// Calculo para la paginacion
$sql = "SELECT p.id, p.nombre, p.codigo_barras, p.costo, p.precio_unidad, p.precio_mayorista, c.nombre AS nombre_categoria, pro.nombre AS proveedor_nombre
    FROM productos p 
    LEFT JOIN categorias c ON (p.categorias_id = c.id) 
    LEFT JOIN proveedor pro ON (p.proveedores_id = pro.id)
    ORDER BY p.id DESC";

$resultado = $conn->query($sql);

$productos_por_pagina = 25;
$total_productos_db = $resultado->num_rows;
$paginas = ceil($total_productos_db / $productos_por_pagina);

require ('header.php'); ?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }

    th:hover {
        cursor: pointer;
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
                <h2>Productos</h2>
            </div>
        </div>
        <div class="block-content">
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal">
                    Agregar producto
                </button>
            </div>

            <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <form class="form-horizontal" action="./productos_por_sucursal_crud.php" enctype="multipart/form-data" method="post" >
                                <input type="hidden" value="" name="id" id="id" />
                                <input type="hidden" value="<?php echo $sucursal_activa ?>" name="sucursal" id="sucursal"/>
                                <div class="form-group">
                                    <div class="col-xs-4">
                                        <label for="bd-qsettings-name">Nombre</label>
                                        <input type="text" class="form-control" name="producto" id="producto" value="" placeholder="Nombre del Producto Completo" />
                                    </div>
                                    <div class="col-xs-8">
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
                                        <label>Stock</label>
                                        <input type="text" class="form-control" name="stock_sucursal" id="stock_sucursal" value="" placeholder="Stock"/>
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
                                <div class="form-group">
                                    <div class="col-xs-4">
                                        <label for="bd-qsettings-name">Cargar Imagen</label>
                                        <input type="file" class="form-control" readonly name="imagen1" id="imagen1" value="" />
                                    </div>
                                    <div class="col-xs-4">
                                        <label for="bd-qsettings-name">Cargar Imagen</label>
                                        <input type="file" class="form-control" readonly name="imagen2" id="imagen2" value=""  />
                                    </div>
                                    <div class="col-xs-4">
                                        <label for="bd-qsettings-name">Cargar Imagen</label>
                                        <input type="file" class="form-control" readonly name="imagen3" id="imagen3" value="" />
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div style="margin-top: 35px;" class="col-xs-12">
                                        <button class="btn btn-primary" type="submit">
                                            Aceptar
                                        </button>
                                        <button onclick="limpiar()" type="button" class="btn btn-secondary" data-dismiss="modal">Atras</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <table class="table-responsive">
                    <thead class="thead-dark">
                        <th class="hidden">Id</th>
                        <th class="text-center">Imagen</th>
                        <th class="text-center">Nombre</th>
                        <th class="text-center">Codigo de barras</th>
                        <th class="text-center">Rubro</th>
                        <th class="text-center">Categoria</th>
                        <th class="text-center">Costo</th>
                        <th class="text-center">Precio minorista</th>
                        <th class="text-center">Precio mayorista</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Opciones</th>
                    </thead>
                    <tbody>
                        <?php
                            $iniciar = ($_GET["pagina"]-1) * $productos_por_pagina;

                            $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.costo, p.precio_unidad, p.precio_mayorista, c.nombre AS nombre_categoria, pro.nombre AS proveedor_nombre
                                FROM productos p 
                                LEFT JOIN categorias c ON (p.categorias_id = c.id) 
                                LEFT JOIN proveedor pro ON (p.proveedores_id = pro.id)
                                ORDER BY p.id DESC
                                LIMIT $iniciar, $productos_por_pagina";

                            $resultado = $conn->query($sql);

                            if ($resultado->num_rows > 0) {
                                // output data of each row
                                while($row = $resultado->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td class="hidden">
                                        <?php echo $row["id"] ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $sql_imagenes = "SELECT imagen_url FROM imagen_producto WHERE productos_id = '{$row['id']}'";
                                            $resultado_imagenes = $conn->query($sql_imagenes) or die($conn->error." --- ".$sql_imagenes);

                                            $numero = 1;
                                            while($row_imagenes = $resultado_imagenes->fetch_assoc()) {
                                                if ($numero === 1) {
                                                    ?>
                                                        <div style="width: 140px;">
                                                            <img class="img-responsive" src="<?php echo (isset($row_imagenes["imagen_url"]))?$row_imagenes["imagen_url"]:"assets/img/photos/no-image-featured-image.png"; ?>" alt="" />
                                                        </div>
                                                <?php
                                                                $numero++;
                                                }   else {
                                                ?>   
                                                        <div style="width: 70px; float: left;">
                                                            <img class="img-responsive" src="<?php echo (isset($row_imagenes["imagen_url"]))?$row_imagenes["imagen_url"]:"assets/img/photos/no-image-featured-image.png"; ?>" style="max-height: 70px; min-height: 70px;" alt="" />
                                                        </div>
                                                <?php
                                                }
                                            }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                       <?php echo $row["nombre"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["codigo_barras"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["proveedor_nombre"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["nombre_categoria"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["costo"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["precio_unidad"] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row["precio_mayorista"] ?>
                                    </td>
                                    <?php 
                                        $sql_stock = "SELECT stock 
                                            FROM stock 
                                            WHERE sucursal_id = '{$sucursal_activa}' AND productos_id = '{$row['id']}'";

                                            $resultado_stock = $conn->query($sql_stock) or die($conn->error." --- ".$sql_stock);
                                            
                                            if ($resultado_stock->num_rows > 0) {
                                                while($row_stock = $resultado_stock->fetch_assoc()) {
                                    ?>
                                                <td id="stock_de_sucursal<?php echo $row['id'] ?>" class="text-center"><?php echo $row_stock["stock"] ?></td>
                                                <td>
                                                    <button style="margin: 2px;" onclick="modificar('<?php echo $row['id']?>', '<?php echo $sucursal_activa ?>');" class="btn btn-warning" data-toggle="modal" data-target="#modal">Modificar</button>
                                                    <?php if ($row_stock["stock"] != 0) { ?>
                                                        <button style="margin: 2px;" onclick="eliminar('<?php echo $row['id']?>', '<?php echo $sucursal_activa ?>');" class="btn btn-danger">Eliminar</button>
                                                    <?php } ?>
                                                </td>
                                    <?php
                                                }
                                            } else {
                                    ?>
                                                <td id="stock_de_sucursal<?php echo $row['id'] ?>" class="text-center">0</td>
                                                <td>
                                                    <button style="margin: 2px;" onclick="modificar('<?php echo $row['id']?>', '<?php echo $sucursal_activa ?>');" class="btn btn-warning" data-toggle="modal" data-target="#modal">Modificar</button>
                                                </td>
                                    <?php
                                            }
                                    ?>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td class="alert alert-info"> No hay productos </td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                    <td class="alert alert-info"></td>
                                </tr>
                            <?php 
                            }
                            ?>
                    </tbody>
                </table>

                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($_GET["pagina"] > 1) {?>
                            <li>
                                <a href="productos_por_sucursal.php?pagina=<?php echo $_GET["pagina"] - 1 ?>" aria-label="Previous">
                                    Anterior
                                </a>
                            </li>
                        <?php } ?>
                        <?php for($i = 1; $i <= $paginas; $i++): ?>
                            <li>   
                                <a href="productos_por_sucursal.php?pagina=<?php echo $i ?>">
                                    <?php echo $i?>
                                </a>
                            </li>
                        <?php endfor ?>
                        <?php if ($_GET["pagina"] < $paginas) {?>
                            <li>
                                <a href="productos_por_sucursal.php?pagina=<?php echo $_GET["pagina"] + 1 ?>" aria-label="Next">
                                    Siguiente
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        jQuery("document").ready(function() {
            setTimeout(function () {
                $("#add_success").hide('slow');
            }, 3000);

            $("#proveedor").change(function(){
                if ($(this).val() != 0){
                    $.post("list_categorias.php?proveedor=" + $(this).val(), function(data, status){  
                        $("#categoria").html(data);
                        $("#categoria").removeAttr("disabled");
                    });
                }else{
                    $("#categoria").html("<option value='0'>Seleccione un artesano</option>");
                    $("#categoria").attr("disabled","disabled");
                }
            });

            $("#filtro").keyup(function() {
                $("tr[id^='articulo_']" ).each(function( index ) {
                    $(this).hide();
                });
                $("tr[id^='articulo_" + $(this).val().toLowerCase() + "']" ).each(function( index ) {
                    $(this).show();
                });
            });
        });

        function modificar(identificador, sucursal) {
            $.get("get_productos_stock_por_sucursal.php?identificador=" + identificador + "&sucursal=" + sucursal, function(data, status){
                console.log(data);
                var jsonData = data;
                $("#id").val(jsonData.id);
                $("#producto").val(jsonData.nombre);
                $("#costo").val(jsonData.costo);
                $("#precio_unidad").val(jsonData.precio_unidad);
                $("#codigo_de_barras").val(jsonData.codigo_barras);
                $("#proveedor").val(jsonData.proveedores_id);
                //
                $("#stock").val(jsonData.stock);
                $("#stock_sucursal").val($("#stock_de_sucursal"+identificador)[0].innerHTML);
                $("#stock_minimo").val(jsonData.stock_minimo);
                $("#precio_mayorista").val(jsonData.precio_mayorista);
                if (jsonData.es_comodato) $("#es_comodato").prop('checked',true); else $("#es_comodato").prop('checked',false);;
                $("#proveedor").change();
                document.location.href = "#bg-black-op";
            });
        }

        function limpiar() {
            $("#id").val('');
            $("#producto").val('');
            $("#codigo_de_barras").val('');
            $("#stock").val(0);
            $("#stock_sucursal").val('');
            $("#costo").val('');
            $("#precio_unidad").val('');
            $("#precio_mayorista").val('');
            $("#imagen1").val('');
            $("#imagen2").val('');
            $("#imagen3").val('');
            $("#proveedor").val(0);
            $("#stock_minimo").val(0);
            $("#categoria").val(0);
        }

        function eliminar(identificador, sucursal){
            if (confirm('Esta seguro que desea eliminar el producto?')) {
                document.location.href = "productos_por_sucursal_crud.php?identificador=" + identificador + "&sucursal=" + sucursal + "&action=eliminar";
            }
        }
    </script>

<?php require ("footer.php"); ?>