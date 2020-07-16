<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");

$id_producto = $_REQUEST['id_producto'];

$sql = "SELECT proveedor.sitio_web
        FROM productos JOIN proveedor ON (productos.proveedores_id = proveedor.id)";
if (isset($id_producto)) $sql .= " WHERE productos.id = ".intval($id_producto);

$resultado = $conn->query($sql) or die($conn->error." --- ".$sql);
$datos = '{"data":"no data"}';

if ($resultado->num_rows > 0) {
    if ($row = $resultado->fetch_assoc()) {
        echo json_encode($row);
    }else{ 
        echo "{}"; 
    }
}else{ 
    echo "{}"; 
}
$conn->close();

exit();
?>