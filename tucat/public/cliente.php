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
$menu["cliente"] = "active";
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
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area de Clientes</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Modificaci&oacute;n/Baja de clientes</h2>
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
        <div class="block-title" >
            Alta de Clientes
        </div>
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/cliente_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Raz&oacute;n Social (*):</label>
                        <input type="text" class="form-control" name="razon_social" id="razon_social" value="" placeholder="Empresa S.A." />
                    </div>
                    <div class="col-xs-4">
                        <label>Domicilio Legal (*):</label>
                        <input type="text" class="form-control" name="domicilio_legal" id="domicilio_legal" value="" placeholder="Calle altura, piso ..." />
                    </div>
                    <div class="col-xs-4">
                        <label>C&oacute;digo Postal (*):</label>
                        <input type="text" class="form-control" name="codigo_postal" id="codigo_postal" value="" placeholder="8500" />
                    </div>
                    <div class="col-xs-4">
                        <label>Tel&eacute;fono (*):</label>
                        <input type="phone" class="form-control" name="telefono" id="telefono" value="" placeholder="+54 9 2920 534323" />
                    </div>
                    <div class="col-xs-4">
                        <label>Provincia (*):</label>
                        <input type="text" class="form-control" name="provincia" id="provincia" value="" placeholder="Rio Negro" />
                    </div>
                    <div class="col-xs-4">
                        <label>Localidad (*):</label>
                        <input type="text" class="form-control" name="localidad" id="localidad" value="" placeholder="Viedma" />
                    </div>
                    <div class="col-xs-4">
                        <label>CUIT (*):</label>
                        <input type="text" class="form-control" name="cuit" id="cuit" value="" placeholder="23282568519" />
                    </div>
                    <div class="col-xs-4">
                        <label>Condicion ante el IVA (*):</label>
                        <select class="form-control" name="condicion_iva" id="condicion_iva" >
                            <option value="0">Seleccione una opci&oacute;n</option>
                            <option value="1">Resp. Inscripto</option>
                            <option value="2">Monotributista</option>
                            <option value="3">Excento</option>
                            <option value="4">Cons. Final</option>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label>Representante Legal (*):</label>
                        <input type="text" class="form-control" name="representante" id="representante" value="" placeholder="Juan Garay" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email (*):</label>
                        <input type="mail" class="form-control" name="email_representante" id="email_representante" value="" placeholder="mail@mail.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Responsable de contrataci&oacute;n:</label>
                        <input type="text" class="form-control" name="responsable_contratacion" id="responsable_contratacion" value="" placeholder="Juan Perez" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email:</label>
                        <input type="mail" class="form-control" name="email_constratacion" id="email_constratacion" value="" placeholder="mail@responsable.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Responsable de Pagos:</label>
                        <input type="text" class="form-control" name="responsable_pagos" id="responsable_pagos" value="" placeholder="Juan Gonzalez" />
                    </div>
                    <div class="col-xs-4">
                        <label>Email:</label>
                        <input type="text" class="form-control" name="email_pagos" id="email_pagos" value="" placeholder="email@pagos.com" />
                    </div>
                    <div class="col-xs-4">
                        <label>Horario de consulta pago a proveedores:</label>
                        <input type="text" class="form-control" name="consulta_proveedores" id="consulta_proveedores" value="" placeholder="Martes - Viernes 8:30 a 12:00hs." />
                    </div>
                    <div class="col-xs-4">
                        <label>Horario de entregas y retiros:</label>
                        <input type="text" class="form-control" name="entrega_retiros" id="entrega_retiros" value="" placeholder="Lunes, Miercoles y Viernes de 9 a 12hs." />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-8 col-xs-offset-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit" id="boton">
                            <i class="fa fa-check push-5-r"></i>Generar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Products -->
    <div class="block block-rounded">
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                    <tbody id="tablaProductos"><?php
                    $sql = "SELECT 
                                *
                                FROM clientes c 
                                order by id DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = '{"data":"no data"}';
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) {
                            ?>
                            <tr id="articulo_<?php echo strtolower($row["nombre"]); ?>">
                                <td class="text-center">
                                    <div style="width: 180px;">
                                        <img class="img-responsive" src="assets/img/photos/no-image-featured-image.png"
                                             alt="">
                                    </div>
                                </td>
                                <td>
                                    <h4><?php echo $row["razon_social"]; ?> </h4>
                                    <p class="remove-margin-b">Domicilio: <b><?php echo $row["domicilio_legal"].", ".$row["localidad"].", ".$row["provincia"].", ".$row["codigo_postal"]; ?></b></p>
                                    <p class="remove-margin-b">Condicion ante el IVA: <b><?php 
                                    switch ($row["condicion_iva"]) {
                                        case '1': echo "Resp. Inscripto"; break;
                                        case '2': echo "Monotributista"; break;
                                        case '3': echo "Excento"; break;
                                        case '4': echo "Cons. Final"; break;
                                    } ?></b></p>
                                    <button onclick="modificar('<?php echo $row["id"]; ?>');" class="btn btn-sm btn-minw btn-rounded btn-primary" style="margin-top:11px;" type="button">
                                        <i class="fa fa-check push-5-r"></i>Modificar
                                    </button>
                                    <button onclick="eliminar('<?php echo $row["id"]; ?>',<?php echo ($row["deshabilitado"] != '0')?"1":"0"; ?>);" class="btn btn-sm btn-minw btn-rounded <?php echo ($row["deshabilitado"] != '0')?"btn-primary":"btn-danger"; ?>" style="margin-top:11px;" type="button">
                                        <i class="fa fa-times push-5-r"></i><?php echo ($row["deshabilitado"] != '0')?"Habilitar":"Deshabilitar"; ?>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <p class="remove-margin-b">Telefono: <span class="text-gray-dark"> <?php echo $row["telefono"]; ?></span></p>
                                    <p class="remove-margin-b">Representante Legal:<br> <span class="text-gray-dark"><?php echo $row["representante"]; ?> - <?php echo $row["email_representante"]; ?></span></p>                                </td>
                            </tr>

                            <?php
                        }
                    } else {
                        echo "<tr><td>No hay Clientes</td></tr>";
                    }
                    ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- END Products -->
</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="/assets/js/pages/clientes.js?v=1.01"></script>
<?php require ("footer.php"); ?>