<?php

if (!isset($_COOKIE["kiosco"])) {
    $apiKey = getenv('API_KEY_INTERNAL') ?: null;
    if (!$apiKey || !isset($_GET["apiKey"]) || $_GET["apiKey"] !== $apiKey) {
        http_response_code(401);
        exit(json_encode(['error' => 'No autorizado']));
    }
}
require_once ("conection.php");

header('Content-Type: application/json');

if (isset($_GET["producto"]) && (intval($_GET["producto"])) !== null){
    $sucursal_id = intval($_GET["sucursal"]);
    $producto_id = intval($_GET["producto"]);

    // Usar prepared statements para evitar SQL injection
    $stmt = $conn->prepare("SELECT ip.imagen_url as imagen, p.nombre, p.codigo_barras, p.id, s.stock, s.stock_minimo, p.precio_unidad, p.precio_mayorista, p.costo FROM stock s INNER JOIN productos p ON s.productos_id = p.id LEFT JOIN imagen_producto ip ON ip.productos_id = p.id WHERE sucursal_id = ? AND s.productos_id = ?");
    $stmt->bind_param("ii", $sucursal_id, $producto_id);
    $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
    $stmt->execute();
    $resultado_stock = $stmt->get_result();
    if ($resultado_stock->num_rows > 0) {
        if ($row_stock = $resultado_stock->fetch_assoc()) {
            $datos[0]["value"]         = $row_stock["nombre"]." (".$row_stock["codigo_barras"].")";
            $datos[0]["label"]         = $row_stock["nombre"]." (".$row_stock["codigo_barras"].")";
            $datos[0]["id"]            = $row_stock["id"];
            $datos[0]["costo"]         = $row_stock["costo"];
            $datos[0]["precio"]        = ($lista_precio == 1)?$row["precio_unidad"]:$row["precio_mayorista"];
            $datos[0]["stock"]         = $row_stock["stock"];
            $datos[$i]["imagen"]         = (isset($row["imagen"]))?$row["imagen"]:"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
            $datos[0]["stock_minimo"]  = $row_stock["stock_minimo"];
            $datos[0]["codigo_barras"] = $row_stock["codigo_barras"];
        }
    $stmt->close();
    }else{
        $stmt2 = $conn->prepare("SELECT p.*, ip.imagen_url as imagen FROM productos p LEFT JOIN imagen_producto ip ON ip.productos_id = p.id WHERE p.id = ?");
        $stmt2->bind_param("i", $producto_id);
        $stmt2->execute();
        $resultado = $stmt2->get_result();
        $i = 0;
        if ($resultado->num_rows > 0) {
        // output data of each row
            while($row = $resultado->fetch_assoc()) {
                $datos[$i]["value"]         = $row["nombre"]." (".$row["codigo_barras"].")";
                $datos[$i]["label"]         = $row["nombre"]." (".$row["codigo_barras"].")";
                $datos[$i]["id"]            = $row["id"];
                $datos[$i]["costo"]         = $row["costo"];
                $datos[$i]["precio"]        = ($_COOKIE["lista_precio"] == 1)?$row_stock["precio_unidad"]:$row_stock["precio_mayorista"];
                $datos[$i]["stock"]         = 0;
                $datos[$i]["imagen"]         = (isset($row["imagen"]))?$row["imagen"]:"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
                $datos[$i]["stock_minimo"]  = 0;
                $datos[$i]["codigo_barras"] = $row["codigo_barras"];
            }
        }
        $stmt2->close();
    }
}else{

    $sucursal_seleccionada = (isset($_GET["sucursal"])) ? intval($_GET["sucursal"]) : (isset($_COOKIE["sucursal"]) ? getSucursal($_COOKIE["sucursal"]) : 0);
    $term = isset($_GET["term"]) ? '%' . $_GET["term"] . '%' : '%%';

    // Usar prepared statements para evitar SQL injection
    // LEFT JOIN con condición en el ON para no filtrar productos sin stock
    $stmt3 = $conn->prepare("SELECT
                p.*,
                ip.imagen_url AS imagen,
                COALESCE(st.`stock`, 0) AS stock_sucursal,
                COALESCE(st.`stock_minimo`, 0) AS stock_minimo_sucursal
            FROM
                productos p
                LEFT JOIN stock st ON (st.`productos_id` = p.`id` AND st.`sucursal_id` = ?)
                LEFT JOIN imagen_producto ip ON ip.productos_id = p.id
            WHERE (p.nombre LIKE ? OR p.codigo_barras LIKE ?)
            GROUP BY p.id");
    $stmt3->bind_param("iss", $sucursal_seleccionada, $term, $term);
    $stmt3->execute();
    $resultado = $stmt3->get_result();  
    $i = 0;
    $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
    if ($resultado->num_rows > 0) {
        // output data of each row
        $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
        while($row = $resultado->fetch_assoc()) {
            $datos[$i]["value"]         = utf8_encode($row["nombre"])." (".$row["codigo_barras"].")";
            $datos[$i]["label"]         = utf8_encode($row["nombre"])." (".$row["codigo_barras"].")";
            $datos[$i]["id"]            = $row["id"];
            $datos[$i]["costo"]         = $row["costo"];
            $datos[$i]["precio"]        = ($lista_precio == 1)?$row["precio_unidad"]:$row["precio_mayorista"];
            $datos[$i]["imagen"]         = (isset($row["imagen"]))?str_replace('/'.$row["id"].'/','/'.$row["id"].'/thumb_300x300_',$row["imagen"]):"/assets/img/photos/no-image-featured-image.png";
            $datos[$i]["stock"]         = $row["stock_sucursal"];
            $datos[$i]["stock_minimo"]  = $row["stock_sucursal"];
            $datos[$i]["codigo_barras"] = $row["codigo_barras"];

            // Esto se utiliza en el caso de tener habilitado puestos y sucursales
            if (isset($_GET["sucursal"]) && ($_GET["sucursal"] != "")){
                $sucursal_param = intval($_GET["sucursal"]);
                $producto_param = intval($row["id"]);
                $stmt_stock = $conn->prepare("SELECT stock, stock_minimo FROM stock WHERE sucursal_id = ? AND productos_id = ?");
                $stmt_stock->bind_param("ii", $sucursal_param, $producto_param);
                $stmt_stock->execute();
                $resultado_stock = $stmt_stock->get_result();
                if ($resultado_stock->num_rows > 0) {
                    if ($row_stock = $resultado_stock->fetch_assoc()) {
                        $datos[$i]["stock"]         =   $row_stock["stock"];
                        $datos[$i]["stock_minimo"]  =   $row_stock["stock_minimo"];
                    }
                }else{
                    $datos[$i]["stock"]         =   0;
                    $datos[$i]["stock_minimo"]  =   0;
                }
                $stmt_stock->close();
            } 


            $i ++;
        }
    } else {
        $datos = array("data" => "no data");
    }
    $stmt3->close();
}
echo json_encode($datos ?? []);
$conn->close();
exit();
?>