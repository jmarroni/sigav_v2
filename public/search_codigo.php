<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
header('Content-Type: application/json');

$term = $_GET["term"] ?? '';

$stmt = $conn->prepare("SELECT * FROM productos WHERE codigo_barras LIKE ?");
$searchTerm = $term . "%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$resultado = $stmt->get_result();

$datos = array();
if ($resultado->num_rows > 0) {
    $i = 0;
    while($row = $resultado->fetch_assoc()) {
        $datos[$i]["value"] = $row["nombre"];
        $datos[$i]["label"] = $row["nombre"];
        $datos[$i]["id"] = $row["id"];
        $datos[$i]["precio"] = $row["precio_unidad"];
        $i++;
    }
    echo json_encode($datos);
} else {
    echo "[]";
}

$stmt->close();
$conn->close();
exit();
?>
