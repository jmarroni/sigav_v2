<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once ("../conection.php");

// Verificar autenticación
if (!isset($_COOKIE["kiosco"])) {
    http_response_code(401);
    echo json_encode(["result" => "0", "error" => "No autorizado"]);
    exit();
}

$data_array = ["result" => "0"];
$target_path = time() . '.jpg';

if (isset($_POST["file"])){

    // Validar que el identificador sea un número entero
    $id = intval($_POST["identificador"]);
    if ($id <= 0) {
        echo json_encode(["result" => "0", "error" => "ID de producto inválido"]);
        exit();
    }

    $imagedata = $_POST['file'];

    // Validar que sea una imagen base64 válida
    if (!preg_match('#^data:image/(jpeg|png|gif|webp);base64,#i', $imagedata)) {
        echo json_encode(["result" => "0", "error" => "Formato de imagen inválido"]);
        exit();
    }

    // Decodificar la imagen
    $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imagedata));

    if ($data === false) {
        echo json_encode(["result" => "0", "error" => "Error al decodificar imagen"]);
        exit();
    }

    // Nombre de archivo seguro (solo números y extensión)
    $imageName = $id . "_" . $target_path;

    // Guardar la imagen
    if (file_put_contents($imageName, $data) === false) {
        echo json_encode(["result" => "0", "error" => "Error al guardar imagen"]);
        exit();
    }

    // Eliminar imagen anterior usando prepared statement
    $stmt = $conn->prepare("DELETE FROM `imagen_producto` WHERE productos_id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        $data_array['result'] = "Error";
        echo json_encode(["result" => "0", "error" => "Error al eliminar imagen anterior"]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    $data_array['result'] = $id;
    $data_array['image_url'] = 'http://todo-kiosco.sigav.com.ar/upload_articles/' . $imageName;

    // Insertar nueva imagen usando prepared statement
    $stmt = $conn->prepare("INSERT INTO `imagen_producto` VALUES (NULL, ?, ?)");
    $stmt->bind_param("si", $data_array['image_url'], $id);

    if ($stmt->execute()) {
        $data_array['result'] = $id;
    } else {
        $data_array['result'] = "Error";
        echo json_encode(["result" => "0", "error" => "Error al insertar imagen"]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
    $conn->close();
}

echo json_encode($data_array);
?>
