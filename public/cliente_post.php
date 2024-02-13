<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    $habilitar = ($_GET["action"] == "1")?"0":date("Y-m-d H:i:s");

    $sql = "UPDATE clientes SET deshabilitado = '".$habilitar."' WHERE id = ".intval($_GET["identificador"]);
    if ($conn->query($sql) === TRUE) {
    //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
        header('Location: /cliente.php?mensaje='.base64_encode("Se elimino el cliente {$_POST["razon_social"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    exit();
}
if (intval($_POST["id"]) != ""){
    $sql = "UPDATE clientes SET 
                                    razon_social = '{$_POST["razon_social"]}', 
                                    domicilio_legal = '{$_POST["domicilio_legal"]}',
                                    codigo_postal = '{$_POST["codigo_postal"]}',
                                    telefono = '{$_POST["telefono"]}',
                                    provincia = '{$_POST["provincia"]}',
                                    localidad = '{$_POST["localidad"]}',
                                    cuit = '{$_POST["cuit"]}',
                                    condicion_iva = '{$_POST["condicion_iva"]}',
                                    representante = '{$_POST["representante"]}',
                                    email_representante = '{$_POST["email_representante"]}',
                                    responsable_contratacion = '{$_POST["responsable_contratacion"]}',
                                    email_constratacion = '{$_POST["email_constratacion"]}',
                                    responsable_pagos = '{$_POST["responsable_pagos"]}',
                                    email_pagos = '{$_POST["email_pagos"]}',
                                    consulta_proveedores = '{$_POST["consulta_proveedores"]}',
                                    entrega_retiros = '{$_POST["entrega_retiros"]}' WHERE id = ".intval($_POST["id"]);

    if ($conn->query($sql) === TRUE) {
	  //  mail('jmarroni@gmail.com','actualizo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
      header('Location: /cliente.php?mensaje='.base64_encode("Se actualizo el cliente {$_POST["razon_social"]} ok"));
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}else{
    $sql = "INSERT INTO clientes VALUES (NULL, 
                                    '{$_POST["razon_social"]}', 
                                    '{$_POST["domicilio_legal"]}',
                                     '{$_POST["codigo_postal"]}',
                                     '{$_POST["telefono"]}',
                                     '{$_POST["provincia"]}',
                                     '{$_POST["localidad"]}',
                                     '{$_POST["cuit"]}',
                                     '{$_POST["condicion_iva"]}',
                                     '{$_POST["representante"]}',
                                     '{$_POST["email_representante"]}',
                                     '{$_POST["responsable_contratacion"]}',
                                     '{$_POST["email_constratacion"]}',
                                     '{$_POST["responsable_pagos"]}',
                                     '{$_POST["email_pagos"]}',
                                     '{$_POST["consulta_proveedores"]}',
                                     '{$_POST["entrega_retiros"]}',
                                     1,
                                     '".date("Y-m-d H:i:s")."')";
    $html = utf8_encode("
        <table>
            <tr>
                <td>
                    <h3>LOGO/Nombre empresa - Falta definir</h3>
                    <h4>Calle 207 Nro. 1725</h4>
                    <h4>Parque Industrial - Viedma (RN)</h4>
                </td>
            </tr>
            <tr>
                <td><h1>ALTA DE CLIENTE</h1></td>
            </tr>
            <tr>
                <td><b>Nro. de Cliente 1000123</b></td>
            </tr>
            <tr>
                <td><b>Razon Social: </b>{$_POST["razon_social"]}</td>
            </tr>
            <tr>
                <td><b>Domicilio Legal: </b>{$_POST["domicilio_legal"]}</td>
            </tr>
            <tr>
                <td><b>C&oacute;digo Postal: </b>{$_POST["codigo_postal"]}</td>
            </tr>
            <tr>
                <td><b>Localidad: </b>{$_POST["localidad"]}</td>
            </tr>
            <tr>
                <td><b>Provincia: </b>{$_POST["provincia"]}</td>
            </tr>
            <tr>
                <td><b>Tel&eacute;fono/s: </b>{$_POST["telefono"]}</td>
            </tr>
            <tr>
                <td><b>CUIT: </b>{$_POST["cuit"]}</td>
            </tr>
            <tr>
                <td><b>Condici&oacute;n ante el IVA: </b>{$_POST["condicion_iva"]}</td>
            </tr>
            <tr>
                <td><b>Representante Legal: </b>{$_POST["representante"]}</td>
            </tr>
            <tr>
                <td><b>Email: </b>{$_POST["email_representante"]}</td>
            </tr>
            <tr>
                <td><b>Responsable de Contrataci&oacute;n: </b>{$_POST["responsable_contratacion"]}</td>
            </tr>
            <tr>
                <td><b>Email: </b>{$_POST["email_constratacion"]}</td>
            </tr>
            <tr>
                <td><b>Responsable de Pagos: </b>{$_POST["responsable_pagos"]}</td>
            </tr>
            <tr>
                <td><b>Email: </b>{$_POST["email_pagos"]}</td>
            </tr>
            <tr>
                <td><b>Horario de consulta pago a proveedores: </b>{$_POST["consulta_proveedores"]}</td>
            </tr>
            <tr>
                <td><b>Horario de entregas y retiros: </b>{$_POST["entrega_retiros"]}</td>
            </tr>
            <tr style='height:150px;'>
                <td ><b>Firma representante legal: </b>__________________________________________</td>
            </tr>
            <tr style='height:150px;'>
                <td><b>Aclaraci&oacute;n: </b>__________________________________________</td>
            </tr>
            <tr style='height:150px;'>
                <td><b>Cargo: </b>__________________________________________</td>
            </tr>
        </table>
        ");
    //echo $html;exit();
	
    if ($conn->query($sql) === TRUE) {
        $sql ="SELECT LAST_INSERT_ID() as last_insert;";
        $resultado = $conn->query($sql) or die($conn->error);
        $datos = '{"data":"no data"}';
        if ($resultado->num_rows > 0 && (isset($_POST["externo"]))) {
            // output data of each row
            if ($row = $resultado->fetch_assoc()) {
                echo '{"id":"'.$row["last_insert"].'"}';
                exit();
            }
        }
        $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
        $html2pdf->setDefaultFont('Arial');
        $html2pdf->writeHTML("<page>".$html."</page>");
        $nombre = "/clientes/".sha1($_POST["cuit"]).".pdf";
        //$html2pdf->Output();
        $html2pdf->Output(dirname(__FILE__).$nombre, "F");
        $devolucion["cliente"] = $nombre;
        echo json_encode($devolucion);
	//    mail('jmarroni@gmail.com', 'cargo un articulo '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
	    header('Location: /cliente.php?mensaje='.base64_encode("Se ingreso el cliente {$_POST["razon_social"]} ok"));
	  //  mail('jmarroni@gmail.com', 'carga o actualizacion '.$_COOKIE["kiosco"],"Se cargo articulo o actualizo");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

}


$conn->close();
exit();
?>
