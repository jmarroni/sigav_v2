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

            header('Location: /cta_corriente.php?mensaje='.base64_encode("Obsequio ingresado correctamnte"));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

$conn->close();
exit();
?>