<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
    if (isset($_POST)){
    	$caja_total = intval($_POST["cien"]) * 100 +
				    	intval($_POST["cincuenta"]) * 50 +
				    	intval($_POST["veinte"]) * 20 +
				    	intval($_POST["diez"]) * 10 +
				    	intval($_POST["cinco"]) * 5;
        $sql = "INSERT INTO caja VALUES (NULL, '{$_POST["cien"]}', 
                                        '{$_POST["cincuenta"]}', 
                                        '{$_POST["veinte"]}',
                                         '{$_POST["diez"]}',
                                         '{$_POST["cinco"]}',
                                         '".date("Y-m-d H:i:s")."',
                                         '{$_COOKIE["kiosco"]}',
                                         '{$_POST["operacion"]}',
                                         '{$_POST["observacion"]}',
										 '$caja_total',
										 '".getSucursal($_COOKIE["sucursal"])."')"; 
        
        
        if ($conn->query($sql) === TRUE) {
        	
        	//Productos vendidos hoy por el usuario
        	$sql_ventas = "SELECT * FROM `ventas` v WHERE `fecha` > '".date("Y-m-d")."' and usuario = '".$_COOKIE["kiosco"]."'";
        	$resultado_ventas = $conn->query($sql_ventas);
        	$total = 0;
        	$cantidad_de_ventas_usuario = 0;
        	if ($resultado_ventas->num_rows > 0) {
        		while($row = $resultado_ventas->fetch_assoc()) {
        			$total += $row["precio"] * $row["cantidad"];
        		}
        	}
        	
        	//Caja que inicio como apertura.
        	$sql_caja = "SELECT * FROM `caja` WHERE fecha > '".date("Y-m-d 00:00:01")."' and usuario = '".$_COOKIE["kiosco"]."' and sucursal_id = '".getSucursal($_COOKIE["sucursal"])."' and operacion = 1 order by id desc";
        	$resultado_caja= $conn->query($sql_caja) or die(mysqli_error($conn)." Q=".$sql_caja);
        	if ($resultado_caja->num_rows > 0) {
        		if ($row_caja= $resultado_caja->fetch_assoc()) {
        					$caja += $row_caja["cien"] * 100 +
        					$row_caja["cincuenta"] * 50 +
        					$row_caja["veinte"] * 20 +
        					$row_caja["diez"] * 10 +
        					$row_caja["cinco"] * 5;
        		}
        	}
        	
            $cabeceras = 'From: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'Reply-To: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
            mail("jmarroni@gmail.com", "Cierre de caja fecha ".date("Y-m-d H:i:s"), "Cierre de caja por ".$_COOKIE["kiosco"].", \n\r Billeter: \n\r- Cien: ".$_POST["cien"]." \n\r- Cincuenta: ".$_POST["cincuenta"]." \n\r- Veinte: ".$_POST["veinte"]." \n\r- Diez: ".$_POST["diez"]." \n\r- Cinco: ".$_POST["cinco"]." \n\r Operacion \n\r {$_POST["operacion"]} \n\r Observacion \n\r {$_POST["observacion"]} \n\r Total: $caja_total Total marcado en venta: ".($total + $caja),$cabeceras);

            header('Location: /cierre_caja.php?mensaje='.base64_encode("Caja ingresada correctamente"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

$conn->close();
exit();
?>