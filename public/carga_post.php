<?php

if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
    else{
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
    }
}

require_once ("conection.php");
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $sql = "DELETE FROM productos WHERE id = ".intval($_GET["identificador"]);
        if ($conn->query($sql) === TRUE) {
        //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
        header('Location: /carga.php?mensaje='.base64_encode("Se actualizo el producto {$_POST["producto"]} ok"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}

// Almaceno las imagenes en un arreglo
$imagenes = array(
    "1" =>$_FILES["imagen3"],
    "2" => $_FILES["imagen2"],
    "3" => $_FILES["imagen1"],
    "4" =>$_FILES["imagen4"],
    "5" => $_FILES["imagen5"],
    "6" => $_FILES["imagen6"]
);

// Llamo funcion imagen con el arreglo de imagenes y guardo las ulrs
$urlImagenes = imagen($imagenes);

function imagen($imagenes) {
    $urlImagenes = array();

    foreach ($imagenes as $imagen) {
        // Me fijo si envio una imagen y la subo
        if (isset($imagen["name"]) && $imagen["name"] != ""){
            $target_dir = dirname(__FILE__)."/upload_articles/";
            $target_file = $target_dir . basename($imagen["name"]);
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            if (strtolower($imageFileType) != "png" && strtolower($imageFileType) != "jpg" && strtolower($imageFileType) != "jpeg"){
            // echo "no hay imagen 1";
            header('Location: /carga.php?mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));exit();
            }
            if (move_uploaded_file($imagen["tmp_name"], $target_file)) {
                // echo "no hay imagen 2";
                $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".strtolower($imageFileType);

                // Verifico como redimensiono parejo
                $sizeI = getimagesize($target_file);

                //$ancho = 180;
                //$largo = round($ancho * $sizeI[1] / $sizeI[0],0);
               $ancho = $sizeI[0];
                $largo = $sizeI[1];
                sleep(2); redim ($target_file,$target_dir.$xmlFile,$ancho,$largo); 
                // rename($target_file, $target_dir.$xmlFile);
                array_unshift($urlImagenes, "/upload_articles/".$xmlFile);
            }
        }else{
            array_push($urlImagenes, "");
            // echo "no hay imagen";
        }
    }

    return $urlImagenes;
}

if (($_POST["id"] !== NULL)  && intval($_POST["id"]) != ""){
    $categoria = (isset($_POST["categoria"]))?explode("_", $_POST["categoria"])[0]:1;
    $sql = "UPDATE productos SET 
                                     precio_unidad = '{$_POST["precio_unidad"]}',
                                     costo = '{$_POST["costo"]}',
                                     proveedores_id = '{$_POST["proveedor"]}',
                                     categorias_id = '$categoria',
                                     stock = '{$_POST["stock"]}',
                                     nombre = '{$_POST["producto"]}',
                                     stock_minimo = '{$_POST["stock_minimo"]}',
                                     codigo_barras =  '{$_POST["codigo_de_barras"]}',
                                     descripcion =  '{$_POST["descripcion"]}',
                                     precio_mayorista = '{$_POST["precio_mayorista"]}' WHERE id = ".intval($_POST["id"]);
    $id = $_POST["id"];
    if ($conn->query($sql) === TRUE) {
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5"){
            set_image($id,$urlImagenes);
            header('Location: /carga.php?mensaje='.base64_encode("Se actualizo el producto {$_POST["producto"]} ok"));
        }else{
            echo '{"proceso":"OK"}'; // Viene desde movil
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    $codigo_barras = "";
    $arrCategoria = explode("_",$_POST["categoria"]);
    $identificador = "0000000001";
    $sql = "SELECT id FROM productos p ORDER BY id DESC LIMIT 1";
    $resultado = $conn->query($sql);
    if ($resultado->num_rows > 0) {
        if ($row = $resultado->fetch_assoc()) {
            $id = $row["id"] + 1;
            $identificador = substr("000000000".$id,-10);
        }
    }
    if ($_POST["codigo_de_barras"] != ""){ $codigo_barras =  $_POST["codigo_de_barras"];}
    else $codigo_barras = $arrCategoria[1].substr("000000".$_POST["proveedor"],-5).$identificador;
    if (isset($_COOKIE["kiosco"])) $usuario = $_COOKIE["kiosco"]; else $usuario = "sistema";
    $sql = "INSERT INTO productos VALUES (NULL, '$codigo_barras', 
                                    '{$_POST["producto"]}', 
                                    '{$_POST["precio_unidad"]}',
                                     '{$_POST["costo"]}',
                                     '{$_POST["stock"]}',
                                     '{$_POST["stock_minimo"]}',
                                     '{$_POST["proveedor"]}',
                                     '{$_POST["categoria"]}',
                                     '$usuario',
                                     '".date("Y-m-d H:i:s")."',
                                     '{$_POST["precio_mayorista"]}',
                                     '{$_POST["descripcion"]}')";
    if ($conn->query($sql) === TRUE) {
        $id = $conn->insert_id;
        
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5"){
            echo $id;
            set_image($id,$urlImagenes,true);
            header('Location: /carga.php?mensaje='.base64_encode("Se ingreso el producto {$_POST["producto"]} ok"));
        } else {
            echo '{"proceso":"OK"}'; // Viene desde movil
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

function set_image($id,$urlImagenes,$es_nuevo = false){
    global $conn;
        if ($es_nuevo){
            foreach ($urlImagenes as $urlImagen) {
                if ($urlImagen == "") $urlImagen = "assets/img/photos/no-image-featured-image.png";
                $sql = "INSERT INTO imagen_producto VALUES (NULL,'$urlImagen','$id');";

                if ($conn->query($sql) === TRUE){}
                else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
        }else{
            if($urlImagenes[0] || $urlImagenes[1] || $urlImagenes[2]) {
                $sql = "DELETE FROM imagen_producto WHERE productos_id = ".intval($id);
                if ($conn->query($sql) === TRUE) {
                    foreach ($urlImagenes as $urlImagen) {
                        if ($urlImagen == "") $urlImagen = "assets/img/photos/no-image-featured-image.png";
                        $sql = "INSERT INTO imagen_producto VALUES (NULL,'$urlImagen','$id');";
                        if ($conn->query($sql) === TRUE){}
                        else {
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }
                    } 
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
        }           
}

$conn->close();
exit();
?>
