<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
$sql = "SELECT 
            *
            FROM sucursales 
            WHERE id = ".intval($_GET["identificador"])."
            order by id DESC ";
$resultado = $conn->query($sql) or die($conn->error);
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
// output data of each row
if ($row = $resultado->fetch_assoc()) {
    echo json_encode($row);
}else{ echo "{}"; }
}else{ echo "{}"; }
$conn->close();

exit();
?>