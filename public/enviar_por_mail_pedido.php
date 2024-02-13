<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
echo "conecion";
// Load Composer's autoloader
require 'vendor/autoload.php';
require_once ("conection.php");


    $sql = "SELECT * FROM perfil limit 1";
    // echo $sql;exit();
    $resultado = $conn->query($sql);
    if ($resultado->num_rows > 0) {
        // output data of each row
        while($row = $resultado->fetch_assoc()) { 
            $perfil = $row;
        }
    }else{ exit();}

//sender
$file = $_GET["factura"];
//email subject
$subject = 'Te envia una factura adjunta '.$perfil["nombre"]; 



//email body content
//email body content
$result = get_web_page("http://".$_SERVER["HTTP_HOST"]."/mail/template.php");
$htmlContent = $result['content'];
$htmlContent = str_replace("@@LOGO@@", "http://".$_SERVER["HTTP_HOST"].$perfil["logo"], $htmlContent);
$htmlContent = str_replace("@@TITULO@@", "Ingresamos tu pedido.", $htmlContent);
$htmlContent = str_replace("@@CONTENIDO@@", 'Tu Pedido se ingreso el d&iacutea '.$_POST[""], $htmlContent);

echo "envio de mail";exit();
//multipart boundary 
$message = $htmlContent; 


// Instantiation and passing `true` enables exceptions
$mail = new PHPMailer(true);
try {
    $header = "From: facturacion@mercado-artesanal.com.ar\nReply-To:facturacion@mercado-artesanal.com.ar\n";
    $header .= "Mime-Version: 1.0\n";
    $header .= "Content-Type: text/plain";
    if(mail("jmarroni@gmail.com", "$subject", "$contenido" ,"$header")){
    echo "Mail Enviado.";
    }else{ echo "Error en el envio";}
        //Recipients
        $mail->setFrom('facturacion@mercado-artesanal.com.ar', $perfil["nombre"]);
        $mail->addAddress($_GET["mail"], 'Cliente');     // Add a recipient
        $mail->addReplyTo('facturacion@mercado-artisanal.com.ar', 'Mercado Artesanal');
        $mail->IsSMTP();
        $mail->Host = "c2101314.ferozo.com";
        $mail->SMTPAuth = true;
        $mail->Username = 'facturacion@mercado-artesanal.com.ar';
        $mail->Password = 'Afoo2te1';
        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody =  'Te enviamos la factura por tu compra.\n\r Desde el siguiente link: http://'.$_SERVER["HTTP_HOST"].$file.' podes visualizar (o descargar) la factura enviada por '.$perfil["nombre"].'\n\r Saludos y gracias por tu compra. \n\r'.$perfil["nombre"];
        $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}



/**
     * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
     * array containing the HTTP server response header fields and content.
     */
    function get_web_page( $url )
    {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }