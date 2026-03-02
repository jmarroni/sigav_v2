<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 5) {
    exit();
}

$mensaje = "";

if (isset($_POST["nombre"])) {
    $nombre = $_POST["nombre"] ?? '';
    $habilitado = intval($_POST["habilitado"] ?? 0);
    $periodo = intval($_POST["periodo"] ?? 0);
    $costo = floatval($_POST["costo"] ?? 0);

    if (isset($_POST["id"]) && $_POST["id"] != "") {
        // Actualizar servicio existente
        $id = intval($_POST["id"]);
        $stmt = $conn->prepare("UPDATE servicios SET nombre = ?, habilitado = ?, periodo = ?, costo = ? WHERE id = ?");
        $stmt->bind_param("siidi", $nombre, $habilitado, $periodo, $costo, $id);

        if ($stmt->execute()) {
            $mensaje = "Servicio actualizado exitosamente";
        } else {
            $mensaje = "Error al actualizar servicio";
        }
        $stmt->close();
    } else {
        // Insertar nuevo servicio
        $fecha = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO servicios VALUES (NULL, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidis", $nombre, $periodo, $costo, $habilitado, $fecha);

        if ($stmt->execute()) {
            $mensaje = "Nuevo servicio ingresado exitosamente";
        } else {
            $mensaje = "Error al insertar servicio";
        }
        $stmt->close();
    }
}

$conn->close();
header("Location: /configuracion_servicios.php?mensaje=" . base64_encode($mensaje));
exit();
?>
