<?php
require_once __DIR__.'/afip_bridge.php';

if (! isset($_COOKIE['kiosco'])) {
    exit();
}

if (isset($_POST['emitir_online']) && (intval($_POST['emitir_online']) === 1 || intval($_POST['emitir_online']) === 0)) {
    afip_set_valor('emitir', intval($_POST['emitir_online']));
}
