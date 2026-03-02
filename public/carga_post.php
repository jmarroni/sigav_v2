<?php

if (!isset($_COOKIE["kiosco"])) {
    $apiKey = getenv('API_KEY_INTERNAL') ?: '';
    if (!isset($_GET["apiKey"]) || $apiKey === '' || $_GET["apiKey"] !== $apiKey) {
        header('Location: /');
        exit();
    } else {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
    }
}

require_once ("conection.php");
$fecha = date("Y-m-d H:i:s");

if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $identificador = intval($_GET["identificador"]);
        $usuario = $_COOKIE["kiosco"] ?? 'api';

        // Prepared statement para DELETE
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $identificador);

        // Prepared statement para auditoria
        $stmt_audit = $conn->prepare("INSERT INTO stock_logs(stock_anterior, stock_minimo_anterior, stock, stock_minimo, sucursal_id, usuario, productos_id, tipo_operacion, updated_at, created_at) VALUES (0, 0, 0, 0, 0, ?, ?, 'Baja', ?, ?)");
        $stmt_audit->bind_param("siss", $usuario, $identificador, $fecha, $fecha);

        if ($stmt->execute() && $stmt_audit->execute()) {
            $producto = isset($_POST["producto"]) ? htmlspecialchars($_POST["producto"]) : '';
            header('Location: /carga?mensaje='.base64_encode("Se eliminó el producto {$producto} ok"));
        } else {
            echo json_encode(["error" => "Error al eliminar producto"]);
        }
        $stmt->close();
        $stmt_audit->close();
        exit();
    }
}
$conn->close();
exit();
?>
