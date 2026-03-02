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
    $target_dir = dirname(__FILE__)."/assets/perfil/";
    $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (!in_array($imageFileType, ["png", "jpg", "jpeg"])){
        header('Location: /perfil.php?mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));
        exit();
    }
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
        $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".$imageFileType;
        rename($target_file, $target_dir.$xmlFile);
        $imagen = "/assets/perfil/".$xmlFile;
    }
}

// Actualizar perfil existente
if (isset($_POST["id"]) && intval($_POST["id"]) != ""){
    $id = intval($_POST["id"]);
    $nombre = $_POST["nombre"] ?? '';
    $razon_social = $_POST["razon_social"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $mail = $_POST["mail"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $provincia = $_POST["provincia"] ?? '';
    $localidad = $_POST["localidad"] ?? '';

    if ($imagen != ""){
        $stmt = $conn->prepare("UPDATE perfil SET nombre = ?, razon_social = ?, direccion = ?, mail = ?, telefono = ?, provincia = ?, localidad = ?, logo = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $nombre, $razon_social, $direccion, $mail, $telefono, $provincia, $localidad, $imagen, $id);
    } else {
        $stmt = $conn->prepare("UPDATE perfil SET nombre = ?, razon_social = ?, direccion = ?, mail = ?, telefono = ?, provincia = ?, localidad = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $nombre, $razon_social, $direccion, $mail, $telefono, $provincia, $localidad, $id);
    }

    if ($stmt->execute()) {
        header('Location: /perfil.php?mensaje='.base64_encode("Se actualizo el perfil correctamente"));
    } else {
        echo json_encode(["error" => "Error al actualizar perfil"]);
    }
    $stmt->close();
} else {
    // Insertar nuevo perfil
    $nombre = $_POST["nombre"] ?? '';
    $razon_social = $_POST["razon_social"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $mail = $_POST["mail"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $provincia = $_POST["provincia"] ?? '';
    $localidad = $_POST["localidad"] ?? '';

    if ($imagen == "") {
        $imagen = "/assets/img/photos/no-image-featured-image.png";
    }

    $stmt = $conn->prepare("INSERT INTO perfil VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $nombre, $razon_social, $direccion, $mail, $telefono, $provincia, $localidad, $imagen);

    if ($stmt->execute()) {
        header('Location: /perfil.php?mensaje='.base64_encode("Se ingreso el perfil correctamente"));
    } else {
        echo json_encode(["error" => "Error al insertar perfil"]);
    }
    $stmt->close();
}

$conn->close();
exit();
?>
