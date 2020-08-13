<?php
define('SEMILLA','$%Reset20122017AnnaLuca#^');
define('PRODUCTOS_LIBRE','SI');

$conn = mysqli_connect("127.0.0.1", "jmarroni", "Afoo2te1", "mercado-artesanal");
date_default_timezone_set('America/Argentina/Buenos_Aires');
if (!$conn) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging ererrorrno: " . mysqli_connect_errno() . PHP_EOL;
    exit;
}

function setRol($rol_id){
    return sha1(SEMILLA.$rol_id.SEMILLA);
}

function setSucursal($sucursal_id){
    return sha1(SEMILLA.$sucursal_id.SEMILLA);
}

function getSucursal($sucursal_sha){
        for ($i=0; $i < 99; $i++) { 
            if (sha1(SEMILLA."$i".SEMILLA) == $sucursal_sha) return $i;
        }
        
        exit();
}

function getRol(){
    for ($i=0; $i < 10; $i++) { 
        if (sha1(SEMILLA."$i".SEMILLA) == $_COOKIE["rol"]) return $i;
    }
    
    exit();
}


function redim($ruta1,$ruta2,$ancho,$alto) 
{ 
    # se obtene la dimension y tipo de imagen 
    $datos=getimagesize ($ruta1); 
        
    $ancho_orig = $datos[0]; # Anchura de la imagen original 
    $alto_orig = $datos[1];    # Altura de la imagen original 
    $tipo = $datos[2]; 
        
    if ($tipo==1){ # GIF 
        if (function_exists("imagecreatefromgif")) 
            $img = imagecreatefromgif($ruta1); 
        else 
            return false; 
    } 
    else if ($tipo==2){ # JPG 
        if (function_exists("imagecreatefromjpeg")) 
            $img = imagecreatefromjpeg($ruta1); 
        else 
            return false; 
    } 
    else if ($tipo==3){ # PNG 
        if (function_exists("imagecreatefrompng")) 
            $img = imagecreatefrompng($ruta1); 
        else 
            return false; 
    } 
        
    # Se calculan las nuevas dimensiones de la imagen 
    if ($ancho_orig>$alto_orig) 
        { 
        $ancho_dest=$ancho; 
        $alto_dest=($ancho_dest/$ancho_orig)*$alto_orig; 
        } 
    else 
        {  
        $alto_dest=$alto; 
        $ancho_dest=($alto_dest/$alto_orig)*$ancho_orig; 
        } 

    // imagecreatetruecolor, solo estan en G.D. 2.0.1 con PHP 4.0.6+ 
    $img2=@imagecreatetruecolor($ancho_dest,$alto_dest) or $img2=imagecreate($ancho_dest,$alto_dest); 

    // Redimensionar 
    // imagecopyresampled, solo estan en G.D. 2.0.1 con PHP 4.0.6+ 
    @imagecopyresampled($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig) or imagecopyresized($img2,$img,0,0,0,0,$ancho_dest,$alto_dest,$ancho_orig,$alto_orig); 

    // Crear fichero nuevo, según extensión. 
    if ($tipo==1) // GIF 
        if (function_exists("imagegif")) 
            imagegif($img2, $ruta2); 
        else 
            return false; 

    if ($tipo==2) // JPG 
        if (function_exists("imagejpeg")) 
            imagejpeg($img2, $ruta2);  
        else 
            return false; 

    if ($tipo==3)  // PNG 
        if (function_exists("imagepng")) 
            imagepng($img2, $ruta2); 
        else 
            return false; 
        
    return true; 
} 
?>
