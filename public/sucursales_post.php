<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 4) {
    exit();
}

// Me fijo si envio una imagen y la subo
$imagen = "";
if (isset($_FILES["imagen"]["name"]) && $_FILES["imagen"]["name"] != ""){
    $target_dir = dirname(__FILE__)."/assets/sucursales/";
    $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (!in_array($imageFileType, ["png", "jpg", "jpeg"])){
        header('Location: /sucursales.php?mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));
        exit();
    }
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
        $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".$imageFileType;
        rename($target_file, $target_dir.$xmlFile);
        $imagen = "/assets/sucursales/".$xmlFile;
    }
}

// Eliminar sucursal
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $identificador = intval($_GET["identificador"]);
        $stmt = $conn->prepare("DELETE FROM sucursales WHERE id = ?");
        $stmt->bind_param("i", $identificador);

        if ($stmt->execute()) {
            header('Location: /sucursales.php?mensaje='.base64_encode("Se elimino la sucursal correctamente"));
        } else {
            echo json_encode(["error" => "Error al eliminar sucursal"]);
        }
        $stmt->close();
        exit();
    }
}

// Actualizar sucursal existente
if (isset($_POST["id"]) && intval($_POST["id"]) != ""){
    $id = intval($_POST["id"]);
    $nombre = $_POST["nombre"] ?? '';
    $fecha_alta = ($_POST["Fecha_alta"] != "") ? $_POST["Fecha_alta"] : date("Y-m-d");
    $fecha_baja = ($_POST["Fecha_baja"] != "") ? $_POST["Fecha_baja"] : date("Y-m-d");
    $provincia = $_POST["provincia"] ?? '';
    $codigo_postal = $_POST["codigo_postal"] ?? '';
    $pto_vta = intval($_POST["pto_vta"] ?? 0);
    $direccion = $_POST["direccion"] ?? '';

    if ($imagen != ""){
        $stmt = $conn->prepare("UPDATE sucursales SET nombre = ?, fecha_alta = ?, fecha_baja = ?, provincia = ?, codigo_postal = ?, pto_vta = ?, direccion = ?, imagen = ? WHERE id = ?");
        $stmt->bind_param("sssssisii", $nombre, $fecha_alta, $fecha_baja, $provincia, $codigo_postal, $pto_vta, $direccion, $imagen, $id);
    } else {
        $stmt = $conn->prepare("UPDATE sucursales SET nombre = ?, fecha_alta = ?, fecha_baja = ?, provincia = ?, codigo_postal = ?, pto_vta = ?, direccion = ? WHERE id = ?");
        $stmt->bind_param("ssssssii", $nombre, $fecha_alta, $fecha_baja, $provincia, $codigo_postal, $pto_vta, $direccion, $id);
    }

    if ($stmt->execute()) {
        header('Location: /sucursales.php?mensaje='.base64_encode("Se actualizo la sucursal correctamente"));
    } else {
        echo json_encode(["error" => "Error al actualizar sucursal"]);
    }
    $stmt->close();
} else {
    // Insertar nueva sucursal
    $nombre = $_POST["nombre"] ?? '';
    $fecha_alta = $_POST["Fecha_alta"] ?? date("Y-m-d");
    $fecha_baja = $_POST["Fecha_baja"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $provincia = $_POST["provincia"] ?? '';
    $codigo_postal = $_POST["codigo_postal"] ?? '';
    $pto_vta = intval($_POST["pto_vta"] ?? 0);
    $usuario = $_COOKIE["kiosco"];

    $stmt = $conn->prepare("INSERT INTO sucursales VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssi", $nombre, $fecha_alta, $usuario, $fecha_baja, $direccion, $imagen, $provincia, $codigo_postal, $pto_vta);

    if ($stmt->execute()) {
        header('Location: /sucursales.php?mensaje='.base64_encode("Se ingreso la sucursal correctamente"));
    } else {
        echo json_encode(["error" => "Error al insertar sucursal"]);
    }
    $stmt->close();
}

$conn->close();
exit();
?>
