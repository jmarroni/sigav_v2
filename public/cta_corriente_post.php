<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
    if (isset($_POST)){
        $sql = "INSERT INTO `cuenta_corriente`
				            (`id`,
				             `usuario`,
				             `productos_id`,
				             `fecha`,
				             `costo`,
				             `estado`,
				             `usuario_login`)
								VALUES (NULL,
								        '{$_POST["usuario"]}',
								        '{$_POST["producto_id"]}',
								        '".date("Y-m-d H:i:s")."',
								        '{$_POST["costo"]}',
								        '0',
								        '{$_COOKIE["kiosco"]}');"; 
				        
        
        if ($conn->query($sql) === TRUE) {
			$sql_update = "UPDATE stock SET stock = (stock - 1) WHERE productos_id = ".$_POST["producto_id"]." AND sucursal_id = ".getSucursal($_COOKIE["sucursal"]);
			$datos["ventas_id"] = $conn->insert_id;
			if ($conn->query($sql_update) === TRUE) {
				echo json_encode($datos);
			} else {
				echo "Error en UPDATE: " . $sql . "<br>" . $conn->error;
			}
            ///$cabeceras = 'From: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'Reply-To: jmarroni@fidegroup.com.ar' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
            //mail("jmarroni@gmail.com", "Cierre de caja fecha ".date("Y-m-d H:i:s"), "Cierre de caja por ".$_COOKIE["kiosco"].", \n\r Billeter: \n\r- Cien: ".$_POST["cien"]." \n\r- Cincuenta: ".$_POST["cincuenta"]." \n\r- Veinte: ".$_POST["veinte"]." \n\r- Diez: ".$_POST["diez"]." \n\r- Cinco: ".$_POST["cinco"]." \n\r Operacion \n\r {$_POST["operacion"]} \n\r Observacion \n\r {$_POST["observacion"]} \n\r Total: $caja_total Total marcado en venta: ".($total + $caja),$cabeceras);

            header('Location: /cta_corriente.php?mensaje='.base64_encode("Obsequio ingresado correctamnte"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

$conn->close();
exit();
?>