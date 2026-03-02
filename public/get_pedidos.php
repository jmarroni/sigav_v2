<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}

require_once ("conection.php");
header('Content-Type: application/json');

$identificador = intval($_GET["identificador"] ?? 0);

$stmt = $conn->prepare("SELECT
            p.*,
            c.abreviatura,
            CONCAT(c.nombre,', ',c.abreviatura) as categoria,
            CONCAT(prov.nombre,', ',prov.apellido) as proveedor
            FROM productos p
            INNER JOIN proveedor prov ON prov.id = p.proveedores_id
            INNER JOIN categorias c ON c.id = p.categorias_id
            WHERE p.id = ?
            ORDER BY id DESC");
$stmt->bind_param("i", $identificador);
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
