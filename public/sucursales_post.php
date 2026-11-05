<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
require_once ("sucursal_validacion.php");
if (getRol() < 4) {
    exit();
}
$esEliminar = isset($_GET["action"]) && $_GET["action"] == "eliminar";
if (!$esEliminar) {
    $errorValidacion = sucursal_error_validacion($_POST);
    if ($errorValidacion !== null) {
        header('Location: /sucursales.php?mensaje='.base64_encode("No se guardó la sucursal. ".$errorValidacion));
        exit();
    }
}
// Me fijo si envio una imagen y la subo
if (isset($_FILES["imagen"]["name"]) && $_FILES["imagen"]["name"] != ""){
    $target_dir = dirname(__FILE__)."/assets/sucursales/";
    $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if (strtolower($imageFileType) != "png" && strtolower($imageFileType) != "jpg" && strtolower($imageFileType) != "jpeg"){
       // echo "no hay imagen 1";
       header('Location: /sucursales.php?mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));exit();
    }
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
     //   echo "no hay imagen 2";
        $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".strtolower($imageFileType);
        rename($target_file, $target_dir.$xmlFile);
        $imagen = "/assets/sucursales/".$xmlFile;
    }
}else{
    $imagen = "";
   // echo "no hay imagen";
}



if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $sql = "DELETE FROM sucursales WHERE id = ".intval($_GET["identificador"]);
        if ($conn->query($sql) === TRUE) {
        header('Location: /sucursales.php?mensaje='.base64_encode("Se elimino la sucursal correctamente"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}
$imagen = isset($imagen) ? $imagen : "";
// Alta / edición con consultas preparadas (antes se interpolaba $_POST en el SQL).
$nombre       = trim((string) $_POST["nombre"]);
$direccion    = (string) ($_POST["direccion"] ?? "");
$provincia    = (string) ($_POST["provincia"] ?? "");
$codigoPostal = sucursal_normalizar_cp($_POST["codigo_postal"]);
$fechaAlta    = (isset($_POST["Fecha_alta"]) && $_POST["Fecha_alta"] != "") ? $_POST["Fecha_alta"] : date("Y-m-d");
// Antes la edición guardaba la fecha de baja también como alta (variable pisada).
$fechaBaja    = (isset($_POST["Fecha_baja"]) && $_POST["Fecha_baja"] != "") ? $_POST["Fecha_baja"] : null;
$ptoVta       = intval($_POST["pto_vta"] ?? 0);
$id           = intval($_POST["id"] ?? 0);

if ($id > 0) {
    $sql = "UPDATE sucursales SET nombre = ?, fecha_alta = ?, fecha_baja = ?, provincia = ?, codigo_postal = ?, pto_vta = ?, direccion = ?";
    $tipos = "sssssis";
    $params = [$nombre, $fechaAlta, $fechaBaja, $provincia, $codigoPostal, $ptoVta, $direccion];
    if ($imagen != "") {
        $sql .= ", imagen = ?";
        $tipos .= "s";
        $params[] = $imagen;
    }
    $sql .= " WHERE id = ?";
    $tipos .= "i";
    $params[] = $id;
    $mensajeOk = "Se actualizo la sucursal {$nombre} ok";
} else {
    $sql = "INSERT INTO sucursales (nombre, fecha_alta, usuario, fecha_baja, direccion, imagen, provincia, codigo_postal, pto_vta)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $tipos = "ssssssssi";
    $params = [$nombre, $fechaAlta, (string) $_COOKIE["kiosco"], $fechaBaja, $direccion, $imagen, $provincia, $codigoPostal, $ptoVta];
    $mensajeOk = "Se ingreso la sucursal {$nombre} ok";
}

$stmt = $conn->prepare($sql);
if ($stmt && $stmt->bind_param($tipos, ...$params) && $stmt->execute()) {
    header('Location: /sucursales.php?mensaje='.base64_encode($mensajeOk));
} else {
    // El detalle va al log del servidor, no a la pantalla.
    error_log("sucursales_post: error al guardar la sucursal: ".($stmt ? $stmt->error : $conn->error));
    header('Location: /sucursales.php?mensaje='.base64_encode("No se pudo guardar la sucursal, intente de nuevo."));
}

$conn->close();
exit();
?>
