<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
$sql = "UPDATE productos SET codigo_barras = '{$_POST["codigo"]}' WHERE id = ".$_POST["id"];

if ($conn->query($sql) === TRUE) {
    echo "OK";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
$conn->close();
exit();
?>