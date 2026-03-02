<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
    exit();
}

// Actualizar estado de pedido
if (isset($_GET["pedido"]) && isset($_GET["estado_nuevo"])){
    $pedido = intval($_GET["pedido"]);
    $estado_nuevo = intval($_GET["estado_nuevo"]);

    $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE nro_pedido = ?");
    $stmt->bind_param("ii", $estado_nuevo, $pedido);

    if ($stmt->execute()) {
        $datos["pedido_nro"] = $pedido;
        echo json_encode($datos);
    } else {
        echo json_encode(["error" => "Error al actualizar pedido"]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

$datos = '{"data":"no data"}';

// Obtener o generar número de pedido
if (!(isset($_POST["nro_pedido"])) || $_POST["nro_pedido"] == ""){
    $sql = "SELECT nro_pedido FROM pedidos p ORDER BY nro_pedido DESC LIMIT 1";
    $pedido_nro = 1;
    $resultado = $conn->query($sql);

    if ($resultado->num_rows > 0) {
        while($row = $resultado->fetch_assoc()) {
            $pedido_nro = $row["nro_pedido"] + 1;
        }
    }
} else {
    $pedido_nro = intval($_POST["nro_pedido"]);
}

// Insertar pedido con prepared statement
$stmt = $conn->prepare("INSERT INTO pedidos VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");

$nombre_producto = $_POST["nombre-producto"] ?? '';
$precio = floatval($_POST["precio"] ?? 0);
$recepcion = $_POST["recepcion"] ?? '';
$entrega = $_POST["entrega"] ?? '';
$usuario = $_COOKIE["kiosco"];
$sucursal = getSucursal($_COOKIE["sucursal"]);
$clientes_id = intval($_POST["clientes_id_"] ?? 0);
$comentario = $_POST["comentario"] ?? '';
$fecha = date("Y-m-d H:i:s");

$stmt->bind_param("isdsssisis", $pedido_nro, $nombre_producto, $precio, $recepcion, $entrega, $usuario, $sucursal, $clientes_id, $comentario, $fecha);

if ($stmt->execute()) {
    $datos["pedido_nro"] = $pedido_nro;
    echo json_encode($datos);
} else {
    echo json_encode(["error" => "Error al insertar pedido"]);
}

$stmt->close();
$conn->close();
exit();
?>
