<?php
$descontar_stock = 0;
if (!isset($_COOKIE["kiosco"])) {
    exit();
}
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once ("conection.php");
require_once ("afip_config.php");
require 'vendor/autoload.php';

// Funcion para limpiar valores "undefined" de JavaScript
function limpiarUndefined($valor, $default = '') {
    if ($valor === null || $valor === '' || $valor === 'undefined' || $valor === 'null') {
        return $default;
    }
    return $valor;
}

// Funcion para loguear errores de facturacion
function logFacturacion($tipo, $mensaje, $datos = []) {
    $logDir = dirname(__DIR__) . '/storage/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . 'facturacion_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $usuario = $_COOKIE["kiosco"] ?? 'desconocido';
    $modo = function_exists('getAfipMode') ? getAfipMode() : 'no definido';
    $logEntry = "[{$timestamp}] [{$tipo}] [{$modo}] [Usuario: {$usuario}] {$mensaje}";
    if (!empty($datos)) {
        $logEntry .= " | Datos: " . json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
    $logEntry .= PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Loguear inicio de facturacion con parametros recibidos
logFacturacion('INFO', 'Inicio de facturacion', [
    'GET' => $_GET,
    'venta_id' => $_GET['venta_id'] ?? 'no definido'
]);
use Spipu\Html2Pdf\Html2Pdf;

$stockfinal = 0;
$stockanterior = 0;
$stockminimo = 0;
$arrSucursal = array();
$resultados_productos_en_carrito = 0;

if (isset($_COOKIE["sucursal"])){
    $sucursal_id = getSucursal($_COOKIE["sucursal"]);
    $stmt = $conn->prepare("SELECT * FROM sucursales WHERE id = ?");
    $stmt->bind_param("i", $sucursal_id);
    $stmt->execute();
    $resultado_perfil = $stmt->get_result();
    if ($resultado_perfil->num_rows > 0) {
        $arrSucursal = $resultado_perfil->fetch_assoc();
    } else {
        exit();
    }
    $stmt->close();
} else {
    exit();
}

// DATOS PARA EL COMPROBANTE (desde directorio seguro)
$comprobante = getAfipValue('comprobante');
$ptovta = $arrSucursal["pto_vta"];
$cuit = getAfipValue('cuit');
$condicion_iva = getAfipValue('condicion_iva');
$inicio_actividades = getAfipValue('inicio_actividades');
$ingresos_brutos = getAfipValue('ingresos_brutos');

// Sanitizar inputs (limpiar valores "undefined" de JavaScript)
$documento = limpiarUndefined($_GET["documento"] ?? "");
$nombre = limpiarUndefined($_GET["nombre"] ?? "");
$tipoDocumento = limpiarUndefined($_GET["tipo-documento"] ?? "");
$tipo = limpiarUndefined($_GET["tipo"] ?? "", "1");
$iva = limpiarUndefined($_GET["iva"] ?? "", "4");
$direccion = limpiarUndefined($_GET["direccion"] ?? "");
$descontar_stock = 1;
$venta_id = intval($_GET['venta_id'] ?? 0);
$presupuesto = intval($_GET["presupuesto"] ?? 0);

// Me fijo si lo tengo que insertar como cliente o ya autocompleto
$clientes_id = limpiarUndefined($_GET["clientes_id"] ?? "");
if (empty($clientes_id) && !empty($nombre)) {
    $stmt = $conn->prepare("INSERT INTO `clientes` (`id`, `razon_social`, `domicilio_legal`, `codigo_postal`, `telefono`, `provincia`, `localidad`, `cuit`, `condicion_iva`, `representante`, `email_representante`, `responsable_contratacion`, `email_constratacion`, `responsable_pagos`, `email_pagos`, `consulta_proveedores`, `entrega_retiros`, `fecha_alta`, `deshabilitado`) VALUES (NULL, ?, ?, '', '', '', '', ?, ?, '', '', '', '', '', '', '', '', '', '')");
    $stmt->bind_param("ssss", $nombre, $direccion, $cuit, $iva);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT * FROM perfil";
$resultado_perfil = $conn->query($sql);
$logo_default = __DIR__ . "/assets/img/photos/no-image-featured-image.png";
$logo_html = ""; // Por defecto sin logo

// Funcion para convertir imagen a base64 data URI
function imageToBase64($path) {
    $imageInfo = @getimagesize($path);
    if (!$imageInfo) return false;
    $imageData = @file_get_contents($path);
    if (!$imageData) return false;
    return 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
}

if ($resultado_perfil->num_rows > 0) {
    if ($row_perfil = $resultado_perfil->fetch_assoc()) {
        $logo_path = __DIR__ . $row_perfil["logo"];

        // Verificar que el archivo existe y convertir a base64
        if (!empty($row_perfil["logo"]) && file_exists($logo_path)) {
            $logo_base64 = imageToBase64($logo_path);
            if ($logo_base64) {
                $logo_html = "<img src='{$logo_base64}' style='height:80px;width:120px;'/>";
            }
        }

        // Si no hay logo, usar default
        if (empty($logo_html) && file_exists($logo_default)) {
            $logo_base64 = imageToBase64($logo_default);
            if ($logo_base64) {
                $logo_html = "<img src='{$logo_base64}' style='height:80px;width:120px;'/>";
            }
        }

        $nombre_fantasia = $row_perfil["nombre"];
        $datos_factura = $row_perfil;
    }
} else {
    if (file_exists($logo_default)) {
        $logo_base64 = imageToBase64($logo_default);
        if ($logo_base64) {
            $logo_html = "<img src='{$logo_base64}' style='height:80px;width:120px;'/>";
        }
    }
    $nombre_fantasia = "SIGAV";
}

$lista_precio = $_COOKIE["lista_precio"] ?? 1;
$emitir = getAfipValue('emitir');
$estado = (intval($emitir) === 1) ? 1 : 3;

// Agarro los productos del carrito para esta venta
$stmt = $conn->prepare("SELECT * FROM productos_en_carrito WHERE venta_id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$resultados_productos_en_carrito = $stmt->get_result();

$array_ids_ventas = [];
$usuario_cookie = $_COOKIE["kiosco"];
$fecha_now = date("Y-m-d H:i:s");
$date = date("Y-m-d");

if ($resultados_productos_en_carrito->num_rows > 0) {
    while ($producto_en_carrito = $resultados_productos_en_carrito->fetch_assoc()) {
        $producto_id = intval($producto_en_carrito["producto_id"]);
        $cantidad = intval($producto_en_carrito["cantidad"]);

        if ($descontar_stock == 1) {
            // Consultar stock
            $stmt_stock = $conn->prepare("SELECT stock, stock_minimo FROM stock WHERE productos_id = ? AND sucursal_id = ?");
            $stmt_stock->bind_param("ii", $producto_id, $sucursal_id);
            $stmt_stock->execute();
            $resultadoquery = $stmt_stock->get_result()->fetch_assoc();
            $stmt_stock->close();

            $stockanterior = $resultadoquery["stock"] ?? 0;
            $stockminimo = $resultadoquery["stock_minimo"] ?? 0;
            $stockfinal = $stockanterior - $cantidad;

            // Descontar stock
            $stmt_upd = $conn->prepare("UPDATE stock SET stock = (stock - ?) WHERE productos_id = ? AND sucursal_id = ?");
            $stmt_upd->bind_param("iii", $cantidad, $producto_id, $sucursal_id);

            if ($stmt_upd->execute()) {
                // Log de stock
                $stmt_log = $conn->prepare("INSERT INTO stock_logs (stock_anterior, stock_minimo_anterior, stock, stock_minimo, sucursal_id, usuario, productos_id, updated_at, created_at, tipo_operacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'VENTA')");
                $stmt_log->bind_param("iiiisisss", $stockanterior, $stockminimo, $stockfinal, $stockminimo, $sucursal_id, $usuario_cookie, $producto_id, $date, $date);
                $stmt_log->execute();
                $stmt_log->close();
            }
            $stmt_upd->close();
        }

        // Insertar venta
        $precio = floatval($producto_en_carrito['precio']);
        $costo = floatval($producto_en_carrito['costo']);
        $stmt_venta = $conn->prepare("INSERT INTO ventas VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, '3', NULL, '1612', ?)");
        $stmt_venta->bind_param("iiddssii", $producto_id, $cantidad, $precio, $costo, $fecha_now, $usuario_cookie, $sucursal_id, $lista_precio);

        if ($stmt_venta->execute()) {
            array_push($array_ids_ventas, $conn->insert_id);
        }
        $stmt_venta->close();
    }
}
$stmt->close();

// Obtener ventas del día
$fecha_hoy = date("Y-m-d 00:00:00");
$stmt = $conn->prepare("SELECT v.*, p.nombre as nombre_producto FROM ventas v INNER JOIN productos p ON p.id = v.productos_id WHERE v.estado = 3 AND v.fecha > ? AND v.sucursal_id = ? AND v.usuario = ?");
$stmt->bind_param("sis", $fecha_hoy, $sucursal_id, $usuario_cookie);
$stmt->execute();
$resultado = $stmt->get_result();

$item = array();
$total = 0;
$datos = array();
$datos_productos = array();

if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $total += $row["precio"] * $row["cantidad"];
        $datos_productos[] = $row;

        // Actualizar estado
        $stmt_upd = $conn->prepare("UPDATE ventas SET estado = 5 WHERE id = ?");
        $stmt_upd->bind_param("i", $row["id"]);
        $stmt_upd->execute();
        $stmt_upd->close();
    }
} else {
    logFacturacion('ERROR', 'No existen productos para facturar', [
        'venta_id' => $venta_id,
        'fecha_hoy' => $fecha_hoy,
        'sucursal_id' => $sucursal_id,
        'usuario' => $usuario_cookie
    ]);
    $devolucion["error"] = "No existen productos para facturar";
    echo json_encode($devolucion);
    exit();
}
$stmt->close();

// Obtener y validar fecha de facturacion
$fechaParam = $_GET["fecha-facturacion"] ?? '';
if (empty($fechaParam) || $fechaParam == 'undefined' || $fechaParam == 'null') {
    $fechaParam = date("Y-m-d");
    logFacturacion('WARNING', 'Fecha no recibida, usando fecha actual', ['fecha_usada' => $fechaParam]);
}
$fecha = explode("-", $fechaParam);
if (count($fecha) < 3 || !checkdate((int)$fecha[1], (int)$fecha[2], (int)$fecha[0])) {
    logFacturacion('ERROR', 'Fecha invalida', [
        'fecha_recibida' => $fechaParam,
        'fecha_array' => $fecha
    ]);
    echo json_encode(["error" => "Fecha inválida: " . $fechaParam]);
    exit();
}

// Usar modo configurado (produccion/homologacion)
$afip = new Afip(getAfipConfig(floatval($cuit)));
$es_homologacion = !isAfipProduction() ? 1 : 0;

if (intval($comprobante) != 11) {
    $ImpNeto = round($total / 1.21, 2);
    $impuestoIVA = round($ImpNeto * 0.21, 2);
} else {
    $impuestoIVA = 0;
    $ImpNeto = $total;
}

// Si no hay documento válido, usar consumidor final (99, 0)
if (!empty($tipoDocumento) && !empty($documento) && is_numeric($tipoDocumento) && is_numeric($documento)) {
    $tipoDocumento = intval($tipoDocumento);
    $documento = intval($documento);
} else {
    $tipoDocumento = 99;  // Consumidor final
    $documento = 0;
}

// Mapear condición IVA de la app a códigos AFIP (RG 5616)
// App: 1=Resp.Inscripto, 2=Monotributista, 3=Exento, 4=Cons.Final
// AFIP: 1=IVA Resp.Inscripto, 4=IVA Sujeto Exento, 5=Consumidor Final, 6=Resp.Monotributo
$mapeoIvaAfip = [
    '1' => 1,  // Responsable Inscripto
    '2' => 6,  // Monotributista
    '3' => 4,  // Exento
    '4' => 5,  // Consumidor Final
];
$condicionIvaReceptor = isset($mapeoIvaAfip[$iva]) ? $mapeoIvaAfip[$iva] : 5; // Default: Consumidor Final

$data = array(
    'CantReg' => 1,
    'PtoVta' => intval($ptovta),
    'CbteTipo' => intval($comprobante),
    'Concepto' => 1,
    'DocTipo' => intval($tipoDocumento),
    'DocNro' => intval($documento),
    'CondicionIVAReceptorId' => $condicionIvaReceptor,
    'CbteFch' => $fecha[0] . $fecha[1] . $fecha[2],
    'ImpTotal' => $total,
    'ImpTotConc' => 0,
    'ImpNeto' => $ImpNeto,
    'ImpOpEx' => 0,
    'ImpIVA' => $impuestoIVA,
    'ImpTrib' => 0,
    'MonId' => 'PES',
    'MonCotiz' => 1,
);

if (intval($comprobante) != 11) {
    $data['Iva'] = array(
        array(
            'Id' => 5,
            'BaseImp' => $ImpNeto,
            'Importe' => $impuestoIVA
        )
    );
}

$voucher_info = "";
$nro_presupuesto = 0;

if ($presupuesto == 0) {
    try {
        // Log de datos enviados a AFIP para debug
        logFacturacion('DEBUG', 'Datos a enviar a AFIP', [
            'iva_recibido' => $iva,
            'condicionIvaReceptor' => $condicionIvaReceptor,
            'data_completa' => $data
        ]);
        $res = $afip->ElectronicBilling->CreateNextVoucher($data);
        $resCAEFchVto = explode("-", $res["CAEFchVto"]);
        $res["CAEFchVto"] = $resCAEFchVto[2] . "-" . $resCAEFchVto[1] . "-" . $resCAEFchVto[0];
        $voucher_info = $afip->ElectronicBilling->GetVoucherInfo($afip->ElectronicBilling->GetLastVoucher($ptovta, $comprobante), $ptovta, $comprobante);
    } catch (Exception $e) {
        // Revertir ventas - volver al estado 3 para que puedan volver a intentar
        foreach ($datos_productos as $prod) {
            if (isset($prod["id"])) {
                $stmt_upd = $conn->prepare("UPDATE ventas SET estado = 3 WHERE id = ?");
                $stmt_upd->bind_param("i", $prod["id"]);
                $stmt_upd->execute();
                $stmt_upd->close();
            }
        }

        // Revertir stock
        $stmt = $conn->prepare("SELECT * FROM productos_en_carrito WHERE venta_id = ?");
        $stmt->bind_param("i", $venta_id);
        $stmt->execute();
        $resultados_carrito = $stmt->get_result();

        if ($resultados_carrito->num_rows > 0) {
            while ($producto_en_carrito = $resultados_carrito->fetch_assoc()) {
                if ($descontar_stock == 1) {
                    $producto_id = intval($producto_en_carrito["producto_id"]);
                    $cantidad = intval($producto_en_carrito["cantidad"]);

                    $stmt_stock = $conn->prepare("SELECT stock, stock_minimo FROM stock WHERE productos_id = ? AND sucursal_id = ?");
                    $stmt_stock->bind_param("ii", $producto_id, $sucursal_id);
                    $stmt_stock->execute();
                    $resultadoquery = $stmt_stock->get_result()->fetch_assoc();
                    $stmt_stock->close();

                    $stockanterior = $resultadoquery["stock"] ?? 0;
                    $stockminimo = $resultadoquery["stock_minimo"] ?? 0;
                    $stockfinal = $stockanterior + $cantidad;

                    $stmt_upd = $conn->prepare("UPDATE stock SET stock = (stock + ?) WHERE productos_id = ? AND sucursal_id = ?");
                    $stmt_upd->bind_param("iii", $cantidad, $producto_id, $sucursal_id);
                    $stmt_upd->execute();
                    $stmt_upd->close();

                    $tipo_op = 'REVERSO VENTA POR ERROR AFIP';
                    $stmt_log = $conn->prepare("INSERT INTO stock_logs (stock_anterior, stock_minimo_anterior, stock, stock_minimo, sucursal_id, usuario, productos_id, updated_at, created_at, tipo_operacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_log->bind_param("iiiisissss", $stockanterior, $stockminimo, $stockfinal, $stockminimo, $sucursal_id, $usuario_cookie, $producto_id, $date, $date, $tipo_op);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            }
        }
        $stmt->close();

        $devolucion["error"] = "Error al generar el comprobante";
        $devolucion["mensaje"] = "AFIP respondio lo siguiente al intentar comunicarnos: " . $e->getMessage();

        // Loguear error de AFIP
        logFacturacion('ERROR', 'Error AFIP al crear comprobante', [
            'mensaje' => $e->getMessage(),
            'venta_id' => $venta_id,
            'total' => $total,
            'ptovta' => $ptovta,
            'comprobante' => $comprobante,
            'data_enviada' => $data
        ]);

        echo json_encode($devolucion);
        exit();
    }
} else {
    $sql = "SELECT * FROM factura WHERE presupuesto = 1 ORDER BY nro_presupuesto DESC LIMIT 1";
    $resultado_perfil = $conn->query($sql);
    if ($resultado_perfil->num_rows > 0) {
        if ($arrPedido = $resultado_perfil->fetch_assoc()) {
            $nro_presupuesto = isset($arrPedido["nro_presupuesto"]) ? $arrPedido["nro_presupuesto"] + 1 : 1;
        } else {
            $nro_presupuesto = 1;
        }
    } else {
        $nro_presupuesto = 1;
    }
    $res["CAEFchVto"] = "";
    $res["voucher_number"] = $nro_presupuesto;
    $res["CAE"] = "";
}

if ($voucher_info === NULL && $presupuesto == 0) {
    logFacturacion('ERROR', 'voucher_info es NULL - Error al generar comprobante', [
        'venta_id' => $venta_id,
        'ptovta' => $ptovta,
        'comprobante' => $comprobante,
        'res' => $res ?? 'no definido'
    ]);
    $devolucion["error"] = "Error al generar el comprobante";
    echo json_encode($devolucion);
    exit();
}

if ($presupuesto == 0) {
    $nombre_factura = "/facturas/" . $ptovta . "_" . $res["CAE"] . "_" . substr("00000" . $res["voucher_number"], -6) . ".pdf";
    $facturanro = "FACTURA NRO.";
} else {
    $nombre_factura = "/presupuesto/" . $ptovta . "_" . $res["CAE"] . "_" . $res["voucher_number"] . ".pdf";
    $facturanro = "PRESUPUESTO NRO.";
}

$solicitar = getAfipValue('solicitar_datos');

switch ($iva) {
    case '1': $texto_iva = "Resp. Inscripto"; break;
    case '2': $texto_iva = "Monotributista"; break;
    case '3': $texto_iva = "Exento"; break;
    case '4': $texto_iva = "Cons. Final"; break;
    default: $texto_iva = "Consumidor Final"; break;
}

switch ($tipo) {
    case '1': $texto_tipo = "Efectivo"; break;
    case '2': $texto_tipo = "Debito"; break;
    case '3': $texto_tipo = "Credito"; break;
    case '4': $texto_tipo = "Transferencia"; break;
    default: $texto_tipo = "Efectivo"; break;
}

switch ($comprobante) {
    case '11': $tipo_comprobante = "C"; break;
    case '1': $tipo_comprobante = "A"; break;
    case '6': $tipo_comprobante = "B"; break;
    default: $tipo_comprobante = "X"; break;
}

$tipo_comprobante = ($presupuesto == 0) ? $tipo_comprobante : "X";
$valido_o_no = ($presupuesto == 0) ? "Comprobante Electronico" : "No valido como factura";

$html_datos_cliente = "<tr>
<td colspan='3' style='border-bottom: 1px solid #000;'><b>Nombre y Apellido</b> " . htmlspecialchars($nombre) . "</td>
</tr><tr>
<td colspan='3' style='border-bottom: 1px solid #000;'><b>Direcci&oacute;n</b> " . htmlspecialchars($direccion) . "</td>
</tr><tr>
<td colspan='3' style='border-bottom: 1px solid #000;'><b>CUIT, CUIL &oacute; CDI </b> " . htmlspecialchars($documento) . "</td>
</tr>
<tr>
<td colspan='3' style='border-bottom: 1px solid #000;'><b>IVA </b> $texto_iva</td>
</tr>
<tr>
<td colspan='3' style='border-bottom: 1px solid #000;'><b>Forma de pago </b> $texto_tipo</td>
</tr>
<tr>
<td colspan='3' style='height:30px;'>&nbsp;</td>
</tr>";

// Insertar factura
$numero_factura = substr("00000" . $res["voucher_number"], -6);
$cae = $res["CAE"];
$cae_fecha = $res["CAEFchVto"];

$stmt = $conn->prepare("INSERT INTO `factura` (`id`, `sucursal_id`, `fecha`, `usuario`, `numero`, `cae`, `fechacae`, `total`, `pdf`, `presupuesto`, `nro_presupuesto`, `nombre`, `direccion`, `documento`, `tipo_documento`, `iva`, `homologacion`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssdsiisssssi", $sucursal_id, $fecha_now, $usuario_cookie, $numero_factura, $cae, $cae_fecha, $total, $nombre_factura, $presupuesto, $nro_presupuesto, $nombre, $direccion, $documento, $tipoDocumento, $iva, $es_homologacion);

if ($stmt->execute()) {
    $factura_id = $conn->insert_id;
    $stmt->close();

    foreach ($array_ids_ventas as $id) {
        $stmt_upd = $conn->prepare("UPDATE ventas SET factura_id = ? WHERE id = ?");
        $stmt_upd->bind_param("ii", $factura_id, $id);
        $stmt_upd->execute();
        $stmt_upd->close();
    }
} else {
    logFacturacion('ERROR', 'Error 502 - Fallo INSERT factura en BD', [
        'venta_id' => $venta_id,
        'sucursal_id' => $sucursal_id,
        'numero_factura' => $numero_factura,
        'total' => $total,
        'mysql_error' => $stmt->error ?? 'no disponible'
    ]);
    echo json_encode(["error" => "Error en la facturacion, codigo 502"]);
    $stmt->close();
    exit();
}

if (strlen($arrSucursal["direccion"]) > 25) {
    $arrDireccion = substr($arrSucursal["direccion"], 0, strpos($arrSucursal["direccion"], " ", 20)) . "<br />" . substr($arrSucursal["direccion"], strpos($arrSucursal["direccion"], " ", 20));
} else {
    $arrDireccion = $arrSucursal["direccion"];
}

$html = utf8_encode("
<style>
h3{
    font-size:1em;
}
</style>
<table>
<tr>
<td colspan='3' style='border: 2px solid #000;text-align:center;'>@@COMPROBANTE@@</td>
</tr>
<tr>
<td style='padding-left:10px;border: 2px solid #000;height: 100px;font-size: 14px;width: 320px;text-align: left;'>
<table>
<tr>
<td>
$logo_html
</td>
<td>
<br />" . htmlspecialchars($arrSucursal["nombre"]) . "
<br />$arrDireccion<br />
" . htmlspecialchars($arrSucursal["codigo_postal"]) . " - " . htmlspecialchars($arrSucursal["provincia"]) . "<br />
<b>$valido_o_no</b>
</td>
</tr>
</table>
</td>
<td style='border: 2px solid #000;height: 100px;font-size: 60px;width: 70px;text-align: center;'>$tipo_comprobante</td>
<td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 280px;text-align: left;'>
<b>@@FACTURANRO@@</b>&nbsp;" . substr("00000" . $ptovta, -6) . "&nbsp;-&nbsp;" . substr("000000" . $res["voucher_number"], -6) . "<br />
<b>CUIT</b>&nbsp;$cuit<br />
<b>Fecha de Emisi&oacute;n</b>&nbsp;" . $fecha[2] . "-" . $fecha[1] . "-" . $fecha[0] . "<br />
<b>Ing.&nbsp;Bruto</b>&nbsp;$ingresos_brutos<br />
<b>IVA</b>&nbsp;$condicion_iva <br />

</td>
</tr>
$html_datos_cliente
<tr>
<td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px'><b>Descripcion</b></td>
<td style='border-bottom: 1px solid #000;'><b>Cantidad</b></td>
<td style='border-bottom: 1px solid #000;'><b>Precio</b></td>
</tr>
");

foreach ($datos_productos as $key => $value) {
    $html .= utf8_encode("<tr>
        <td style='border-bottom: 1px solid #000;word-wrap: break-word;width:230px;text-align:justify'><i>" . htmlspecialchars($value["nombre_producto"]) . "</i></td>
        <td style='border-bottom: 1px solid #000;'>" . intval($value["cantidad"]) . "</td>
        <td style='border-bottom: 1px solid #000;'>" . number_format(floatval($value["precio"]), 2, ",", ".") . "</td>
        </tr>
        ");
}

$html .= utf8_encode("<tr>
    <td></td>
    <td style='border-bottom: 1px solid #000;'>Total</td>
    <td style='border-bottom: 1px solid #000;'>" . number_format($total, 2, ",", ".") . "</td>
    </tr>");

if ($comprobante == 1) {
    $html .= utf8_encode("
        <tr>
        <td></td>
        <td style='border-bottom: 1px solid #000;'>Importe Neto</td>
        <td style='border-bottom: 1px solid #000;'>" . number_format($ImpNeto, 2, ",", ".") . "</td>
        </tr>
        <tr>
        <td></td>
        <td style='border-bottom: 1px solid #000;'>IVA (21%)</td>
        <td style='border-bottom: 1px solid #000;'>" . number_format($impuestoIVA, 2, ",", ".") . "</td>
        </tr>");
}

$html .= utf8_encode("</table>");

if ($res["CAE"] != "") {
    $html .= utf8_encode("<p style='text-align:right'><b>CAE Nro.:</b> " . $res["CAE"] . "<br />
        <b>Fecha de Vto. CAE: </b>" . $res["CAEFchVto"] . "<br /></p>");
}

$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
$html2pdf->setDefaultFont('Arial');
$html = str_replace("@@FACTURANRO@@", $facturanro, $html);

$html2pdf->writeHTML("<page>" . str_replace("@@COMPROBANTE@@", "ORIGINAL", $html) . "<br><br><hr style='border-style: dotted;' /><br><br></page><page>" . str_replace("@@COMPROBANTE@@", "DUPLICADO", $html) . "<br><br><hr style='border-style: dotted;' /><br><br></page>");

$html2pdf->Output(dirname(__FILE__) . $nombre_factura, "F");
$devolucion["factura"] = $nombre_factura;

// Eliminar carrito
$stmt = $conn->prepare("DELETE FROM productos_en_carrito WHERE venta_id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$stmt->close();

// Log de exito
logFacturacion('SUCCESS', 'Facturacion completada exitosamente', [
    'venta_id' => $venta_id,
    'factura_id' => $factura_id ?? 'no definido',
    'numero_factura' => $numero_factura,
    'total' => $total,
    'presupuesto' => $presupuesto,
    'archivo' => $nombre_factura
]);

echo json_encode($devolucion);
exit();
?>
