<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");

if (isset($_POST["producto_id"]) && intval($_POST["producto_id"]) != "") {
    $producto_id = intval($_POST["producto_id"]);
    $sucursal_id = intval($_POST["sucursal"]);
    $stock = intval($_POST["stock"] ?? 0);
    $stock_minimo = intval($_POST["stock_minimo"] ?? 0);
    $usuario = $_COOKIE["kiosco"];

    // Verificar si existe stock para esta sucursal y producto
    $stmt = $conn->prepare("SELECT * FROM stock WHERE sucursal_id = ? AND productos_id = ?");
    $stmt->bind_param("ii", $sucursal_id, $producto_id);
    $stmt->execute();
    $resultado_stock = $stmt->get_result();

    if ($resultado_stock->num_rows > 0) {
        $row_stock = $resultado_stock->fetch_assoc();
        $stmt->close();

        // Actualizar stock existente
        $stmt_update = $conn->prepare("UPDATE stock SET stock_minimo = ?, stock = ? WHERE id = ?");
        $stmt_update->bind_param("iii", $stock_minimo, $stock, $row_stock["id"]);

        if ($stmt_update->execute()) {
            header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
        } else {
            echo json_encode(["error" => "Error al actualizar stock"]);
        }
        $stmt_update->close();
    } else {
        $stmt->close();

        // Insertar nuevo registro de stock
        $stmt_insert = $conn->prepare("INSERT INTO stock VALUES (NULL, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iiisi", $stock, $stock_minimo, $sucursal_id, $usuario, $producto_id);

        if ($stmt_insert->execute()) {
            header('Location: /stock_por_sucursales.php?mensaje='.base64_encode("Se actualizo el stock del producto ok"));
        } else {
            echo json_encode(["error" => "Error al insertar stock"]);
        }
        $stmt_insert->close();
    }
}

$conn->close();
exit();
?>
