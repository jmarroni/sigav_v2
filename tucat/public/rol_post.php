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
			$sql = "UPDATE roles SET nombre = '".$_POST["rol"]."', habilitado = '".intval($_POST["habilitado"])."' WHERE id = ".intval($_POST["id"]);
            if ($conn->query($sql) === TRUE) { 
                $id = intval($_POST["id"]);
                $mensaje = "Nuevo rol actualizado exitosamente"; 
            }else{
                echo $sql;exit();
            }
        }else{
            $sql = "INSERT INTO roles VALUES(NULL,'".$_POST["rol"]."','".date("Y-m-d H:i:s")."',1);";
            if ($conn->query($sql) === TRUE) {
               $id = $conn->insert_id;
               $mensaje = "Nuevo rol ingresado exitosamente";
            }else{
                $mensaje = "error al insertar rol";
            }
        }
        $sql_relacion_del = "DELETE FROM relacion_seccion_rol WHERE roles_id = ".intval($id);
        if ($conn->query($sql_relacion_del) === TRUE) {
            foreach($_POST["secciones"] as $key => $ids){
                $sql = "INSERT INTO relacion_seccion_rol VALUES(NULL,'$ids','$id');";
                $respuesta = $conn->query($sql) or die("Error, $sql");
            }
        }else{
            $mensaje = "error al insertar relacion";
        }
        
}
$conn->close();
header("Location: /rol.php?mensaje=".base64_encode($mensaje));
exit();
?>