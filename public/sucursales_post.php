<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 4) {
    exit();
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
        //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
        header('Location: /sucursales.php?mensaje='.base64_encode("Se elimino la sucursal correctamente"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}
if (intval($_POST["id"]) != ""){
    $fecha_alta = ($_POST["Fecha_alta"] != "")?$_POST["Fecha_alta"]:date("Y-m-d");
    $fecha_alta = ($_POST["Fecha_baja"] != "")?$_POST["Fecha_baja"]:date("Y-m-d");
    $sql = "UPDATE sucursales SET 
                nombre = '{$_POST["nombre"]}',
                fecha_alta = '{$fecha_alta}',
                fecha_baja = '{$fecha_alta}',
                provincia = '{$_POST["provincia"]}',
                codigo_postal = '{$_POST["codigo_postal"]}',
                pto_vta = '{$_POST["pto_vta"]}',
                direccion = '{$_POST["direccion"]}'";
    if ($imagen != ""){
        $sql .= ", imagen= '$imagen' ";
    }                       
    $sql .= " WHERE id = ".intval($_POST["id"]);

    if ($conn->query($sql) === TRUE) {
	  //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
      header('Location: /sucursales.php?mensaje='.base64_encode("Se actualizo la sucursal {$_POST["nombre"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    
    $sql = "INSERT INTO sucursales VALUES (NULL, 
                                    '{$_POST["nombre"]}', 
                                    '{$_POST["Fecha_alta"]}',
                                    '{$_COOKIE["kiosco"]}',
                                    '{$_POST["Fecha_baja"]}',
                                    '{$_POST["direccion"]}',
                                    '$imagen',
                                    '{$_POST["provincia"]}',
                                    '{$_POST["codigo_postal"]}',
                                    '{$_POST["pto_vta"]}')";

    if ($conn->query($sql) === TRUE) {
	   header('Location: /sucursales.php?mensaje='.base64_encode("Se ingreso la sucursal {$_POST["nombre"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

}


$conn->close();
exit();
?>
