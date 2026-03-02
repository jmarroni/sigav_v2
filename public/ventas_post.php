<?php
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
require_once ("conection.php");
require_once ("afip_config.php");
mysqli_set_charset($conn,"utf8");
if (getRol() < 4 && getRol() != 1) {
    exit();
}
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (PRODUCTOS_LIBRE !== null && PRODUCTOS_LIBRE == "SI" && $_POST["id"] == ''){
    // Usar prepared statement para evitar SQL injection
    $stmt = $conn->prepare("INSERT INTO `productos`
                            (`id`, `codigo_barras`, `nombre`, `precio_unidad`, `costo`, `stock`, `stock_minimo`,
                             `proveedores_id`, `categorias_id`, `usuario`, `fecha`, `precio_mayorista`, `es_comodato`,
                             `descripcion`, `descripcion_pr`, `descripcion_en`, `material`, `precio_reposicion`,
                             `updated_at`, `created_at`)
                            VALUES (NULL, '', ?, ?, ?, ?, '0', '1', '1', ?, ?, '0', 0, ?, '', '', '', 0, ?, ?)");

    $nombre = $_POST["nombreproducto"];
    $precio = floatval($_POST["precio"]);
    $costo_calc = round($precio/2, 2);
    $cantidad = intval($_POST["cantidad"]);
    $usuario = $_COOKIE["kiosco"];
    $fecha = date("Y-m-d H:i:s");
    $descripcion = 'Producto brindado por sistema ' . date("Y-m-d");

    $stmt->bind_param("sddiissss", $nombre, $precio, $costo_calc, $cantidad, $usuario, $fecha, $descripcion, $fecha, $fecha);

    if ($stmt->execute()) {
        $_POST["id"] = $conn->insert_id;
    } else {
        echo json_encode(["error" => "Error al insertar producto"]);
        exit();
    }
    $stmt->close();
}
// Prepared statement para consulta de producto
$sucursal_id = getSucursal($_COOKIE["sucursal"]);
$producto_id = intval($_POST["id"]);
$stmt = $conn->prepare("SELECT p.*, st.stock as stock_sucursal, ip.imagen_url as imagen
                        FROM productos p
                        LEFT JOIN imagen_producto ip ON ip.productos_id = p.id
                        LEFT JOIN stock st ON (st.productos_id = p.id AND st.sucursal_id = ?)
                        WHERE p.id = ?");
$stmt->bind_param("ii", $sucursal_id, $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
    // output data of each row
    while($row = $resultado->fetch_assoc()) {
        $row["precio_unidad"] = $_POST["precio"];//($_COOKIE["lista_precio"] == 1)?$row["precio_unidad"]:$row["precio_mayorista"];
        $costo = $row["costo"];
        $precio = $row["precio_unidad"];
        $row["stock_sucursal"] =  $row["stock_sucursal"] - $_POST["cantidad"];
        $row["imagen"] = (isset($row["imagen"]))?$row["imagen"]:"http://sistema.mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
        $datos = $row;
    }
    
    $datos["fecha"] = date("Y-m-d H:i:s");
    $datos["usuario"] = $_COOKIE["kiosco"];
} else {
    echo "0 results";
}

$emitir = getAfipValue('emitir');
if (intval($emitir) === 1 ){ // Habilito la factura online
    $estado = 1;
}else{
    $estado = 3;
}

if (isset($_COOKIE["lista_precio"])) $lista_precio = $_COOKIE["lista_precio"];
else $lista_precio = 1;
/*$sql = "INSERT INTO ventas VALUES (NULL, '{$_POST["id"]}', 
                                    '{$_POST["cantidad"]}', 
                                    '$precio',
                                    '$costo',
                                    '".date("Y-m-d H:i:s")."',
                                    '{$_COOKIE["kiosco"]}',
                                    '".getSucursal($_COOKIE["sucursal"])."',
                                    '$estado',
                                    NULL,1612,
                                    {$lista_precio})";*/
$ventas_id = (!empty($_POST["venta_id"]) && intval($_POST["venta_id"]) > 0) ? intval($_POST["venta_id"]) : rand(111111,999999);

// Prepared statement para insertar en carrito
$stmt2 = $conn->prepare("INSERT INTO productos_en_carrito VALUES (NULL, ?, ?, '0', ?, ?, ?, ?, ?, ?)");
$fecha_now = date("Y-m-d H:i:s");
$usuario_cookie = $_COOKIE["kiosco"];
$cantidad_post = intval($_POST["cantidad"]);
$stmt2->bind_param("iissiidd", $ventas_id, $producto_id, $fecha_now, $usuario_cookie, $sucursal_id, $cantidad_post, $precio, $costo);

if ($stmt2->execute()) {
    // QUITAR
    //$sql_update = "UPDATE stock SET stock = (stock - {$_POST["cantidad"]}) WHERE productos_id = ".$_POST["id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);
    // HASTA ACA

    $datos["ventas_id"] = $ventas_id;

    /*$sql = "INSERT INTO relacion_ VALUES (NULL, '{$_POST["id"]}', 
                                    '{$conn->insert_id}',
                                    '{$_POST['id']}')";*/
    
    //if ($conn->query($sql_update) === TRUE) {
    echo json_encode($datos);
    //} else {
    //   echo "Error en UPDATE: " . $sql . "<br>" . $conn->error;
    //}
} else {
    echo json_encode(["error" => "Error al agregar al carrito"]);
}
$stmt2->close();

$conn->close();
exit();
?>