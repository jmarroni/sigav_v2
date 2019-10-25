<?php 
require_once ("../conection.php");
$sql = "SELECT * FROM mail_configuracion limit 1";
$resultado = $conn->query($sql) or die($conn->error);

$datos = array("usuario" => "", "clave" => "","imap" => "","subject" =>"");
if ($resultado->num_rows > 0) {
    // output data of each row
    while($row = $resultado->fetch_assoc()) { 
        $datos = $row;
    }
}

$hostname = $datos["imap"];
$username = $datos["usuario"];
$password = $datos["clave"];
 
 
$inbox = imap_open($hostname,$username,$password) or die('Ha fallado la conexión: ' . imap_last_error());
$emails = imap_search($inbox,'SUBJECT "'.$datos["subject"].'" SINCE "'.date('d F Y',time()-(60*60*24)).'"');



if($emails) {
   
  $salida = '';
   
  foreach($emails as $email_number) {
          
    $overview = imap_fetch_overview($inbox,$email_number,0);  
    $message = imap_fetchbody($inbox,$email_number,2);
    $structure = imap_fetchstructure($inbox,$email_number);
    $attachments = array();
       if(isset($structure->parts) && count($structure->parts)) {
         for($i = 0; $i < count($structure->parts); $i++) {
           $attachments[$i] = array(
              'is_attachment' => false,
              'filename' => '',
              'name' => '',
              'attachment' => '');

           if($structure->parts[$i]->ifdparameters) {
             foreach($structure->parts[$i]->dparameters as $object) {
               if(strtolower($object->attribute) == 'filename') {
                 $attachments[$i]['is_attachment'] = true;
                 $attachments[$i]['filename'] = $object->value;
               }
             }
           }

           if($structure->parts[$i]->ifparameters) {
             foreach($structure->parts[$i]->parameters as $object) {
               if(strtolower($object->attribute) == 'name') {
                 $attachments[$i]['is_attachment'] = true;
                 $attachments[$i]['name'] = $object->value;
               }
             }
           }

           if($attachments[$i]['is_attachment']) {
             $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
             if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
               $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
             }
             elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
               $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
             }
           }             
         } // for($i = 0; $i < count($structure->parts); $i++)
       } // if(isset($structure->parts) && count($structure->parts))

    $directorio = dirname(dirname(__FILE__));
    if(count($attachments)!=0){
        foreach($attachments as $at){
            if($at['is_attachment']==1){
                $filename_attach = $directorio ."/attach/". date("Ymd")."-".$at['filename'];
                file_put_contents($filename_attach, $at['attachment']);
            }
        }
        $file = fopen($filename_attach, "r") or exit("Unable to open file!");
        //Output a line of the file until the end is reached
        while(!feof($file))
        {
            $parametros = explode(";",fgets($file));
            print_r($parametros);
        }
    fclose($file);
    }
    
    exit();

    
  }
 // print_r($overview);
 
} 
 
imap_close($inbox);
  
?>