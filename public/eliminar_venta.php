<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
header('Content-Type: application/json');
require_once ("conection.php");

$sql = "DELETE FROM ventas WHERE id =".intval($_POST["id"]);
if ($conn->query($sql) === TRUE) {
    $sql_update = "UPDATE productos SET stock = (stock + {$_POST["cantidad"]}) WHERE id = ".$_POST["producto_id"];
    if ($conn->query($sql_update) === TRUE) {
        echo "{'respuesta': 'OK'}";
    } else {
        echo "Error en UPDATE: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
exit();
?>
?>