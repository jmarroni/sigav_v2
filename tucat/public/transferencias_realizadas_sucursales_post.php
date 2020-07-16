<?php
    if (!isset($_COOKIE["kiosco"])) {
        header('Location: /');
    }

    require_once ("conection.php");

    if (intval($_REQUEST["id_transferencia"]) != ""){
        // Modifico la transferencia
        $sql = "UPDATE transferencias SET 
                    estado_id = '{$_REQUEST["id_estado"]}',
                    comentario = '{$_REQUEST["comentario"]}'";                      
        $sql .= " WHERE id = ".intval($_REQUEST["id_transferencia"]);

        if ($conn->query($sql) === TRUE) {
            if (intval($_REQUEST["id_estado"]) == 4) {
                // Agarro los productos de la bd
                $sql = "SELECT t.sucursal_origen_id, t.sucursal_destino_id, rtp.cantidad, p.id, p.nombre
                        FROM transferencias t
                        JOIN relacion_transferencias_productos rtp ON (t.id = rtp.tranferencia_id)
                        JOIN productos p ON (p.id = rtp.producto_id)
                        WHERE t.id = '{$_REQUEST['id_transferencia']}'";
                $productos = $conn->query($sql);

                // Recorro los productos y cambio el stock
                if ($productos->num_rows > 0) {
                    while($producto = $productos->fetch_assoc()) {
                        $sql = "UPDATE stock SET stock = stock - {$producto['cantidad']} WHERE productos_id = {$producto['id']} and sucursal_id = {$producto['sucursal_origen_id']}";
                        if ($conn->query($sql) === FALSE) {
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }

                        $select = "SELECT * FROM stock WHERE productos_id = {$producto['id']} and sucursal_id = {$producto['sucursal_destino_id']}";
                        $resultado_caja = $conn->query($select);

                        if ($resultado_caja->num_rows > 0) {
                            $sql_update = "UPDATE stock SET stock = stock + {$producto['cantidad']} WHERE productos_id = {$producto['id']} and sucursal_id = {$producto['sucursal_destino_id']}";
                            if ($conn->query($sql_update) === FALSE) {
                                echo "Error: " . $sql_update . "<br>" . $conn->error;
                            }
                        } else {
                            $sql_insert = "INSERT INTO stock VALUES(NULL, {$producto['cantidad']}, 1, '{$producto['sucursal_destino_id']}', '{$_COOKIE["kiosco"]}', {$producto['id']});";
                            if ($conn->query($sql_insert) === FALSE) {
                                echo "Error: " . $sql_insert . "<br>" . $conn->error;
                            }
                        }
                    }
                }
            }

            header('Location: /transferencias_realizadas_sucursales.php?mensaje='.base64_encode("Se actualizo el estado con exito.-"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    $conn->close();
    exit();
?>
