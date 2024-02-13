<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");

if (intval($_GET["producto"]) != ""){

    $sql_stock = "SELECT * FROM stock WHERE sucursal_id =".intval($_GET["sucursal"])." AND productos_id = ".$_GET["producto"];
    $resultado_stock = $conn->query($sql_stock);
    if ($resultado_stock->num_rows > 0) {
        if ($row_stock = $resultado_stock->fetch_assoc()) {
            $sql_update = "UPDATE stock SET stock_minimo =".intval($_GET["stock_minimo"]).", stock = ".$_GET["stock"]." WHERE id =".$row_stock["id"];
            if ($conn->query($sql_update) === TRUE) {
                echo "OK";
             // header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
            }else {
                echo "Error: " . $sql_update . "<br>" . $conn->error;
            }
        }
    }else{
      //  print_r($_POST);exit();
        $sql_insert = "INSERT INTO stock VALUES(NULL,'{$_GET["stock"]}','{$_GET["stock_minimo"]}','{$_GET["sucursal"]}','{$_COOKIE["kiosco"]}','{$_GET["producto"]}');";
        if ($conn->query($sql_insert) === TRUE) {
           echo "OK";
            //header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
        }else {
            echo "Error: " . $sql_insert . "<br>" . $conn->error;
        }
    }
}
$conn->close();
exit();



?>
