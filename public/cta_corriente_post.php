<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");

if (isset($_POST["usuario"]) && isset($_POST["producto_id"])) {
    $usuario = $_POST["usuario"] ?? '';
    $producto_id = intval($_POST["producto_id"]);
    $fecha = date("Y-m-d H:i:s");
    $costo = floatval($_POST["costo"] ?? 0);
    $usuario_login = $_COOKIE["kiosco"];
    $sucursal_id = getSucursal($_COOKIE["sucursal"]);

    // Prepared statement para INSERT
    $stmt = $conn->prepare("INSERT INTO `cuenta_corriente` (`id`, `usuario`, `productos_id`, `fecha`, `costo`, `estado`, `usuario_login`) VALUES (NULL, ?, ?, ?, ?, '0', ?)");
    $stmt->bind_param("sisds", $usuario, $producto_id, $fecha, $costo, $usuario_login);

    if ($stmt->execute()) {
        $datos["ventas_id"] = $conn->insert_id;
        $stmt->close();

        // Prepared statement para UPDATE stock
        $stmt_update = $conn->prepare("UPDATE stock SET stock = (stock - 1) WHERE productos_id = ? AND sucursal_id = ?");
        $stmt_update->bind_param("ii", $producto_id, $sucursal_id);

        if ($stmt_update->execute()) {
            echo json_encode($datos);
        } else {
            echo json_encode(["error" => "Error al actualizar stock"]);
        }
        $stmt_update->close();

        header('Location: /cta_corriente.php?mensaje='.base64_encode("Obsequio ingresado correctamente"));
    } else {
        echo json_encode(["error" => "Error al insertar cuenta corriente"]);
        $stmt->close();
    }
}

$conn->close();
exit();
?>
