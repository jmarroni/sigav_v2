<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}

require_once ("conection.php");
header('Content-Type: application/json');

if (isset($_GET["identificador"])) {
    $identificador = intval($_GET["identificador"]);
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $identificador);
} elseif (isset($_GET["term"])) {
    $term = "%" . $_GET["term"] . "%";
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE razon_social LIKE ? ORDER BY id DESC");
    $stmt->bind_param("s", $term);
} else {
    $stmt = $conn->prepare("SELECT * FROM clientes ORDER BY id DESC");
}

$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    if ($resultado->num_rows > 1) {
        $arrResponse = array();
        while ($row = $resultado->fetch_assoc()) {
            $row["value"] = $row["razon_social"] . " (" . $row["cuit"] . ")";
            $arrResponse[] = $row;
        }
        echo json_encode($arrResponse);
    } else {
        if ($row = $resultado->fetch_assoc()) {
            $row["value"] = $row["razon_social"];
            if (isset($_GET["term"])) {
                echo json_encode(array($row));
            } else {
                echo json_encode($row);
            }
        } else {
            echo "{}";
        }
    }
} else {
    echo "{}";
}

$stmt->close();
$conn->close();
exit();
?>
