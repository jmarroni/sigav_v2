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
    $target_dir = dirname(__FILE__)."/assets/perfil/";
    $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if (strtolower($imageFileType) != "png" && strtolower($imageFileType) != "jpg" && strtolower($imageFileType) != "jpeg"){
       // echo "no hay imagen 1";
       header('Location: /perfil.php?mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));exit();
    }
    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
     //   echo "no hay imagen 2";
        $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".strtolower($imageFileType);
        rename($target_file, $target_dir.$xmlFile);
        $imagen = "/assets/perfil/".$xmlFile;
    }
}else{
    $imagen = "";
   // echo "no hay imagen";
}
if (intval($_POST["id"]) != ""){
    $sql = "UPDATE perfil SET 
                nombre = '{$_POST["nombre"]}',
                razon_social = '{$_POST["razon_social"]}',
                direccion = '{$_POST["direccion"]}',
                mail = '{$_POST["mail"]}',
                telefono = '{$_POST["telefono"]}',
                provincia = '{$_POST["provincia"]}',
                localidad = '{$_POST["localidad"]}'";
    if ($imagen != ""){
        $sql .= ", logo= '$imagen' ";
    }                       
    $sql .= " WHERE id = ".intval($_POST["id"]);

    if ($conn->query($sql) === TRUE) {
	  //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
      header('Location: /perfil.php?mensaje='.base64_encode("Se actualizo el perfil {$_POST["nombre"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    if ($imagen == "") $imagen = "/assets/img/photos/no-image-featured-image.png";
    $sql = "INSERT INTO perfil VALUES (NULL,'".$_POST["nombre"]."', 
                                    '".$_POST["razon_social"]."',
                                    '".$_POST["direccion"]."',
                                     '".$_POST["mail"]."',
                                     '".$_POST["telefono"]."',
                                     '".$_POST["provincia"]."',
                                     '".$_POST["localidad"]."','".$imagen."')";

    if ($conn->query($sql) === TRUE) {
	   header('Location: /perfil.php?mensaje='.base64_encode("Se ingreso el perfil {$_POST["nombre"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

}


$conn->close();
exit();
?>
