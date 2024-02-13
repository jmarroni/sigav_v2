<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
$cliente = intval($_GET["cliente"]);
$sql = "SELECT  
                            ss.*,rsc.id as activado
                            FROM servicios ss 
                                LEFT JOIN relacion_servicio_cliente rsc ON
                                ss.id = rsc.servicios_id and rsc.cliente_id = $cliente                                   
                            ORDER BY nombre DESC ";
                    $resultado = $conn->query($sql) or die($conn->error);
$resultado = $conn->query($sql) or die($conn->error);
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
// output data of each row
$arrDevolucion = array();
    while ($row = $resultado->fetch_assoc()) {
        $arrDevolucion[]  =$row;
    }
    echo json_encode($arrDevolucion);
}else{ echo "{}"; }
$conn->close();

exit();
?>