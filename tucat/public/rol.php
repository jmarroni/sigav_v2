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
    <div id="formulario" class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op" id="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Roles</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Bajas/Modificaciones de roles</h2>
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
            <form class="form-horizontal" action="/rol_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Rol</label>
                        <input type="text" class="form-control" name="rol" id="rol" value="" placeholder="nombre de fantasia del rol" />
                    </div>
                    <div class="col-xs-3 col-xs-offest-1" style="margin-top:2%;">
                        <label class="css-input switch switch-success">
                            <input type="checkbox" id="habilitado" name="habilitado" ><span></span> Habilitado
                        </label>
                    </div>
                    <div class="col-xs-12" style="margin-top:20px;">
                        <?php
                        $sql_seccion = "SELECT * FROM seccion ORDER BY id DESC";
                        $resultado_seccion = $conn->query($sql_seccion);
                        if ($resultado_seccion->num_rows > 0) {
                            while ($row_seccion = $resultado_seccion->fetch_assoc()){
                                ?>
                                <div class="col-xs-2">
                                    <input style="width:20%;display:inline;float:right" type="checkbox" name="secciones[]" id="secciones_<?php echo $row_seccion["id"]; ?>" class="form-control" value="<?php echo $row_seccion["id"]; ?>" ><label style="float:left;margin-top:10px;"><?php echo $row_seccion["nombre"]; ?>&nbsp;</label>
                                </div>
                            <?php }
                        }
                        ?>
                    </div>
                    <div class="col-xs-4 col-xs-offset-4">
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
                <h3 class="block-title">ROLES</h3>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT  
                            r. id AS rol_id,
                            r.nombre AS rol,
                            r.`habilitado` AS rol_habilitado
                            FROM roles r                                    
                            ORDER BY r.nombre DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { 
                            $nombre = $row["rol"];
                            ?>
                            <tr id="articulo_<?php echo strtolower($row["rol_id"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4>Rol: <?php echo $nombre; ?> </h4>
                                    <p class="remove-margin-b">Secciones:
                                    <?php 
                                    $seccion_id = "";
                                    $sql_seccion = "SELECT  
                                            s.id AS seccion_id,
                                            s.nombre AS seccion
                                            FROM relacion_seccion_rol rsr
                                                INNER JOIN seccion s
                                                ON s.id = rsr.secciones_id 
                                            WHERE rsr.roles_id = ".$row["rol_id"]."                                   
                                            ORDER BY s.nombre DESC ";
                                    $resultado_seccion = $conn->query($sql_seccion) or die($conn->error);
                                    While ($seccion = $resultado_seccion->fetch_assoc()){
                                        echo ($seccion_id != "")?" - ".$seccion["seccion"]:$seccion["seccion"];
                                        $seccion_id .= ($seccion_id != "")?"|".$seccion["seccion_id"]:$seccion["seccion_id"];
                                    } ?>
                                    
                                     </p>
                                     <input type="hidden" value="<?php echo $seccion_id ?>" name="seccion_<?php echo $row["rol_id"]; ?>" id="seccion_<?php echo $row["rol_id"]; ?>" />
                                    <button onclick="modificar('<?php echo $row["rol_id"]; ?>','<?php echo $row["rol"]; ?>','<?php echo $row["rol_habilitado"]; ?>');"  class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["rol_id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-danger" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i>Eliminar
                                    </button>
                                </td>                                
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No hay Roles</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script>
function modificar(identificador,titulo,habilitado){
    $("#rol").val(titulo);
    $("#id").val(identificador);
    if (habilitado == 1){$("#habilitado").prop("checked",true);}else{$("#habilitado").prop("checked",false);}
    var secciones = $("#seccion_" + identificador).val().split("|");
    var i;
    for (i =0; i < secciones.length; i ++){
        $("#secciones_" + secciones[i]).prop("checked",true);
    }
    document.location.href ="#formulario";
}
</script>
<?php require ("footer.php"); ?>