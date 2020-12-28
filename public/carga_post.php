<?php

if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
    else{
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
    }
}

require_once ("conection.php");
$fecha= date("Y-m-d H:i:s");
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $sql = "DELETE FROM productos WHERE id = ".intval($_GET["identificador"]);
        $sqlauditoria="INSERT INTO stock_logs(stock_anterior, stock_minimo_anterior, stock, stock_minimo,sucursal_id, usuario, productos_id,tipo_operacion, updated_at, created_at) VALUES (0,0,0,0,0,'".$_COOKIE["kiosco"]."',".$_GET["identificador"].",'Baja','".$fecha."','".$fecha."')";
        if ($conn->query($sql) === TRUE && $conn->query($sqlauditoria) === TRUE) {
        header('Location: /carga?mensaje='.base64_encode("Se eliminó el producto {$_POST["producto"]} ok"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}
$conn->close();
exit();
?>
