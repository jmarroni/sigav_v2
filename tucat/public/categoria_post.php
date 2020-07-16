<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
//Verificacion de duplicidad
$sql = "SELECT * FROM `categorias` WHERE `abreviatura` = '{$_POST["costo"]}' ORDER BY nombre";
    $resultado = $conn->query($sql);
    if ($resultado->num_rows > 0) {
        header('Location: /actualizar_categorias.php?mensaje='.base64_encode("Error la abreviatura ya existe"));
        exit();
    }

if (isset($_GET["id"])){
    $sql = "UPDATE categorias SET habilitada = {$_GET["habilitar"]} WHERE id = ".intval($_GET["id"]);

    if ($conn->query($sql) === TRUE) {
        header('Location: /actualizar_categorias.php?mensaje='.base64_encode("Se deshabilito/habilito la categoria correctamente.-"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{

    $sql = "INSERT INTO `categorias`
                                (`id`,
                                `nombre`,
                                `abreviatura`,
                                `habilitada`,
                                `usuario`)
                            VALUES (NULL,
                            '{$_POST["producto"]}',
                            '{$_POST["costo"]}',
                            '0',
                            '{$_COOKIE["kiosco"]}');";
    if ($conn->query($sql) === TRUE) {
	    header('Location: /actualizar_categorias.php?mensaje='.base64_encode("Se ingreso la categoria {$_POST["producto"]} sin inconvenientes.-"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

}


$conn->close();
exit();
?>
