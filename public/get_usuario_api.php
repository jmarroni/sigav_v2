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

    if (($_REQUEST["id_usuario"] !== NULL)  && intval($_REQUEST["id_usuario"]) != "") {
	    $sql = "SELECT id, name, email FROM users WHERE id = '{$_REQUEST['id_usuario']}'";

	    $resultado = $conn->query($sql) or die($conn->error);

	    if ($resultado->num_rows > 0) {
	        if ($row = $resultado->fetch_assoc()) {
	            echo json_encode($row);
	        } else {
	            echo "{}";
	        }
	    } else { 
	        echo "{}";
	    }
	 }
	
	$conn->close();

	exit();
?>