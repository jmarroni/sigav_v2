<?php
    if (!isset($_COOKIE["kiosco"])) {
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
    }

    require_once ("conection.php");

    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');

    $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.costo, p.precio_unidad, p.precio_mayorista, p.es_comodato, c.id AS categoria_id, pro.id AS proveedores_id
            FROM productos p 
            LEFT JOIN categorias c ON (p.categorias_id = c.id) 
            LEFT JOIN proveedor pro ON (p.proveedores_id = pro.id)";

    if (isset($_GET["identificador"])) $sql .= "WHERE p.id = ".intval($_GET["identificador"]);
    if (isset($_GET["codigo"])) $sql .= " WHERE p.codigo_barras = ".intval($_GET["codigo"]);

    $resultado = $conn->query($sql) or die($conn->error);
    $datos = '{"data":"no data"}';

    if ($resultado->num_rows > 0) {
        if ($row = $resultado->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo "{}";
        }
    } else { 
        echo "{}";
    }

    $conn->close();
    exit();
?>