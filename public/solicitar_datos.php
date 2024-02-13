<?php
if (intval($_POST["solicitar"]) === 1 || intval($_POST["solicitar"]) === 0 ){
    if (file_exists(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/solicitar_datos")){
    file_put_contents(dirname(__FILE__)."/vendor/afipsdk/afip.php/src/Afip_res/solicitar_datos",$_POST["solicitar"]);
    };
}
?>