<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
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
$menu["cargas"] = "";
$menu["reportes"] = "";
$menu["actualizar"] = "active";
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
                <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td style="width: 10%">ID</td>
                                    <td style="width: 300px;">Nombre</td>
                                    <td style="width: 10%">Proveedor</td>
                                    <td style="width: 10%">Codigo</td>
                                    <td style="width: 10%">Precio</td>
                                    <td style="width: 10%">Costo</td>
                                    <td style="width: 10%">Stock</td>
                                    <td style="width: 10%">Stock Minimo</td>
                                    <td>Acci&oacute;n</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "select * from productos order by nombre DESC";
                            $resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
                            if ($resultado->num_rows > 0) {
                                $i = 1;
                                while($row = $resultado->fetch_assoc()) {?>
                                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                                        <td><?php echo $row["id"]; ?></td>
                                        <td><?php echo $row["nombre"]; ?></td>
                                        <td><select id="proveedor_<?php echo $row["id"]; ?>" name="proveedor_<?php echo $row["id"]; ?>">
                                            <?php
                                            $sql_proveedor = "SELECT * FROM `proveedores`";
                                            $resultado_proveedor = $conn->query($sql_proveedor);
                                            if ($resultado_proveedor->num_rows > 0) {
                                                // output data of each row
                                                while($row_proveedor = $resultado_proveedor->fetch_assoc()) {
                                                    ?>
                                                    <option <?php echo ($row["proveedores_id"] == $row_proveedor["id"])?"selected='selected'":""; ?> value="<?php echo $row_proveedor["id"]; ?>"><?php echo $row_proveedor["nombre"]; ?></option>
                                                <?php }} ?>
                                        </select></td>
                                        <td><input style="width: 120px" type="text" id="codigo_barras_<?php echo $row["id"]; ?>" name="codigo_barras_<?php echo $row["id"]; ?>" value="<?php echo $row["codigo_barras"]; ?>" /></td>
                                        <td><input style="width: 80px" type="text" id="precio_unidad_<?php echo $row["id"]; ?>" name="precio_unidad_<?php echo $row["id"]; ?>" value="<?php echo $row["precio_unidad"]; ?>" /></td>
                                        <td><input style="width: 80px" type="text" id="costo_<?php echo $row["id"]; ?>" name="costo_<?php echo $row["id"]; ?>" value="<?php echo $row["costo"]; ?>" /></td>
                                        <td><input style="width: 50px" type="text" id="stock_<?php echo $row["id"]; ?>" name="stock_<?php echo $row["id"]; ?>" value="<?php echo $row["stock"]; ?>" /></td>
                                        <td><input style="width: 50px" type="text" id="stock_minimo_<?php echo $row["id"]; ?>" name="stock_minimo_<?php echo $row["id"]; ?>" value="<?php echo $row["stock_minimo"]; ?>" /></td>
                                        <td><button id="boton_actualizar_<?php echo $row["id"]; ?>" class="btn btn-primary" onclick="actualizarProducto(<?php echo $row["id"]; ?>);">Actualizar</button></td>
                                    </tr>
                                <?php $i++;}
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
        </div>
        <!-- END Page Content -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/assets/js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<?php require ("footer.php"); ?>
<script type="text/javascript">
    $(document).ready(function(){
        $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            }
        });
    });

    function actualizarProducto(identificador){
        var datos_actualizar = "?codigo_barras=" + $("#codigo_barras_" + identificador).val() +
            "&precio_unidad=" + $("#precio_unidad_" + identificador).val() +
            "&costo=" + $("#costo_" + identificador).val() +
            "&stock=" + $("#stock_" + identificador).val() +
            "&stock_minimo=" + $("#stock_minimo_" + identificador).val() +
            "&proveedores_id=" + $("#proveedor_" + identificador).val() +
            "&id=" + identificador;

        $.ajax({
            method: "POST",
            url: "carga_post.php" + datos_actualizar,
        })
        .done(function (msg) {
            if (msg == "OK"){
                $("#boton_actualizar_" + identificador).html("OK");
            }
        });
    }
</script>