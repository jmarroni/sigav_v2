<?php
if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
    header('Location: /');
}

require_once ("conection.php");

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$sql = "SELECT 
            p.*,
            c.abreviatura,
            CONCAT(c.nombre,', ',c.abreviatura) as categoria,
            CONCAT(prov.nombre,', ',prov.apellido) as proveedor 
            FROM productos p 
            LEFT JOIN proveedor prov
            ON prov.id = p.proveedores_id
            LEFT JOIN categorias c
            ON c.id = p.categorias_id ";

if (isset($_GET["identificador"])) $sql .= "WHERE p.id = ".intval($_GET["identificador"]);
if (isset($_GET["codigo"])) $sql .= "WHERE p.codigo_barras = ".intval($_GET["codigo"]);

           $sql .=" order by id DESC ";
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