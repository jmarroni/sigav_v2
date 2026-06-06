<?php
require_once __DIR__.'/afip_bridge.php';

if (! isset($_COOKIE['kiosco'])) {
    exit();
}

if (isset($_POST['solicitar']) && (intval($_POST['solicitar']) === 1 || intval($_POST['solicitar']) === 0)) {
    afip_set_valor('solicitar_datos', intval($_POST['solicitar']));
}
