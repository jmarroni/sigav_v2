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

    if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
        if ($_GET["action"] == "eliminar") {
                // elimino stock
                $sql_verificar = "SELECT * FROM stock
                    WHERE productos_id = ".intval($_GET["identificador"]);

                $result = $conn->query($sql_verificar) or die($conn->error." --- ".$sql_verificar);

                $sql_eliminar_stock = 
                        "DELETE FROM stock                          
                            WHERE productos_id = ".intval($_GET["identificador"]).
                            " AND sucursal_id = ".intval($_GET["sucursal"]);

                if ($result->num_rows == 1) {
                    if ($conn->query($sql_eliminar_stock) === TRUE) {
                        $sql = "DELETE FROM productos WHERE id = ".intval($_GET["identificador"]);
                    
                        if ($conn->query($sql) === TRUE) {
                            header('Location: /productos_por_sucursal.php?pagina=1&mensaje='.base64_encode("Se elimino el producto"));
                        } else {
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }
                    } else {
                        echo "Error: " . $sql_eliminar_stock . "<br>" . $conn->error;
                    }

                    exit();
                } else {
                    if ($conn->query($sql_eliminar_stock) === TRUE) {
                        header('Location: /productos_por_sucursal.php?pagina=1&mensaje='.base64_encode("Se elimino el producto"));
                    } else {
                        echo "Error: " . $sql_eliminar_stock . "<br>" . $conn->error;
                    }

                    exit();
                }
        }
    }

    // Almaceno las imagenes en un arreglo
    $imagenes = array(
        "1" =>$_FILES["imagen3"],
        "2" => $_FILES["imagen2"],
        "3" => $_FILES["imagen1"]
    );

    // Llamo funcion imagen con el arreglo de imagenes y guardo las ulrs
    $urlImagenes = imagen($imagenes);

    function imagen($imagenes) {
        $urlImagenes = array();

        foreach ($imagenes as $imagen) {
            // Me fijo si envio una imagen y la subo
            if (isset($imagen["name"]) && $imagen["name"] != ""){
                $target_dir = dirname(__FILE__)."/upload_articles/";
                $target_file = $target_dir . basename($imagen["name"]);
                $uploadOk = 1;
                $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                if (strtolower($imageFileType) != "png" && strtolower($imageFileType) != "jpg" && strtolower($imageFileType) != "jpeg"){
                // echo "no hay imagen 1";
                header('Location: /productos_por_sucursal.php?pagina=1&mensaje='.base64_encode('Extension de archivo incorrecto debe ser (png o jpg)'));exit();
                }
                if (move_uploaded_file($imagen["tmp_name"], $target_file)) {
                    // echo "no hay imagen 2";
                    $xmlFile = date("YmdHis")."-".rand(11111111,99999999).".".strtolower($imageFileType);

                    // Verifico como redimensiono parejo
                    $sizeI = getimagesize($target_file);

                    $ancho = 180;
                    $largo = round($ancho * $sizeI[1] / $sizeI[0],0);

                    sleep(2); redim ($target_file,$target_dir.$xmlFile,$ancho,$largo); 
                    // rename($target_file, $target_dir.$xmlFile);
                    array_unshift($urlImagenes, "/upload_articles/".$xmlFile);
                }
            }else{
                array_push($urlImagenes, "");
                // echo "no hay imagen";
            }
        }

        return $urlImagenes;
    }

    if (($_POST["id"] !== NULL)  && intval($_POST["id"]) != ""){
        $categoria = (isset($_POST["categoria"]))?explode("_", $_POST["categoria"])[0]:1;
        $sql = "UPDATE productos SET 
                                        precio_unidad = '{$_POST["precio_unidad"]}',
                                        costo = '{$_POST["costo"]}',
                                        proveedores_id = '{$_POST["proveedor"]}',
                                        categorias_id = '$categoria',
                                        stock = '{$_POST["stock"]}',
                                        nombre = '{$_POST["producto"]}',
                                        stock_minimo = '{$_POST["stock_minimo"]}',
                                        codigo_barras =  '{$_POST["codigo_de_barras"]}',
                                        precio_mayorista = '{$_POST["precio_mayorista"]}' WHERE id = ".intval($_POST["id"]);
        $id = $_POST["id"];
        if ($conn->query($sql) === TRUE) {
            $sql_ya_tiene_stock = "SELECT * FROM stock WHERE productos_id = $id AND sucursal_id = {$_POST["sucursal"]}";

            $resultado_stock = $conn->query($sql_ya_tiene_stock) or die($conn->error." --- ".$sql_ya_tiene_stock);

            if ($resultado_stock->num_rows > 0) {
                $sql_stock = "UPDATE stock 
                                SET stock = '{$_POST["stock_sucursal"]}' 
                                WHERE productos_id = ".intval($_POST["id"])." AND sucursal_id = ".intval($_POST["sucursal"]);
            } else {
                $sql_stock = "INSERT INTO stock VALUES (
                                            NULL, 
                                            '{$_POST["stock_sucursal"]}', 
                                            '1',
                                            {$_POST["sucursal"]},
                                            '$usuario',
                                            $id)";
            }

            if ($conn->query($sql_stock) === TRUE) {
                if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5"){
                    set_image($id,$urlImagenes);
                    header('Location: /productos_por_sucursal.php?pagina=1&mensaje='.base64_encode("Se actualizo el producto {$_POST["producto"]} ok"));
                }else{
                    echo '{"proceso":"OK"}'; // Viene desde movil
                }
            } else {
                echo "Error: " . $sql_stock . "<br>" . $conn->error;
            }
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }else{
        $codigo_barras = "";
        $arrCategoria = explode("_",$_POST["categoria"]);
        $identificador = "0000000001";
        $sql = "SELECT id FROM productos p ORDER BY id DESC LIMIT 1";
        $resultado = $conn->query($sql);
        if ($resultado->num_rows > 0) {
            if ($row = $resultado->fetch_assoc()) {
                $id = $row["id"] + 1;
                $identificador = substr("000000000".$id,-10);
            }
        }
        if ($_POST["codigo_de_barras"] != ""){ $codigo_barras =  $_POST["codigo_de_barras"];}
        else $codigo_barras = $arrCategoria[1].substr("000000".$_POST["proveedor"],-5).$identificador;
        if (isset($_COOKIE["kiosco"])) $usuario = $_COOKIE["kiosco"]; else $usuario = "sistema";
        $sql = "INSERT INTO productos VALUES (NULL, '$codigo_barras', 
                                        '{$_POST["producto"]}', 
                                        '{$_POST["precio_unidad"]}',
                                        '{$_POST["costo"]}',
                                        '{$_POST["stock"]}',
                                        '{$_POST["stock_minimo"]}',
                                        '{$_POST["proveedor"]}',
                                        '{$_POST["categoria"]}',
                                        '$usuario',
                                        '".date("Y-m-d H:i:s")."',
                                        '{$_POST["precio_mayorista"]}')";

        if ($conn->query($sql) === TRUE) {
            $id = $conn->insert_id;

            // INSERTAR STOCK
            $sql_stock = "INSERT INTO stock VALUES (
                                        NULL, 
                                        '{$_POST["stock_sucursal"]}', 
                                        '1',
                                        {$_POST["sucursal"]},
                                        '$usuario',
                                        $id)";
            
            if ($conn->query($sql_stock) === TRUE) {
                if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5"){
                    echo $id;
                    set_image($id, $urlImagenes,true);
                    header('Location: /productos_por_sucursal.php?pagina=1&mensaje='.base64_encode("Se ingreso el producto {$_POST["producto"]} correctamente"));
                } else {
                    echo '{"proceso":"OK"}'; // Viene desde movil
                }
            } else {
                echo "Error: " . $sql_stock . "<br>" . $conn->error;
            }
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    function set_image($id,$urlImagenes,$es_nuevo = false){
        global $conn;
            if ($es_nuevo){
                foreach ($urlImagenes as $urlImagen) {
                    if ($urlImagen == "") $urlImagen = "assets/img/photos/no-image-featured-image.png";
                    $sql = "INSERT INTO imagen_producto VALUES (NULL,'$urlImagen','$id');";

                    if ($conn->query($sql) === TRUE){}
                    else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }else{
                if($urlImagenes[0] || $urlImagenes[1] || $urlImagenes[2]) {
                    $sql = "DELETE FROM imagen_producto WHERE productos_id = ".intval($id);
                    if ($conn->query($sql) === TRUE) {
                        foreach ($urlImagenes as $urlImagen) {
                            if ($urlImagen == "") $urlImagen = "assets/img/photos/no-image-featured-image.png";
                            $sql = "INSERT INTO imagen_producto VALUES (NULL,'$urlImagen','$id');";
                            if ($conn->query($sql) === TRUE){}
                            else {
                                echo "Error: " . $sql . "<br>" . $conn->error;
                            }
                        } 
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }           
    }

    $conn->close();
    exit();
?>