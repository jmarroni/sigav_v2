<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit();
}
require_once ("conection.php");
if (getRol() < 5) {
    exit();
}
if (isset($_POST)){
    	$parametros = array("producto_id" => $_POST["producto_id"],
    						"persona" =>  $_POST["persona"],
    						"monto" => $_POST["monto"],
    						"usuario" => $_COOKIE["kiosco"]
    	);
    	$sql = "INSERT INTO cta_cte VALUES (NULL, '{$parametros["persona"]}', '{$parametros["producto_id"]}', '{$parametros["monto"]}', '{$parametros["usuario"]}');";         
        if ($conn->query($sql) === TRUE) {
        	//header('Location: /cta_corriente.php');
        	exit();
        }
}
$conn->close();
exit();
?>