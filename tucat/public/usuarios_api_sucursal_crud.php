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

    switch ($_POST["metodo"]) {
        case 'post':
            if ($_POST["usuario_id"] !== NULL && $_POST["usuario_id"] !== "") {
                $usuario_id = $_POST["usuario_id"];
                $sucursal_id = $_POST["sucursal_id"];

                $sql_verificar = "SELECT * FROM relacion_users_sucursales WHERE sucursal_id = '$sucursal_id' AND user_id = '$usuario_id'";

                $resultado_sucursales = $conn->query($sql_verificar);

                if ($resultado_sucursales->num_rows <= 0) {
                    $sql = "INSERT INTO relacion_users_sucursales 
                        VALUES (
                            NULL,
                            '$sucursal_id',
                            '$usuario_id'
                    )";

                    if ( $conn->query($sql) === TRUE) {
                        header('Location: /usuarios_api.php?mensaje='.base64_encode("Se agrego la sucursal al usuario ok"));
                        exit();
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }
            break;
        
        default:
            if ($_POST["usuario_id"] !== NULL && $_POST["usuario_id"] !== "") {
                $usuario_id = $_POST["usuario_id"];
                $sucursal_id = $_POST["sucursal_id"];

                $sql = "DELETE FROM relacion_users_sucursales 
                        WHERE sucursal_id = '$sucursal_id' AND user_id = '$usuario_id'";

                if ( $conn->query($sql) === TRUE) {
                    header('Location: /usuarios_api.php?mensaje='.base64_encode("Se quito la sucursal al usuario ok"));
                    exit();
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }
            break;
    }

    $conn->close();
    exit();
?>
