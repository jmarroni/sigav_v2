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
		if ($_POST["id"] != ""){
			$sql = "UPDATE servicios SET nombre = '".$_POST["nombre"]."', habilitado = '".intval($_POST["habilitado"])."', periodo = '".intval($_POST["periodo"])."', costo = '".$_POST["costo"]."' WHERE id = ".intval($_POST["id"]);
            if ($conn->query($sql) === TRUE) { 
                $id = intval($_POST["id"]);
                $mensaje = "Nuevo rol actualizado exitosamente"; 
            }else{
                echo $sql;exit();
            }
        }else{
            $sql = "INSERT INTO servicios VALUES(NULL,'".$_POST["nombre"]."','".$_POST["periodo"]."','".$_POST["costo"]."','".$_POST["habilitado"]."','".date("Y-m-d H:i:s")."');";
            if ($conn->query($sql) === TRUE) {
               $id = $conn->insert_id;
               $mensaje = "Nuevo servicio ingresado exitosamente";
            }else{
                $mensaje = "error al insertar servicio";
            }
        }        
}
$conn->close();
header("Location: /configuracion_servicios.php?mensaje=".base64_encode($mensaje));
exit();
?>