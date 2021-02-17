<?php

if (!isset($_COOKIE["kiosco"])) {
    if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
    header('Location: /');
}
require_once ("conection.php");

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if (isset($_GET["producto"]) && (intval($_GET["producto"])) !== null){

    $sql_stock = "SELECT ip.imagen_url as imagen, p.nombre, p.codigo_barras, p.id,s.stock, s.stock_minimo, p.precio_unidad, p.precio_mayorista, p.costo FROM stock s inner join productos p on s.productos_id = p.id left join imagen_producto ip ON ip.productos_id = p.id WHERE sucursal_id =".intval($_GET["sucursal"])." AND s.productos_id = ".$_GET["producto"];
    echo $sql_stock."<br>";
    $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
    $resultado_stock = $conn->query($sql_stock);
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
    }else{
        $sql = "SELECT p.*,ip.imagen_url as imagen FROM productos p left join imagen_producto ip ON ip.productos_id = p.id WHERE id = ".$_GET["producto"];
        $resultado = $conn->query($sql);
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
    }
}else{

    $sucursal_seleccionada = (isset($_GET["sucursal"]))?intval($_GET["sucursal"]):getSucursal($_COOKIE["sucursal"]);
    $sql = "SELECT 
                p.*,
                ip.imagen_url AS imagen,
                st.`stock` AS stock_sucursal,
                st.`stock_minimo` AS stock_sucursal
            FROM
                productos p 
                LEFT JOIN stock st
                ON st.`productos_id` = p.`id`
                    LEFT JOIN sucursales s
                    ON s.`id` = st.`sucursal_id`
                        LEFT JOIN imagen_producto ip 
                        ON ip.productos_id = p.id 
            WHERE (p.nombre like '%".$_GET["term"]."%' OR p.codigo_barras like '%".$_GET["term"]."%') AND s.id = ".$sucursal_seleccionada;
    $sql .= " GROUP BY p.id";
    //echo $sql;exit();
    $resultado = $conn->query($sql) or die('Error, en la query '.$sql);  
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
                $sql_stock = "SELECT * FROM stock WHERE sucursal_id =".intval($_GET["sucursal"])." AND productos_id = ".$row["id"];
                //echo $sql_stock."<br>";
                $resultado_stock = $conn->query($sql_stock);
                if ($resultado_stock->num_rows > 0) {
                    if ($row_stock = $resultado->fetch_assoc()) {
                        $datos[$i]["stock"]         =   $row_stock["stock"];
                        $datos[$i]["stock_minimo"]  =   $row_stock["stock_minimo"];
                    }
                }else{
                    $datos[$i]["stock"]         =   0; 
                    $datos[$i]["stock_minimo"]  =   0;
                }
            } 


            $i ++;
        }
    } else {
        $datos = array("data" => "no data");
    }
}
//echo json_encode($datos);
$conn->close();
exit();?>