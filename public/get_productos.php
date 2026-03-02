<?php
if (!isset($_COOKIE["kiosco"])) {
    $apiKey = getenv('API_KEY_INTERNAL') ?: '';
    if (!isset($_GET["apiKey"]) || $apiKey === '' || $_GET["apiKey"] !== $apiKey) {
        header('Location: /');
        exit();
    }
}

require_once ("conection.php");

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Base query
$baseQuery = "SELECT
            p.*,
            c.abreviatura,
            CONCAT(c.nombre,', ',c.abreviatura) as categoria,
            CONCAT(prov.nombre,', ',prov.apellido) as proveedor,
            descripcion
            FROM productos p
            LEFT JOIN proveedor prov ON prov.id = p.proveedores_id
            LEFT JOIN categorias c ON c.id = p.categorias_id";

if (isset($_GET["identificador"])) {
    $identificador = intval($_GET["identificador"]);
    $stmt = $conn->prepare($baseQuery . " WHERE p.id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $identificador);
} elseif (isset($_GET["codigo"])) {
    $codigo = intval($_GET["codigo"]);
    $stmt = $conn->prepare($baseQuery . " WHERE p.codigo_barras = ? ORDER BY id DESC");
    $stmt->bind_param("i", $codigo);
} else {
    $stmt = $conn->prepare($baseQuery . " ORDER BY id DESC LIMIT 1");
}

$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    if ($row = $resultado->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo "{}";
    }
} else {
    echo "{}";
}

$stmt->close();
$conn->close();
exit();
?>
