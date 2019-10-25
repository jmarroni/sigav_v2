<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	if (!isset($_COOKIE["kiosco"])) {
		header('Location: /');
	}

	require_once ("conection.php");

	$productos = explode("||", $_GET["productos"]);
	$sucursal_origen = $_GET["origen"];
	$sucursal_destino = $_GET["destino"];

	// SQL transferencia
	$sql = "INSERT INTO transferencias VALUES (NULL, '{$sucursal_origen}', '{$sucursal_destino}', '".date("Y-m-d H:i:s")."', 1, '', '{$_COOKIE["kiosco"]}')";
	if ($conn->query($sql) === FALSE) {
		echo "Error: " . $sql . "<br>" . $conn->error;
	}

	// Id de la transferencia
	$id_transferencia = $conn->insert_id;

	foreach ($productos as $key => $value) {
		$producto_stock = explode(",", $value);

		// SQL transferencia_producto
		$sql = "INSERT INTO relacion_transferencias_productos VALUES (NULL, '{$id_transferencia}', '{$producto_stock[0]}', '{$producto_stock[1]}', '{$_COOKIE["kiosco"]}')";
		if ($conn->query($sql) === FALSE) {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}

	echo "OK";
	$conn->close();
	exit();
?>