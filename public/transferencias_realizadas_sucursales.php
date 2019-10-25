<html>
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
$menu["stock_sucursales"] = "active";
$menu["reportes"] = "";

$sucursal_activa = (getSucursal($_COOKIE["sucursal"]) !== null)?getSucursal($_COOKIE["sucursal"]):"";

if ($sucursal_activa == "") {
    exit();
}

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
            <h3 class="block-title">Transferencias </h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-center text-center">
                    <thead>
                        <th class="text-center">Sucursal Origen</th>
                        <th class="text-center">Sucursal Destino</th>
                        <th class="text-center">Fecha origen</th>
                        <th class="text-center">Usuario</th>
                        <th class="text-center">Productos</th>
                        <th class="text-center">Comentarios</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Opciones</th>
                    </thead>
                    <tbody>
                        <?php
                            $sql = "SELECT t.*, 
                                            e.nombre AS nombre_estado, 
                                            e.id AS id_estado, 
                                            so.nombre AS sucursal_origen_nombre, 
                                            sd.nombre AS sucursal_destino_nombre
                                    FROM transferencias t 
                                        JOIN estado_transferencia e ON (t.estado_id = e.id)
                                        JOIN sucursales so ON (t.sucursal_origen_id = so.id)
                                        JOIN sucursales sd ON (t.sucursal_destino_id = sd.id)
                                    WHERE sucursal_origen_id = $sucursal_activa OR sucursal_destino_id = $sucursal_activa
                            ";

                            $resultado = $conn->query($sql) or die($sql." -- ".$conn->error);
                            $datos = '{"data":"no data"}';
                            
                            if ($resultado->num_rows > 0) {
                                while($row = $resultado->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td>
                                        <?php echo $row["sucursal_origen_nombre"] ?>
                                    </td>
                                    <td>
                                        <?php echo $row["sucursal_destino_nombre"] ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo date("d/m/Y h:m:s", strtotime($row["fecha"]));
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $row["usuario"] ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="<?php echo "#modal{$row['id']}"?>">
                                          Mostrar productos
                                        </button>

                                        <div class="modal fade" id="<?php echo "modal{$row['id']}"?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                          <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLongTitle">Productos</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">
                                                    <?php
                                                        $sql_productos = "SELECT rtp.*, p.*
                                                                        FROM relacion_transferencias_productos rtp 
                                                                        JOIN productos p ON (p.id = rtp.producto_id)
                                                                        WHERE rtp.tranferencia_id = '{$row['id']}'
                                                        ";
                                                        $resultado_productos = $conn->query($sql_productos) or die($sql_productos." -- ".$conn->error);
                                                        $datos_productos = '{"data":"no data"}';
                                                        if ($resultado_productos->num_rows > 0) {
                                                            while($row_productos = $resultado_productos->fetch_assoc()) {
                                                    ?>

                                                        <?php
                                                            $sql_imagenes_productos = "SELECT ip.*
                                                                            FROM imagen_producto ip
                                                                            WHERE ip.productos_id = {$row_productos['producto_id']}
                                                                            LIMIT 1
                                                            ";
                                                            $resultado_imagenes_productos = $conn->query($sql_imagenes_productos) or die($sql_imagenes_productos." -- ".$conn->error);
                                                            $datos_productos = '{"data":"no data"}';
                                                            if ($resultado_imagenes_productos->num_rows > 0) {
                                                                while($row_imagenes_productos = $resultado_imagenes_productos->fetch_assoc()) {
                                                        ?>
                                                            <div class="row">
                                                                <div style="float: left; width: 48%;">
                                                                    <img style="width: 50px; margin-top: 5px;" src="<?php echo $row_imagenes_productos["imagen_url"]?>" >
                                                                </div>
                                                        <?php 
                                                                }
                                                            }
                                                        ?>
                                                                <div style="float: left; width: 48%;">
                                                                    <p style="vertical-align: middle; text-align: center; vertical-align: middle; line-height: 50px;"> 
                                                                        <?php echo $row_productos["nombre"] ?> 
                                                                        <small>(<?php echo $row_productos["cantidad"] ?>)</small>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                    <?php  
                                                            }
                                                        }
                                                    ?>
                                                </ul>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Atras</button>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($row["sucursal_destino_id"] == $sucursal_activa) { ?>
                                            <textarea id="<?php echo "comentario{$row['id']}"?>" name="comentario"><?php echo $row["comentario"] ?></textarea>
                                        <?php } else { ?>
                                            <p><?php echo $row["comentario"] ?></p>
                                         <?php } ?>
                                    </td>
                                    <td class="
                                            <?php
                                                switch ($row["estado_id"]) {
                                                    case 1:
                                                        echo "active";
                                                        break;
                                                    case 2:
                                                        echo "info";
                                                        break;
                                                    case 3:
                                                        echo "warning";
                                                        break;
                                                    case 4:
                                                        echo "success";
                                                        break;
                                                    case 5:
                                                        echo "danger";
                                                        break;
                                                }
                                            ?>
                                        ">
                                        <?php
                                            if ($row["sucursal_destino_id"] == $sucursal_activa) {
                                        ?>
                                            <select name="estado" id="<?php echo "estado{$row['id']}"?>">
                                                <?php  
                                                    $sql_estados = "SELECT * FROM estado_transferencia";
                                                    $resultado_estados = $conn->query($sql_estados) or die($sql_estados." -- ".$conn->error);
                                                    $datos_estados = '{"data":"no data"}';
                                                    if ($resultado_estados->num_rows > 0) {
                                                        while($row_estado = $resultado_estados->fetch_assoc()) {
                                                ?>
                                                            <option value="<?php echo $row_estado["id"] ?>" <?=($row['estado_id']==$row_estado["id"])?'selected':'';?>> <?php echo $row_estado["nombre"]; ?> </option>
                                                <?php  
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        <?php
                                            } else {
                                                echo $row['nombre_estado'];
                                            }
                                        ?>
                                    </td>
                                    <?php
                                        if ($row["sucursal_destino_id"] == $sucursal_activa) {
                                    ?>
                                        <td>
                                            <button onclick="cambiarEstado('<?php echo $row['id']; ?>', '<?php echo $row['id']; ?>')" class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;" type="submit"
                                                <?php if ($row["id_estado"] == '4' || $row["id_estado"] == '5') {?>
                                                    disabled
                                                <?php } ?>
                                                >
                                                Cambiar estado
                                            </button>
                                        </td>
                                    <?php
                                        }
                                    ?>
                                </tr>
                        <?php
                                }
                            } else {
                        ?>
                            <div class="alert alert-info">
                                <p>No ha realizado ninguna transferencia</p>
                            </div>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
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
    function cambiarEstado(identificador) {
       if(confirm("¿Esta seguro que quiere realizar este cambio? Los cambios seran permanentes")) {
            var estado = $("#estado"+identificador).val();
            var comentario = $("#comentario"+identificador).val();

            $.post('transferencias_realizadas_sucursales_post.php', 
                    { id_transferencia: identificador, id_estado: estado, comentario: comentario }, 
                    function(data, status) {
                 if (status === 'success') {
                    var mensaje = btoa("Estado modificado con exito.-");
                    document.location.href = "?mensaje=" + mensaje;
                 }
            });
       }
    }
</script>
<?php require ("footer.php"); ?>