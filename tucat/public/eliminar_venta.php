<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
header('Content-Type: application/json');
require_once ("conection.php");

// Elimino rows que tengan como venta_id la venta a eliminar
$sql_eliminar = "DELETE FROM productos_en_carrito WHERE venta_id = ".intval($_POST["id"])." AND producto_id = ".intval($_POST["producto_id"]);

if ($conn->query($sql_eliminar) === TRUE) {
    echo "{'respuesta': 'OK'}";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
exit();
?>
?>