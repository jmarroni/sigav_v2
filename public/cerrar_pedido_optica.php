<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
if (getRol() < 4) {
    exit();
}

$sql = "INSERT INTO pedidos_optica VALUES (NULL,'".$_GET["doctor"]."',
                                    '".$_GET["fecha_rp"]."',
                                    '".$_GET["pedido"]."',
                                    '".$_GET["retira"]."',
                                    '".$_GET["d_esf"]."',
                                    '".$_GET["d_eje"]."',
                                    '".$_GET["d_dip"]."',
                                    '".$_GET["d_alt_pel"]."',
                                    '".$_GET["producto"]."',
                                    '".$_GET["armazon"]."',
                                    '".$_GET["i_esf"]."',
                                    '".$_GET["i_eje"]."',
                                    '".$_GET["i_dip"]."',
                                    '".$_GET["i_alt_pel"]."',
                                    '".$_GET["cliente"]."')";

if ($conn->query($sql) === TRUE) {
 //   header('Location: /pedidos_optica.php?mensaje='.base64_encode("Se ingreso el perfil {$_POST["nombre"]} ok"));
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}


$conn->close();
exit();
?>
