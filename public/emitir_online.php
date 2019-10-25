<?php
if (intval($_POST["emitir_online"]) === 1 || intval($_POST["emitir_online"]) === 0 ){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir")){
    file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/emitir",$_POST["emitir_online"]);
    };
}
?>