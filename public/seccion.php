<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
if (getRol() < 4) {
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
$menu["usuario"] = "active";
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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Usuarios</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Bajas/Modificaciones de usuarios</h2>
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
            <form class="form-horizontal" action="/usuarios_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Usuario</label>
                        <input type="text" class="form-control" name="usuario" id="usuario" value="" placeholder="sin espacios" />
                    </div>
                    <div class="col-xs-4">
                        <label>Clave</label>
                        <input type="text" class="form-control" name="costo" id="costo" value="" placeholder="Costo por unidad" />
                    </div>
                    <div class="col-xs-4">
                        <label>Rol</label>
                        <select class="form-control" name="rol" id="rol" >
                            <option value="1">Vendedor</option>
                            <option value="2">Carga</option>
                            <option value="3">Carga y Transferencia</option>
                            <option value="4">Sucursales</option>
                            <option value="5">Administrador</option>
                        </select>
                    </div>
                </div><div  class="form-group">
                    <div class="col-xs-6">
                        <label>Nombre Completo</label>
                        <input type="text" class="form-control" name="stock" id="stock" value="" placeholder="stock" />
                    </div>
                    <div class="col-xs-6">
                        <label>Apellido</label>
                        <input type="text" class="form-control" name="stock_minimo" id="stock_minimo" value="" placeholder="Alerta Stock Minimo" />
                    </div>
                    
                </div>
                <div class="form-group">
                    
                    <div class="col-xs-4">
                        <label>Telefono</label>
                        <input type="text" class="form-control" name="telefono" id="telefono" value="">

                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Sucursal</label>
                        <select class="form-control" name="sucursales" id="sucursales" >
                        <?php
                        $sql = "SELECT *
                            FROM sucursales
                            ORDER BY nombre DESC ";
                            $resultado = $conn->query($sql) or die($conn->error);
                            $datos = '{"data":"no data"}';
                            if ($resultado->num_rows > 0) {
                                // output data of each row
                                while($row = $resultado->fetch_assoc()) {
                            ?>
                            <option value="<?php echo $row["id"]; ?>"><?php echo $row["nombre"]; ?></option>
                                <?php }} ?>
                        </select>
                    </div>
                    <div class="col-xs-4">
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
                <h3 class="block-title">USUARIOS</h3>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT  u.*,s.nombre as nombre_sucursal
                                FROM usuarios u 
                                INNER JOIN sucursales s
                                ON s.id = u.sucursal_id
                                order by nombre DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { ?>
                            <tr id="articulo_<?php echo strtolower($row["nombre"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4>Usuario: <?php echo $row["usuario"]; ?> </h4>
                                    <p class="remove-margin-b">Nombre y Apellido: <b><?php echo $row["nombre"].", ".$row["apellido"]; ?></b></p>
                                    <button onclick="modificar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i>Eliminar
                                    </button>
                                </td>
                                <td>
                                    <p class="remove-margin-b">Tel: <span class="text-gray-dark"><?php echo $row["telefono"];//$row["precio_unidad"]; ?></span>
                                    </p>
                                    <p class="remove-margin-b">Sucursal: <span class="text-gray-dark"> <?php echo $row["nombre_sucursal"]; ?></span></p>
                                    <p class="remove-margin-b">Rol: <span class="text-gray-dark"><?php 
                                    
                                    switch ($row["rol_id"]) {
                                        case '1': echo "Vendedor";break;
                                        case '2': echo "Carga";break;
                                        case '3': echo "Carga y Transferencia";break;
                                        case '4': echo "Sucursales";break;
                                        case '5': echo "Administrador";break;
                                        
                                        default:
                                            echo "Sin asignar";
                                            break;
                                    } ?></span>
                                    </p>
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
<script src="/assets/js/pages/carga.js?v=1"></script>
<?php require ("footer.php"); ?>