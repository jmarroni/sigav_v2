<?php
    if (!isset($_COOKIE["kiosco"])) {
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
    }

    require_once ("conection.php");

    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');

    $sql = "SELECT p.*, rcp.categoria_id FROM proveedor p LEFT JOIN relacion_categoria_proveedor rcp ON (p.id = rcp.proveedor_id) WHERE p.id = {$_REQUEST["id_artesano"]}";
    $resultado = $conn->query($sql) or die($conn->error." --- ".$sql);

    if ($resultado->num_rows > 0) {
        if ($row = $resultado->fetch_assoc()) {
            echo json_encode($row);
        }else { 
            echo "{}";
        }
    } else { 
        echo "{}";
    }

    $conn->close();
    exit();

?>