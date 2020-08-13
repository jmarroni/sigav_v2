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
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $sql = "DELETE FROM productos WHERE id = ".intval($_GET["identificador"]);
        if ($conn->query($sql) === TRUE) {
        header('Location: /carga?mensaje='.base64_encode("Se elimino el producto {$_POST["producto"]} ok"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}
$conn->close();
exit();
?>
