<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 5) {
    exit();
}
$cliente = intval($_GET["cliente"]);
$servicio = intval($_GET["servicio"]);
if (isset($_POST)){
    $verificar_existencia = "select * FROM relacion_servicio_cliente WHERE cliente_id = $cliente and servicios_id = $servicio"; 
    $resultado = $conn->query($verificar_existencia) or die($conn->error);
    $datos = '{"data":"no data"}';
    if ($resultado->num_rows > 0) {
        $sql_relacion_del = "DELETE FROM relacion_servicio_cliente WHERE cliente_id = $cliente and servicios_id = $servicio";
        // HAY QUE VER TODO EL TEMA DE LA FACTURACION
        if ($conn->query($sql_relacion_del) === TRUE) {
            $mensaje = "relacion quitada con exito";
        }else{
            $mensaje = "error al querer quitar el rol";
        }
    }else{
        $sql = "INSERT INTO relacion_servicio_cliente VALUES(NULL,'".$cliente."','".$servicio."','".date("Y-m-d H:i:s")."');";
        if ($conn->query($sql) === TRUE) {
           $id = $conn->insert_id;
           $mensaje = "Nuevo rol ingresado exitosamente";
        }else{
            $mensaje = "error al insertar rol ".$sql;
        }
    }

        
}
$conn->close();
echo json_encode(array("mensaje" => $mensaje));
exit();
?>