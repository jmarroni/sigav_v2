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
    $menu["cargas"] = "";
    $menu["reportes"] = "";
    $menu["actualizaciones"] = "active";
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
        <div class="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area api usuarios</h1>
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
            <form id="formulario" class="form-horizontal" action="/api/auth/signup" method="post">
                <div class="form-group">
                    <input class="hidden" value="" id="id" name="id">
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" value="" placeholder="Nombre"/>
                    </div>
                    <div class="col-xs-4">
                        <label>Email</label>
                        <input type="text" class="form-control" id="email" name="email" value="" placeholder="Email"/>
                    </div>
                    <div class="col-xs-4">
                        <label>Password</label>
                        <input type="text" class="form-control" id="password" name="password" value="" placeholder="Contraseña" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-6">
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
            <h3 class="block-title">Usuarios api</h3>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter">
                <?php
                $sql = "SELECT *
                FROM users
                ORDER BY name";
                $resultado = $conn->query($sql);
                if ($resultado->num_rows > 0) {
                    while($row = $resultado->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <h4><?php echo $row["name"] ?> (<a href="javascript:void();" onclick="eliminarUsuario('<?php echo $row["id"]; ?>')">Eliminar</a> <!-- , 
                                <a href="#" onclick="modificarUsuario('<?php echo $row["id"]; ?>')">Modificar</a> --> )
                        </td>
                        <td style="width: 100px;">
                            <p>Mail: <span class="text-gray-dark"><?php echo $row["email"]; ?></span></p>
                        </td>
                        <td class="text-center">
                            <span class="text-gray-dark" >Sucursales permitidas:<br>
                                <select style="width: 100px;" id="<?php echo $row["id"]?>sucursales_a_quitar" name="<?php echo $row["id"] ?>sucursales_a_quitar">
                                <?php 
                                    $query_sucursal = "SELECT s.id, s.nombre 
                                    FROM relacion_users_sucursales rus 
                                    JOIN sucursales s ON (rus.sucursal_id = s.id) 
                                    WHERE rus.user_id = ".$row["id"];
                                    
                                    $resultado_sucursal = $conn->query($query_sucursal);

                                    if ($resultado_sucursal->num_rows > 0) {
                                        while($row_sucursal = $resultado_sucursal->fetch_assoc()) {
                                ?>
                                            <option value="<?php echo $row_sucursal["id"] ?>"><?php echo $row_sucursal["nombre"]?></option>
                                <?php
                                        }
                                ?>
                                </select>
                                <?php
                                    } else {
                                ?>
                                    <option>No hay sucursales asignadas</option>
                                <?php 
                                    }
                                ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="eliminarSucursal(<?php echo $row["id"] ?>)" class="btn btn-sm btn-minw btn-rounded btn-danger" style="width: 70%;margin-top: 7%;">
                                Sacar sucursal
                            </button>
                        </td>
                        <td>
                            <small>Seleccione sucursal</small>
                            <br>
                            <select style="width: 100px;" id="<?php echo $row["id"]?>sucursales_a_asignar" name="<?php echo $row["id"] ?>sucursales_a_asignar">
                                <?php 
                                    $sql_sucursales = "
                                        SELECT id, nombre 
                                        FROM sucursales  
                                        ORDER BY nombre DESC";

                                    $resultado_sucursales = $conn->query($sql_sucursales);

                                    if ($resultado_sucursales->num_rows > 0) {
                                        while($row_sucursal = $resultado_sucursales->fetch_assoc()) {
                                ?>
                                        <option value="<?php echo $row_sucursal["id"]?>">
                                            <?php echo $row_sucursal["nombre"] ?>
                                        </option>
                                <?php 
                                        }
                                    }
                                ?>
                            </select>
                        </td>
                        <td>
                            <button onclick="agregarSucursal(<?php echo $row["id"] ?>)" class="btn btn-sm btn-minw btn-rounded btn-primary" style="width: 100%;margin-top: 7%;">
                                Agregar sucursal
                            </button>
                        </td>
                        <?php
                                } 
                            }
                            $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
    </div>
    <!-- END usuarios -->
</div>

<script src="https://code.jquery.com/jquery-1.12.4.js"></script>

<script>
    jQuery("document").ready(function() {
        setTimeout(function () {
            $("#add_success").hide('slow');
        }, 3000);
    });

    function agregarSucursal(identificador) {
        var sucursal_id = parseInt($("#" + identificador + "sucursales_a_asignar").val());
        
        $.post("usuarios_api_sucursal_crud.php", { metodo: "post", usuario_id: identificador, sucursal_id: sucursal_id }, function(data, status) {
            if (status === 'success') {
                document.location.href = 'usuarios_api.php?mensaje=' + btoa("Sucursal asignada al usuario con exito!");
            }
        });
    }

    function eliminarSucursal(identificador) {
        var sucursal_id = parseInt($("#" + identificador + "sucursales_a_quitar").val());
        
        $.post("usuarios_api_sucursal_crud.php", { metodo: "put", usuario_id: identificador, sucursal_id: sucursal_id }, function(data, status) {
            if (status === 'success') {
                document.location.href = 'usuarios_api.php?mensaje=' + btoa("Sucursal eliminada al usuario con exito!");
            }
        });
    }

    function eliminarUsuario(identificador){
        if (confirm('Seguro que desea eliminar el usuario?')) {
            $.get("usuarios_api_crud.php", { id_usuario: identificador, action: 'eliminar' }, function(data, status) {
                if (status === 'success') {
                    document.location.href = 'usuarios_api.php?mensaje=' + btoa("Usuario eliminado con exito!");
                }
            });
        }
    }

    /*function modificarUsuario(identificador) {
        $.get("get_usuario_api.php", { id_usuario: identificador }, function(data, status) {
            if (status === 'success') {
                data = JSON.parse(data);
                $("#id").val(data.id);
                $("#nombre").val(data.name);
                $("#email").val(data.email);
            }
        });
    }*/
</script>

<?php require ("footer.php"); ?>