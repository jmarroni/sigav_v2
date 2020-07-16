<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
    exit();
}



if (isset($_GET["pedido"]) && isset($_GET["estado_nuevo"])){
    $pedido         = $_GET["pedido"];
    $estado_nuevo   = $_GET["estado_nuevo"];
    $pedido_sql = "UPDATE pedidos SET estado =".$estado_nuevo." WHERE nro_pedido =".$pedido;
    if ($conn->query($pedido_sql) === TRUE) {
        $datos["pedido_nro"] = $pedido;
        echo json_encode($datos);
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    exit();
}

$datos = '{"data":"no data"}';


if (!(isset($_POST["nro_pedido"])) || $_POST["nro_pedido"] == ""){
    $sql = "SELECT nro_pedido  FROM pedidos p ORDER BY nro_pedido DESC LIMIT 1";
    $pedido_nro = 1;
    $resultado = $conn->query($sql);

    if ($resultado->num_rows > 0) {
        // output data of each row
        while($row = $resultado->fetch_assoc()) {
            $pedido_nro = $row["nro_pedido"] + 1;
        }
    }
}else{
    $pedido_nro = $_POST["nro_pedido"];
}

$sql = "INSERT INTO pedidos VALUES (NULL,$pedido_nro,'{$_POST["nombre-producto"]}', 
                                    '{$_POST["precio"]}',
                                    '{$_POST["recepcion"]}',
                                    '{$_POST["entrega"]}',
                                     '{$_COOKIE["kiosco"]}',
                                     '".getSucursal($_COOKIE["sucursal"])."',
                                     '{$_POST["clientes_id_"]}',
                                     1,
                                     '{$_POST["comentario"]}',
                                     '".date("Y-m-d H:i:s")."')";
//echo $sql;exit();
if ($conn->query($sql) === TRUE) {
    $datos["pedido_nro"] = $pedido_nro;
    echo json_encode($datos);
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}



$conn->close();
exit();
?>
?>