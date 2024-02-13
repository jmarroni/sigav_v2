<html>
<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
if (getRol() < 4) {
    exit();
}
//Productos vendidos hoy por el usuario
$sql = "SELECT * FROM `productos`";
$resultado = $conn->query($sql);
$total = 0;
$cantidad_de_ventas_usuario = 0;
$caja = 540;
if ($resultado->num_rows > 0) {
    $total = $resultado->num_rows; 
}

$menu["ventas"] = "";
$menu["usuario"] = "active";
$menu["reportes"] = "";
require ('header.php'); 
?>
<style>
    .ui-autocomplete-loading {
        background: white url("assets/img/favicons/ui-anim_basic_16x16.gif") right center no-repeat;
    }
</style>
<!-- Page Content -->
<div class="content content-boxed">
    <!-- Section -->
    <div id="formulario" class="bg-image img-rounded overflow-hidden push" style="background-image: url('assets/img/photos/photo25@2x.jpg');">
        <div class="bg-black-op" id="bg-black-op">
            <div class="content">
                <div class="block block-transparent block-themed text-center">
                    <div class="block-content">
                        <h1 class="h1 font-w700 text-white animated fadeInDown push-5">Area Mails</h1>
                        <h2 class="h4 font-w400 text-white-op animated fadeInUp">Alta/Bajas/Modificaciones de mail</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Section -->

    <?php if (isset($_GET["mensaje"])){ ?>
        <div class="block block-rounded" id="add_success" style="background-color: #46c37b !important;color:white;">
            <div class="block-header">
                <div class="col-xs-12 bg-success" id="nombre-devuelto"><?php echo base64_decode($_GET["mensaje"]); ?></div>
            </div>
        </div>
    <?php } ?>
    <?php
                    $sql = "SELECT * FROM mail_configuracion limit 1";
                    $resultado = $conn->query($sql) or die($conn->error);
                    $datos = array("usuario" => "", "clave" => "","imap" => "","subject" =>"");
                    if ($resultado->num_rows > 0) {
                        // output data of each row
                        while($row = $resultado->fetch_assoc()) { 
                            $datos = $row;
                        }
                    }
                            ?>
    <div class="block block-rounded">
        <div class="block-content" id="block-content">
            <form class="form-horizontal" action="/mail_receptor_post.php" method="post" >
                <input type="hidden" value="" name="id" id="id" />
                <div class="form-group">
                    <div class="col-xs-4 col-xs-offset-2">
                        <label for="bd-qsettings-name">Mail</label>
                        <input type="text" class="form-control" name="usuario" id="usuario" value="<?php echo $datos["usuario"]; ?>" placeholder="mail@mail.com" />
                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Clave</label>
                        <input type="text" data-toggle="tooltip" data-placement="bottom" title="" class="form-control" name="clave" id="clave" value="<?php echo $datos["clave"]; ?>" placeholder="Aaflodot" />
                    </div>
                    
                </div><div class="form-group">
                    <div class="col-xs-4 col-xs-offset-2">
                        <label for="bd-qsettings-name">IMAP</label>
                        <input type="text" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Cadena de caracteres de imap paara acceso" class="form-control" name="imap" id="imap" value="<?php echo $datos["imap"]; ?>" placeholder="{c0380494.ferozo.com:993/ssl}INBOX" />
                    </div>
                    <div class="col-xs-4">
                        <label for="bd-qsettings-name">Asunto</label>
                        <input type="text" class="form-control" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="coloque el asunto del mail" name="asunto" id="asunto" value="<?php echo $datos["subject"]; ?>" placeholder="Asunto del mail a buscar" />
                    </div>
                    <div class="col-xs-8 col-xs-offset-2">
                        <button class="btn btn-sm btn-minw btn-rounded btn-primary" style="width:98%;margin-top:25px;" type="submit">
                            <i class="fa fa-check push-5-r"></i>Guadar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row text-uppercase">
                    <div class="col-sm-12">
                        <table id="tabla_compras">
                            <thead>
                                <tr>
                                    <td>De</td>
                                    <td>D&iacute;a</td>
                                    <td>Tama&ntilde;o</td>
                                </tr>
                            </thead>
                            <tbody>
    <?php
        $hostname = $datos["imap"];
        $username = $datos["usuario"];
        $password = $datos["clave"];
        
        if ($datos["imap"] != ""){
            $inbox = imap_open($hostname,$username,$password) or die('Ha fallado la conexión: ' . imap_last_error());
            $emails = imap_search($inbox,'SUBJECT "'.$datos["subject"].'"');
            if($emails) {
                $i = 0;
                $salida = '';
                foreach($emails as $email_number) {        
                    $overview = imap_fetch_overview($inbox,$email_number,0); ?>
                    <tr style="<?php echo (($i % 2)== 0)?"background-color: #fff !important;":"background-color: #f9f9f9 !important;"; ?>">
                        <td><?php echo $overview[0]->from; ?></td>
                        <td><?php 
                        $dia = new DateTime($overview[0]->date);
                        echo $dia->format("Y-m-d H:i:s"); ?></td>
                        <td><?php echo $overview[0]->size; ?></td>
                    </tr>
                <?php $i++;}
            }       
            imap_close($inbox);
        }
        ?>
        </tbody>
                        </table>
                    </div>
                </div>
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" />
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js"></script>
<script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/vfs_fonts.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>
<script>
function modificar(identificador,titulo,habilitado,periodo,costo){
    $("#nombre").val(titulo);
    $("#id").val(identificador);
    if (habilitado == 1){$("#habilitado").prop("checked",true);}else{$("#habilitado").prop("checked",false);}
    $("#periodo").val(periodo);
    $("#costo").val(costo);
    
    document.location.href ="#formulario";
}

    $(document).ready(function(){
        $('#tabla_compras').DataTable({
             "language": {
                "url": "/assets/language/Spanish.json"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            "order": [[ 1, "desc" ]]
        });
    });

</script>
<?php require ("footer.php"); ?>