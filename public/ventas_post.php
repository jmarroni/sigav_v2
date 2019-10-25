<?php
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
if (getRol() < 4 && getRol() != 1) {
    exit();
}

if (PRODUCTOS_LIBRE !== null && PRODUCTOS_LIBRE == "SI" && $_POST["id"] == ''){
    $insert_producto = "INSERT INTO `productos`
                                            (`id`,
                                            `codigo_barras`,
                                            `nombre`,
                                            `precio_unidad`,
                                            `costo`,
                                            `stock`,
                                            `stock_minimo`,
                                            `proveedores_id`,
                                            `categorias_id`,
                                            `usuario`,
                                            `fecha`,
                                            `precio_mayorista`)
                                        VALUES (NULL,
                                        '',
                                        '{$_POST["nombreproducto"]}',
                                        '{$_POST["precio"]}',
                                        '".round($_POST["precio"]/2,2)."',
                                        '{$_POST["cantidad"]}',
                                        '0',
                                        '1',
                                        '1',
                                        '{$_COOKIE["kiosco"]}',
                                        '".date("Y-m-d H:i:s")."',
                                        '0');";
    if ($conn->query($insert_producto) === TRUE) {
        $_POST["id"] = $conn->insert_id;
    }else exit();
}

$sql = "SELECT p.*,st.stock as stock_sucursal,ip.imagen_url as imagen  FROM productos p left join imagen_producto ip ON ip.productos_id = p.id Left JOIN stock st ON (st.productos_id = p.id AND  st.sucursal_id = ".getSucursal($_COOKIE["sucursal"]).") WHERE p.id = ".$_POST["id"];

$resultado = $conn->query($sql);
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
    // output data of each row
    while($row = $resultado->fetch_assoc()) {
        $row["precio_unidad"] = ($_COOKIE["lista_precio"] == 1)?$row["precio_unidad"]:$row["precio_mayorista"];
        $costo = $row["costo"];
        $precio = $row["precio_unidad"];
        $row["stock_sucursal"] =  $row["stock_sucursal"] - $_POST["cantidad"];
        $row["imagen"] = (isset($row["imagen"]))?$row["imagen"]:"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
        $datos = $row;
    }
    
    $datos["fecha"] = date("Y-m-d H:i:s");
    $datos["usuario"] = $_COOKIE["kiosco"];
} else {
    echo "0 results";
}

$emitir = file_get_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir");
if (intval($emitir) === 1 ){ // Habilito la factura online
    $estado = 1;
}else{
    $estado = 3;
}
if (isset($_COOKIE["lista_precio"])) $lista_precio = $_COOKIE["lista_precio"];
else $lista_precio = 1;
$sql = "INSERT INTO ventas VALUES (NULL, '{$_POST["id"]}', 
                                    '{$_POST["cantidad"]}', 
                                    '$precio',
                                    '$costo',
                                    '".date("Y-m-d H:i:s")."',
                                    '{$_COOKIE["kiosco"]}',
                                    '".getSucursal($_COOKIE["sucursal"])."',
                                    '$estado',
                                    NULL,1612,
                                    {$lista_precio})";

if ($conn->query($sql) === TRUE) {
    $sql_update = "UPDATE stock SET stock = (stock - {$_POST["cantidad"]}) WHERE productos_id = ".$_POST["id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);
    $datos["ventas_id"] = $conn->insert_id;
    if ($conn->query($sql_update) === TRUE) {
        echo json_encode($datos);
    } else {
        echo "Error en UPDATE: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}


$conn->close();
exit();
?>
?>