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

    // Eliminar
    if (isset($_REQUEST["id_usuario"]) && intval($_REQUEST["id_usuario"]) != "" && isset($_REQUEST["action"])){
        if ($_REQUEST["action"] == "eliminar"){
            $sql = "DELETE FROM oauth_access_tokens WHERE user_id = ".intval($_REQUEST["id_usuario"]);
            if($conn->query($sql) === FALSE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            };

            $sql = "DELETE FROM relacion_users_sucursales WHERE user_id = ".intval($_REQUEST["id_usuario"]);
            if($conn->query($sql) === FALSE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            };

            $sql = "DELETE FROM users WHERE id = ".intval($_REQUEST["id_usuario"]);
            if($conn->query($sql) === FALSE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            };
        }
    }

    /*if ( ($_POST["id"] !== NULL)  && (intval($_POST["id"]) != "")) {
        $opciones = [
            'cost' => 10
        ];

        $contrasena = password_hash($_POST["contrasena"], PASSWORD_BCRYPT, $opciones);

        $sql = "UPDATE users 
                SET 
                    name = '{$_POST["nombre"]}',
                    email = '{$_POST["email"]}',
                    password = '{$contrasena}',
                    updated_at = '".date("Y-m-d H:i:s")."'
                WHERE id = ".intval($_POST["id"]);

        if ($conn->query($sql) === TRUE) {
            $sql = "DELETE FROM relacion_users_sucursales WHERE user_id = ".intval($_POST["id"]);
            if($conn->query($sql) === FALSE) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            };

            if ($_POST["sucursales"] !== NULL && $_POST["sucursales"] !== "") {
                $sucursal_id = $_POST["sucursales"];

                $sql = "INSERT INTO relacion_users_sucursales 
                    VALUES (
                        NULL,
                        '$sucursal_id',
                        '{$_POST["id"]}'
                )";

                if ( $conn->query($sql) === TRUE) {
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }

                header('Location: /usuarios_api.php?mensaje='.base64_encode("Se actualizo el usuario {$_POST["nombre"]} ok"));
            }
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else if($_POST["nombre"]) {
        $contrasena = hash('ripemd160', $_POST["contrasena"]);

        $sql = "INSERT INTO users VALUES (
            NULL,
            '{$_POST["nombre"]}',
            '{$_POST["email"]}',
            '{$contrasena}',
            FALSE,
            '".date("Y-m-d H:i:s")."',
            '".date("Y-m-d H:i:s")."'
        )";

        if ( $conn->query($sql) === TRUE ) {
            $id_user = $conn->insert_id;
            $sucursal_id = $_POST["sucursales"];

            if ($sucursal_id !== NULL && $sucursal_id !== "") {
                $sql = "INSERT INTO relacion_users_sucursales 
                VALUES (
                    NULL,
                    '$sucursal_id',
                    '$id_user'
                )";

                if ( $conn->query($sql) === TRUE) {
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }

            header('Location: /usuarios_api.php?mensaje='.base64_encode("Se ingreso el usuario {$_POST["nombre"]} ok"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }*/

    $conn->close();
    exit();
?>
