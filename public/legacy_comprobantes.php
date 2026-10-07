<?php
/**
 * Helpers compartidos por los procesadores legacy de comprobantes
 * (nota_de_credito.php, nota_de_debito.php; facturar.php tiene su propia
 * versión inline). Sin conexiones a la base: testeables con PHPUnit.
 *
 * Requiere que el autoload de Html2Pdf ya esté cargado por quien lo incluye.
 */

if (! function_exists('legacy_ptovta_sucursal')) {
    /**
     * Punto de venta AFIP de una sucursal (fila de `sucursales`).
     * Devuelve null si no está configurado: en ese caso NO hay que emitir,
     * porque el comprobante saldría por un punto de venta ajeno (incidente
     * 2026-09-23: 20 NC emitidas por el punto global en vez del de la sucursal).
     */
    function legacy_ptovta_sucursal($sucursal)
    {
        if (! is_array($sucursal) || ! isset($sucursal['pto_vta'])) {
            return null;
        }
        $pto = $sucursal['pto_vta'];
        if (! is_numeric($pto) || intval($pto) <= 0) {
            return null;
        }
        return intval($pto);
    }
}

if (! function_exists('legacy_ptovta_de_pdf')) {
    /**
     * Punto de venta con el que se emitió un comprobante: está en el nombre
     * de su PDF (`/facturas/{pto}_{cae}_{nro}.pdf`). null si no se puede leer.
     */
    function legacy_ptovta_de_pdf($pdf)
    {
        if (! is_string($pdf) || ! preg_match('#/(\d+)_[^/]*\.pdf\z#', $pdf, $m)) {
            return null;
        }
        return intval($m[1]) > 0 ? intval($m[1]) : null;
    }
}

if (! function_exists('legacy_ptovta_comprobante')) {
    /**
     * Punto de venta a usar para asociar/anular un comprobante existente:
     * el real (del PDF) y, si no se puede leer, el de su sucursal.
     */
    function legacy_ptovta_comprobante($pdf, $sucursal)
    {
        $pto = legacy_ptovta_de_pdf($pdf);
        return $pto !== null ? $pto : legacy_ptovta_sucursal($sucursal);
    }
}

if (! function_exists('legacy_prevuelo_pdf')) {
    /**
     * Verifica ANTES de pedir el CAE todo lo que puede hacer fallar el PDF:
     * carpeta destino escribible y render del logo con Html2Pdf en memoria.
     * Devuelve null si está todo bien, o el mensaje de error.
     */
    function legacy_prevuelo_pdf($carpeta, $logo)
    {
        if (! is_dir($carpeta) || ! is_writable($carpeta)) {
            return 'La carpeta de comprobantes no es escribible en el servidor.';
        }
        try {
            $pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'pt', true, 'UTF-8');
            $pdf->setDefaultFont('Arial');
            $pdf->writeHTML("<page><img = src='".$logo."' style='height:80px;width:120px;'/> prueba</page>");
            $pdf->Output('prevuelo.pdf', 'S');
            unset($pdf);
        } catch (\Throwable $e) {
            return 'No se puede generar el PDF del comprobante ('.$e->getMessage().').';
        }
        return null;
    }
}

if (! function_exists('legacy_html_latin1')) {
    /**
     * Escapa texto para el HTML del PDF legacy. Ese HTML se arma en latin1
     * (datos de la base) y recién después pasa por utf8_encode: hay que
     * escapar como ISO-8859-1, o htmlspecialchars() devuelve "" ante una
     * "ñ" latin1 y el dato desaparece del comprobante.
     */
    function legacy_html_latin1($texto)
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'ISO-8859-1');
    }
}

if (! function_exists('legacy_texto_form_latin1')) {
    /** Lo que llega del formulario es UTF-8; se lleva a latin1 como el resto del HTML. */
    function legacy_texto_form_latin1($texto)
    {
        $texto = trim((string) $texto);
        if ($texto !== '' && mb_check_encoding($texto, 'UTF-8')) {
            return utf8_decode($texto);
        }
        return $texto;
    }
}

/*
 * Reserva atómica de un comprobante a asociar (NC sobre una factura, ND
 * sobre una NC). La tabla destino tiene una columna UNIQUE con el id del
 * comprobante asociado: se inserta una fila placeholder (cae='') ANTES de
 * pedir el CAE; un segundo intento falla por 1062 sin tocar AFIP. Si AFIP
 * rechaza, se libera; si autoriza, se confirma con número/CAE/PDF.
 */
if (! function_exists('legacy_reserva_limpiar')) {
    /**
     * Borra placeholders colgados (proceso muerto entre la reserva y el CAE).
     * La ventana (30 min) supera cualquier timeout de AFIP: una reserva viva
     * nunca debe caer acá. Solo filas con el comprobante asociado seteado.
     */
    function legacy_reserva_limpiar(mysqli $conn, $tabla, $columna, $minutos = 30)
    {
        $limite = date('Y-m-d H:i:s', time() - $minutos * 60);
        $stmt = $conn->prepare("DELETE FROM `$tabla` WHERE `$columna` IS NOT NULL AND (cae IS NULL OR cae = '') AND fecha < ?");
        if (! $stmt) {
            return 0;
        }
        $stmt->bind_param('s', $limite);
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
}

if (! function_exists('legacy_reserva_insertar')) {
    /** INSERT genérico con prepared statement. Devuelve ['id'=>N] o ['errno'=>int,'error'=>string]. */
    function legacy_reserva_insertar(mysqli $conn, $tabla, array $fila)
    {
        $columnas = array_keys($fila);
        foreach ($columnas as $c) {
            if (! preg_match('/^[a-z_]+$/', $c)) {
                return ['errno' => -1, 'error' => 'columna inválida '.$c];
            }
        }
        $sql = "INSERT INTO `$tabla` (`".implode('`, `', $columnas)."`) VALUES (".implode(', ', array_fill(0, count($fila), '?')).")";
        $stmt = $conn->prepare($sql);
        if (! $stmt) {
            return ['errno' => $conn->errno, 'error' => $conn->error];
        }
        $valores = array_values($fila);
        $refs = [];
        foreach ($valores as $i => $v) {
            $valores[$i] = ($v === '' || $v === null) && in_array($columnas[$i], ['tipo_documento', 'iva', 'numero'], true) ? null : $v;
            $refs[$i] = &$valores[$i];
        }
        array_unshift($refs, str_repeat('s', count($valores)));
        call_user_func_array([$stmt, 'bind_param'], $refs);
        if (! $stmt->execute()) {
            $r = ['errno' => $stmt->errno, 'error' => $stmt->error];
            $stmt->close();
            return $r;
        }
        $id = $stmt->insert_id;
        $stmt->close();
        return ['id' => $id];
    }
}

if (! function_exists('legacy_reserva_tomar')) {
    /**
     * Reserva el comprobante asociado. Devuelve:
     *  ['id' => N]                       reserva tomada
     *  ['duplicada' => fila|null]        ya existe (fila previa con numero/cae, o null si no se pudo leer)
     *  ['errno' => int, 'error' => str]  otro error (p. ej. 1054: falta la migración)
     */
    function legacy_reserva_tomar(mysqli $conn, $tabla, $columna, $valor, array $fila)
    {
        $fila = [$columna => $valor, 'numero' => 0, 'cae' => '', 'fechacae' => '', 'pdf' => ''] + $fila;
        $r = legacy_reserva_insertar($conn, $tabla, $fila);
        if (isset($r['id'])) {
            return $r;
        }
        if ($r['errno'] == 1062) {
            $previa = null;
            $stmt = $conn->prepare("SELECT id, numero, cae FROM `$tabla` WHERE `$columna` = ?");
            if ($stmt) {
                $stmt->bind_param('s', $valor);
                $stmt->execute();
                $res = $stmt->get_result();
                $previa = $res ? $res->fetch_assoc() : null;
                $stmt->close();
            }
            return ['duplicada' => $previa];
        }
        return $r;
    }
}

if (! function_exists('legacy_reserva_liberar')) {
    /** Borra el placeholder (solo si sigue sin CAE). Devuelve filas borradas. */
    function legacy_reserva_liberar(mysqli $conn, $tabla, $id)
    {
        $stmt = $conn->prepare("DELETE FROM `$tabla` WHERE id = ? AND (cae IS NULL OR cae = '')");
        if (! $stmt) {
            return 0;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
}

if (! function_exists('legacy_reserva_confirmar')) {
    /**
     * Graba número/CAE/vencimiento/PDF sobre la reserva. Si la reserva ya no
     * está (la limpió otro proceso), re-inserta la fila completa; si el UNIQUE
     * lo impide, la inserta sin el comprobante asociado para no perder el CAE.
     * Devuelve true si el comprobante quedó grabado de alguna forma.
     */
    function legacy_reserva_confirmar(mysqli $conn, $tabla, $id, array $cae, $columna, $valor, array $fila)
    {
        $stmt = $conn->prepare("UPDATE `$tabla` SET numero = ?, cae = ?, fechacae = ?, pdf = ? WHERE id = ? AND (cae IS NULL OR cae = '')");
        if ($stmt) {
            $stmt->bind_param('ssssi', $cae['numero'], $cae['cae'], $cae['fechacae'], $cae['pdf'], $id);
            $ok = $stmt->execute();
            $n = $stmt->affected_rows;
            $stmt->close();
            if ($ok && $n === 1) {
                return true;
            }
        }
        error_log("legacy_reserva_confirmar: la reserva $tabla#$id no estaba; se re-inserta el comprobante ".$cae['numero']." (CAE ".$cae['cae'].")");
        $completa = [$columna => $valor] + $cae + $fila;
        $r = legacy_reserva_insertar($conn, $tabla, $completa);
        if (isset($r['id'])) {
            return true;
        }
        if ($r['errno'] == 1062) {
            error_log("legacy_reserva_confirmar: $columna=$valor ya tiene otro comprobante; se graba ".$cae['numero']." sin asociar. REVISAR.");
            $completa[$columna] = null;
            $r = legacy_reserva_insertar($conn, $tabla, $completa);
            return isset($r['id']);
        }
        error_log("legacy_reserva_confirmar: NO se pudo grabar el comprobante ".$cae['numero']." (CAE ".$cae['cae']."): ".$r['error']);
        return false;
    }
}

if (! function_exists('legacy_afip_emitir')) {
    /**
     * Pide el CAE de UN comprobante y, si la llamada falla, averigua en AFIP
     * si igual quedó autorizado antes de darlo por no emitido (un timeout
     * después de que AFIP procesó = comprobante real sin registrar).
     *
     * $afip es la instancia del SDK (afip_instance()). $data sin CbteDesde/Hasta.
     * Devuelve:
     *  ['estado'=>'emitida',  'res'=>['CAE','CAEFchVto'(Y-m-d),'voucher_number']]
     *  ['estado'=>'rechazada','mensaje'=>...]   seguro que NO se emitió
     *  ['estado'=>'desconocida','mensaje'=>...] no se pudo confirmar: NO liberar
     */
    function legacy_afip_emitir($afip, array $data)
    {
        $wsfe = $afip->ElectronicBilling;
        try {
            $ultimo = (int) $wsfe->GetLastVoucher($data['PtoVta'], $data['CbteTipo']);
        } catch (\Throwable $e) {
            return ['estado' => 'rechazada', 'mensaje' => 'AFIP no respondió al consultar el último comprobante: '.$e->getMessage()];
        }
        $numero = $ultimo + 1;
        $data['CbteDesde'] = $numero;
        $data['CbteHasta'] = $numero;
        try {
            $res = $wsfe->CreateVoucher($data);
            if (! empty($res['CAE'])) {
                return ['estado' => 'emitida', 'res' => ['CAE' => $res['CAE'], 'CAEFchVto' => $res['CAEFchVto'], 'voucher_number' => $numero]];
            }
            $error = 'AFIP no devolvió CAE.';
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        // Falló la solicitud: ¿quedó autorizado igual?
        try {
            $ahora = (int) $wsfe->GetLastVoucher($data['PtoVta'], $data['CbteTipo']);
            if ($ahora < $numero) {
                return ['estado' => 'rechazada', 'mensaje' => 'AFIP respondio lo siguiente al intentar comunicarnos: '.$error];
            }
            $info = $wsfe->GetVoucherInfo($numero, $data['PtoVta'], $data['CbteTipo']);
            if ($info && ! empty($info->CodAutorizacion)) {
                $vto = (string) $info->FchVto;
                if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $vto, $m)) {
                    $vto = $m[1].'-'.$m[2].'-'.$m[3];
                }
                error_log("legacy_afip_emitir: la solicitud fallo ($error) pero AFIP autorizo el comprobante $numero (CAE ".$info->CodAutorizacion."); se registra.");
                return ['estado' => 'emitida', 'res' => ['CAE' => $info->CodAutorizacion, 'CAEFchVto' => $vto, 'voucher_number' => $numero]];
            }
            return ['estado' => 'rechazada', 'mensaje' => 'AFIP respondio lo siguiente al intentar comunicarnos: '.$error];
        } catch (\Throwable $e2) {
            return ['estado' => 'desconocida', 'mensaje' => 'No se pudo confirmar con AFIP si el comprobante fue autorizado ('.$error.'). NO reintente: verifique en el reporte en unos minutos o avise al administrador.'];
        }
    }
}
