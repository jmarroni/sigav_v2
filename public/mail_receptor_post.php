<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 5) {
    exit();
}
if (isset($_POST)){
    $sql = "UPDATE `mail_configuracion` SET imap = '".$_POST["imap"]."', usuario = '".$_POST["usuario"]."', clave = '".$_POST["clave"]."', subject = '".$_POST["asunto"]."'";
    if ($conn->query($sql) === TRUE) { 
        $id = intval($_POST["id"]);
        $mensaje = "Mail actualizado exitosamente"; 
    }else{
        echo $sql;exit();
    }  
}
$conn->close();
header("Location: /configuracion_mail.php?mensaje=".base64_encode($mensaje));
exit();
?>