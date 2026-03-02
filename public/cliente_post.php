<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
    exit(); // SEGURIDAD: Agregar exit() después del redirect
}
require_once ("conection.php");
require 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

// Acción de habilitar/deshabilitar cliente
if (isset($_GET["identificador"]) && intval($_GET["identificador"]) != "" && isset($_GET["action"])){
    $habilitar = ($_GET["action"] == "1") ? "0" : date("Y-m-d H:i:s");
    $identificador = intval($_GET["identificador"]);

    $stmt = $conn->prepare("UPDATE clientes SET deshabilitado = ? WHERE id = ?");
    $stmt->bind_param("si", $habilitar, $identificador);

    if ($stmt->execute()) {
        $razon_social = isset($_POST["razon_social"]) ? htmlspecialchars($_POST["razon_social"]) : '';
        header('Location: /cliente.php?mensaje='.base64_encode("Se elimino el cliente {$razon_social} ok"));
    } else {
        echo "Error al actualizar cliente";
    }
    $stmt->close();
    exit();
}

// Actualizar cliente existente
if (isset($_POST["id"]) && intval($_POST["id"]) != ""){
    $stmt = $conn->prepare("UPDATE clientes SET
                            razon_social = ?,
                            domicilio_legal = ?,
                            codigo_postal = ?,
                            telefono = ?,
                            provincia = ?,
                            localidad = ?,
                            cuit = ?,
                            condicion_iva = ?,
                            representante = ?,
                            email_representante = ?,
                            responsable_contratacion = ?,
                            email_constratacion = ?,
                            responsable_pagos = ?,
                            email_pagos = ?,
                            consulta_proveedores = ?,
                            entrega_retiros = ?
                            WHERE id = ?");

    $id = intval($_POST["id"]);
    $razon_social = $_POST["razon_social"] ?? '';
    $domicilio_legal = $_POST["domicilio_legal"] ?? '';
    $codigo_postal = $_POST["codigo_postal"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $provincia = $_POST["provincia"] ?? '';
    $localidad = $_POST["localidad"] ?? '';
    $cuit = $_POST["cuit"] ?? '';
    $condicion_iva = $_POST["condicion_iva"] ?? '';
    $representante = $_POST["representante"] ?? '';
    $email_representante = $_POST["email_representante"] ?? '';
    $responsable_contratacion = $_POST["responsable_contratacion"] ?? '';
    $email_constratacion = $_POST["email_constratacion"] ?? '';
    $responsable_pagos = $_POST["responsable_pagos"] ?? '';
    $email_pagos = $_POST["email_pagos"] ?? '';
    $consulta_proveedores = $_POST["consulta_proveedores"] ?? '';
    $entrega_retiros = $_POST["entrega_retiros"] ?? '';

    $stmt->bind_param("ssssssssssssssssi",
        $razon_social, $domicilio_legal, $codigo_postal, $telefono,
        $provincia, $localidad, $cuit, $condicion_iva, $representante,
        $email_representante, $responsable_contratacion, $email_constratacion,
        $responsable_pagos, $email_pagos, $consulta_proveedores, $entrega_retiros, $id);

    if ($stmt->execute()) {
        header('Location: /cliente.php?mensaje='.base64_encode("Se actualizo el cliente {$razon_social} ok"));
    } else {
        echo "Error al actualizar cliente";
    }
    $stmt->close();
} else {
    // Insertar nuevo cliente
    $stmt = $conn->prepare("INSERT INTO clientes VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

    $razon_social = $_POST["razon_social"] ?? '';
    $domicilio_legal = $_POST["domicilio_legal"] ?? '';
    $codigo_postal = $_POST["codigo_postal"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $provincia = $_POST["provincia"] ?? '';
    $localidad = $_POST["localidad"] ?? '';
    $cuit = $_POST["cuit"] ?? '';
    $condicion_iva = $_POST["condicion_iva"] ?? '';
    $representante = $_POST["representante"] ?? '';
    $email_representante = $_POST["email_representante"] ?? '';
    $responsable_contratacion = $_POST["responsable_contratacion"] ?? '';
    $email_constratacion = $_POST["email_constratacion"] ?? '';
    $responsable_pagos = $_POST["responsable_pagos"] ?? '';
    $email_pagos = $_POST["email_pagos"] ?? '';
    $consulta_proveedores = $_POST["consulta_proveedores"] ?? '';
    $entrega_retiros = $_POST["entrega_retiros"] ?? '';
    $fecha = date("Y-m-d H:i:s");

    $stmt->bind_param("sssssssssssssssss",
        $razon_social, $domicilio_legal, $codigo_postal, $telefono,
        $provincia, $localidad, $cuit, $condicion_iva, $representante,
        $email_representante, $responsable_contratacion, $email_constratacion,
        $responsable_pagos, $email_pagos, $consulta_proveedores, $entrega_retiros, $fecha);

    // Usar htmlspecialchars para el HTML del PDF (prevenir XSS)
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
                <td><b>Razon Social: </b>".htmlspecialchars($razon_social)."</td>
            </tr>
            <tr>
                <td><b>Domicilio Legal: </b>".htmlspecialchars($domicilio_legal)."</td>
            </tr>
            <tr>
                <td><b>C&oacute;digo Postal: </b>".htmlspecialchars($codigo_postal)."</td>
            </tr>
            <tr>
                <td><b>Localidad: </b>".htmlspecialchars($localidad)."</td>
            </tr>
            <tr>
                <td><b>Provincia: </b>".htmlspecialchars($provincia)."</td>
            </tr>
            <tr>
                <td><b>Tel&eacute;fono/s: </b>".htmlspecialchars($telefono)."</td>
            </tr>
            <tr>
                <td><b>CUIT: </b>".htmlspecialchars($cuit)."</td>
            </tr>
            <tr>
                <td><b>Condici&oacute;n ante el IVA: </b>".htmlspecialchars($condicion_iva)."</td>
            </tr>
            <tr>
                <td><b>Representante Legal: </b>".htmlspecialchars($representante)."</td>
            </tr>
            <tr>
                <td><b>Email: </b>".htmlspecialchars($email_representante)."</td>
            </tr>
            <tr>
                <td><b>Responsable de Contrataci&oacute;n: </b>".htmlspecialchars($responsable_contratacion)."</td>
            </tr>
            <tr>
                <td><b>Email: </b>".htmlspecialchars($email_constratacion)."</td>
            </tr>
            <tr>
                <td><b>Responsable de Pagos: </b>".htmlspecialchars($responsable_pagos)."</td>
            </tr>
            <tr>
                <td><b>Email: </b>".htmlspecialchars($email_pagos)."</td>
            </tr>
            <tr>
                <td><b>Horario de consulta pago a proveedores: </b>".htmlspecialchars($consulta_proveedores)."</td>
            </tr>
            <tr>
                <td><b>Horario de entregas y retiros: </b>".htmlspecialchars($entrega_retiros)."</td>
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

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;

        if (isset($_POST["externo"])) {
            echo '{"id":"'.$last_id.'"}';
            $stmt->close();
            $conn->close();
            exit();
        }

        $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
        $html2pdf->setDefaultFont('Arial');
        $html2pdf->writeHTML("<page>".$html."</page>");
        $nombre = "/clientes/".sha1($cuit).".pdf";
        $html2pdf->Output(dirname(__FILE__).$nombre, "F");
        $devolucion["cliente"] = $nombre;
        echo json_encode($devolucion);
        header('Location: /cliente.php?mensaje='.base64_encode("Se ingreso el cliente {$razon_social} ok"));
    } else {
        echo "Error al insertar cliente";
    }
    $stmt->close();
}

$conn->close();
exit();
?>
