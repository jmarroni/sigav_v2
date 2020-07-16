<?php
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
    exit();
}
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    if ($_GET["action"] == "eliminar"){
        $sql = "DELETE FROM usuarios WHERE id = ".intval($_GET["identificador"]);
        if ($conn->query($sql) === TRUE) {
        //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
        header('Location: /usuarios.php?mensaje='.base64_encode("Se elimino el usuario correctamente"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        exit();
    }
}
if ($_POST["id"] == ''){
    $sql = "INSERT INTO usuarios VALUES (NULL, 
                                        '{$_POST["usuario"]}', 
                                        '".sha1($_POST["clave"].SEMILLA)."', 
                                        '{$_POST["rol"]}',
                                        '{$_POST["nombre"]}',
                                        '{$_POST["apellido"]}',
                                        '{$_POST["telefono"]}',
                                        '{$_POST["sucursales"]}')";

    if ($conn->query($sql) === TRUE) {
        header('Location: /usuarios.php?mensaje='.base64_encode("Usuario ingresado correctamente"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    $sql = "UPDATE usuarios SET usuario = '{$_POST["usuario"]}', 
                            rol_id = '{$_POST["rol"]}',
                            nombre = '{$_POST["nombre"]}',
                            apellido = '{$_POST["apellido"]}',
                            telefono = '{$_POST["telefono"]}',
                            sucursal_id = '{$_POST["sucursales"]}'
            WHERE id = '{$_POST["id"]}'";

if ($conn->query($sql) === TRUE) {
    if ($_POST["clave"] != ""){
        $sql = "UPDATE usuarios SET clave = '".sha1($_POST["clave"].SEMILLA)."' WHERE id = {$_POST["id"]};";
        $conn->query($sql);
        header('Location: /usuarios.php?mensaje='.base64_encode("Usuario actualizado correctamente"));
    }else{
        header('Location: /usuarios.php?mensaje='.base64_encode("Usuario actualizado correctamente"));
    }
} else {
echo "Error: " . $sql . "<br>" . $conn->error;
}
}


$conn->close();
exit();
?>
?>