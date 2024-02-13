<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");

if (intval($_POST["producto_id"]) != ""){

    $sql_stock = "SELECT * FROM stock WHERE sucursal_id =".intval($_POST["sucursal"])." AND productos_id = ".$_POST["producto_id"];
    $resultado_stock = $conn->query($sql_stock);
    if ($resultado_stock->num_rows > 0) {
        if ($row_stock = $resultado_stock->fetch_assoc()) {
            $sql_update = "UPDATE stock SET stock_minimo =".intval($_POST["stock_minimo"]).", stock = ".$_POST["stock"]." WHERE id =".$row_stock["id"];
            if ($conn->query($sql_update) === TRUE) {
              header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
            }else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }else{
      //  print_r($_POST);exit();
        $sql_insert = "INSERT INTO stock VALUES(NULL,'{$_POST["stock"]}','{$_POST["stock_minimo"]}','{$_POST["sucursal"]}','{$_COOKIE["kiosco"]}','{$_POST["producto_id"]}');";
        if ($conn->query($sql_insert) === TRUE) {
           header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
        }else {
            echo "Error: " . $sql_insert . "<br>" . $conn->error;
        }
    }
}
$conn->close();
exit();



?>
