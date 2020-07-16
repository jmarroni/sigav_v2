<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
//print_r($_POST);
//exit();
//Verificacion de duplicidad

if (isset($_GET["action"])){
    // Elimino la relacion primero
    $sql = "DELETE FROM relacion_categoria_proveedor WHERE proveedor_id = ".intval($_GET["id"]);

    if ($conn->query($sql) === TRUE) {
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $sql = "DELETE FROM proveedor WHERE id = ".intval($_GET["id"]);

    if ($conn->query($sql) === TRUE) {
        header('Location: /actualizar_artesanos.php?mensaje='.base64_encode("Se elimino el artesano correctamente.-"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
if (isset($_GET["id"])){
    $sql = "UPDATE categorias SET habilitada = {$_GET["habilitar"]} WHERE id = ".intval($_GET["id"]);

    if ($conn->query($sql) === TRUE) {
        header('Location: /actualizar_categorias.php?mensaje='.base64_encode("Se deshabilito/habilito la categoria correctamente.-"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    if ( isset($_POST["id_proveedor"]) && $_POST["id_proveedor"] !== '' ) {
        $sql = "UPDATE `proveedor`
                        SET  nombre = '{$_POST["nombre"]}', 
                        apellido = '{$_POST["apellido"]}',
                        direccion = '{$_POST["direccion"]}',
                        ciudad = '{$_POST["ciudad"]}',
                        provincia = '{$_POST["provincia"]}',
                        telefono =  '{$_POST["telefono"]}',
                        mail = '{$_POST["mail"]}',
                        usuario = '{$_COOKIE["kiosco"]}',
                        sitio_web = '{$_POST["sitio"]}'
                        WHERE id = '{$_POST["id_proveedor"]}';";
    } else {
        $sql = "INSERT INTO `proveedor`
                        (`id`,
                        `nombre`,
                        `apellido`,
                        `direccion`,
                        `ciudad`,
                        `provincia`,
                        `telefono`,
                        `mail`,
                        `usuario`,
                        `sitio_web`)
                        VALUES (NULL,
                        '{$_POST["nombre"]}',
                        '{$_POST["Apellido"]}',
                        '{$_POST["direccion"]}',
                        '{$_POST["ciudad"]}',
                        '{$_POST["provincia"]}',
                        '{$_POST["telefono"]}',
                        '{$_POST["mail"]}',
                        '{$_COOKIE["kiosco"]}',
                        '{$_POST["sitio"]}');";
    }
    
    if ($conn->query($sql) === TRUE) {
        if ( isset($_POST["id_proveedor"]) && $_POST["id_proveedor"] !== '' ) {
            $sql_eliminar_relacion = "DELETE FROM relacion_categoria_proveedor WHERE proveedor_id = '{$_POST["id_proveedor"]}'";
            
            if ($conn->query($sql_eliminar_relacion) === TRUE) {
            } else {
                echo "Error SQL Relacion: " . $sql_relacion . "<br>" . $conn->error;
            }

            $lastInsertId = $_POST["id_proveedor"];
        } else {
            $lastInsertId = $conn->insert_id;
        }

        foreach ($_POST["categoria"] as $key => $value) {
            $sql_relacion = "INSERT INTO `relacion_categoria_proveedor`
            (`id`,
            `proveedor_id`,
            `categoria_id`)
            VALUES ('id',
            '$lastInsertId',
            '$value');";

            if ($conn->query($sql_relacion) === TRUE) {
            } else {
                echo "Error SQL Relacion: " . $sql_relacion . "<br>" . $conn->error;
            }

        }

        if ( $_POST["id_proveedor"] !== NULL  && $_POST["id_proveedor"] != "" ) {
            header('Location: ./actualizar_artesanos.php?mensaje='.base64_encode("Se modifico el artesano {$_POST["nombre"]} sin inconvenientes.-"));
        } else {
            header('Location: ./actualizar_artesanos.php?mensaje='.base64_encode("Se ingreso el artesano {$_POST["nombre"]} sin inconvenientes.-"));
        }
    } else {
        echo "Error SQL: " . $sql . "<br>" . $conn->error;
    }
}

    $conn->close();
    exit();
?>
