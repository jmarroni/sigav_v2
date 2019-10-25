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
$menu["cargas"] = "";
$menu["reportes"] = "";
$menu["actualizaciones"] = "active";
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

    <div class="block block-rounded">
        <div class="block-content">
            <form class="form-horizontal" action="/artesano_post.php" method="post" >
                <input type="hidden" value="" name="id_proveedor" id="id_proveedor"/>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="" placeholder="Nombre del Artesano" />
                    </div>
                    <div class="col-xs-4">
                        <label>Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="" placeholder="Apelllido  del Artesano" />
                    </div>
                    <div class="col-xs-4">
                        <label>Direcci&oacute;n</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="" placeholder="Alvear 453 local 3" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label>Ciudad</label>
                        <input type="text" class="form-control" id="ciudad" name="ciudad" value="" placeholder="Viedma" />
                    </div>
                    <div class="col-xs-4">
                        <label>Provincia</label>
                        <input type="text" class="form-control" id="provincia" name="provincia" value="" placeholder="Rio Negro" />
                    </div>
                    <div class="col-xs-4">
                        <label>Telefono / Celular</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="" placeholder="2920 425672" />
                    </div>

                </div>
                <div class="form-group">
                    <div class="col-xs-4">
                        <label>Categorias de Producci&oacute;n</label>
                        <select class="form-control" id="categoria" multiple name="categoria[]">
                            <?php
                            $sql = "SELECT * FROM `categorias` ORDER BY nombre";
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
                        <label>Mail</label>
                        <input type="text" class="form-control" id="mail" name="mail" value="" placeholder="mail@mail.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Sitio web <small>(url)</small></label>
                        <input type="url" class="form-control" id="sitio" name="sitio" value="" placeholder="www.ejemplo.com"/>
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;margin-top: 7%;" type="submit">
                            <i class="fa fa-check push-5-r"></i>A&ntilde;adir
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensaje no se pudo eliminar artesano -->
    <div id="erroreliminar" class="alert alert-danger text-center hidden" role="alert" style="position: fixed; bottom: 20px; width: 100%;">
        <p style="font-weight: bold;">No se puede eliminar este artesano <small style="font-weight: normal;">Debe eliminar todos sus productos primero</small></p>
    </div>

    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-header">
            <h3 class="block-title">Artesanos</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
        <?php
        $sql = "SELECT p.*
                FROM proveedor p
                ORDER BY p.`nombre`";
                $resultado = $conn->query($sql);
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { ?>
                            <tr>
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png" alt="">
                                        </div>
                                    </td>
                                <td>
                                    <h4><?php echo $row["nombre"]." ".$row["apellido"] ?> (<a href="javascript:void();" onclick="eliminarArtesano('<?php echo $row["id"]; ?>')">Eliminar</a> , <a href="#" onclick="modificarArtesano('<?php echo $row["id"]; ?>')">Modificar</a>)
                                        
                                     <p class="remove-margin-b">Ingresado por <?php echo $row["usuario"]; ?></p>
                                    </td>
                                <td>
                                    <p class="remove-margin-b">Telefono: <span class="text-gray-dark"><?php echo  $row["telefono"] ?></span></p>
                                    <p>Mail: <span class="text-gray-dark"><?php echo $row["mail"]; ?></span></p>
                                    <p>Sitio web: <a href="<?php echo $row["sitio_web"] ?>" target="_blank" class="text-gray-dark"><?php echo $row["sitio_web"] ?></a></p> 
                                </td>
                                <td class="text-center">
                                    <span class="text-gray-dark" >Categorias:<br>
                                    <?php 
                                    $query = "SELECT 
                                    c.`nombre`,
                                    c.`abreviatura` 
                                    FROM `relacion_categoria_proveedor` rcp 
                                    INNER JOIN categorias c 
                                      ON c.id = rcp.`categoria_id` 
                                    WHERE rcp.`proveedor_id` = ".$row["id"];
                                    $resultados = "-";
                                                    $resultado_ = $conn->query($query);
                                                    if ($resultado_->num_rows > 0) {
                                                        $resultados = "";
                                                        // output data of each row
                                                        while($row_ = $resultado_->fetch_assoc()) {
                                                            $resultados .= ($resultados != "")?", ".$row_["nombre"]:$row_["nombre"];
                                                        }
                                                    }
                                    echo $resultados; ?>
                                     </span>
                                </td>
                            </tr>
                        <?php }
                    } else {?>
                        <tr>
                            <td>
                                <label  style="text-align: center;padding-bottom: 15px;font-weight: bold;width: 100%;">No hay ventas en el d&iacute;a de hoy</label>
                            </td>
                        </tr>
                    <?php }
                    $conn->close();
                    ?>
                                        </tbody>
                </table>
            </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
});

function eliminarArtesano(identificador){
    if (confirm('Seguro que desea eliminar el artesano?')) {
        $.get("get_productos_artesano.php", { id_artesano: identificador }, function(data, status) {
            if (status === 'success') {
                if ( data.id ) {
                    $("#erroreliminar").fadeIn();
                    $("#erroreliminar").removeClass("hidden");
                    
                    setTimeout( function() {
                        $("#erroreliminar").fadeOut();
                    }, 3000);
                } else {
                    document.location.href = './artesano_post.php?action=1&id=' + identificador;
                }
            }
        });
    }
}

function modificarArtesano(identificador) {
    $.get("get_proveedores.php", { id_artesano: identificador }, function(data, status) {
        if (status === 'success') {
            $("#id_proveedor").val(data.id);
            $("#nombre").val(data.nombre);
            $("#apellido").val(data.apellido);
            $("#direccion").val(data.direccion);
            $("#ciudad").val(data.ciudad);
            $("#provincia").val(data.provincia);
            $("#telefono").val(data.telefono);
            $("#mail").val(data.mail);
            $("#sitio").val(data.sitio_web);
            $("#categoria").val(data.categoria_id);
        }
    });
}
</script>
<?php require ("footer.php"); ?>