<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}
require(__DIR__.'/librarys/fpdf.php');
require __DIR__.'/vendor/autoload.php';
require_once ("conection.php");
require_once __DIR__.'/librarys/NumberToLetterConverter.class.php';
use Spipu\Html2Pdf\Html2Pdf;

//ob_start(); 
if (isset($_GET["etiquetas"]) && intval($_GET["etiquetas"]) != ""){?>
    <button class="btn btn-sm btn-minw btn-rounded btn-primary" onclick="window.print();" style="width:98%;height:30px;margin-top:25px;" type="button">
        <i class="fa fa-check push-5-r"></i>Imprimir etiqueta
    </button>
    <?php
    $arrEtiquetas = explode('-',$_GET["etiquetas"]);
    foreach ($arrEtiquetas as $key => $value) {
        $etiqueta = explode('@',$value);
        $cantidad = ($etiqueta[1] != "")?intval($etiqueta[1]):13;
        $sql = "SELECT * FROM cajas WHERE id = '".$etiqueta[0]."'";
        $resultado = $conn->query($sql);
        $i = 0;
        if ($resultado->num_rows > 0) { 
            while($row = $resultado->fetch_assoc()) { ?>
                <table>
                    <?php 
                    echo "<tr>";
                    for ($i=0; $i < $cantidad; $i++) { 
                        if (($i % 3) == 0)echo "</tr><tr>";
                        ?>
                        <td style="border: 1px solid #CCC;width:320px;">
                            <div style="margin-top:5px;">
                                <img alt="testing" src="/librarys/barcode.php?codetype=Code39&text=<?php echo $row["codigo_barras"];  ?>&print=true&size=40" />
                            </div>
                        </td>
                    <?php } ?>
                    </tr>
            </table>

        <?php 
            } 
        } 
    }?>
    
<?php }else{?>
    <h1 style="text-align: center;">Por favor seleccione un producto</h1>
<?php 
} 
// 	$html = utf8_encode(ob_get_clean());
//	$html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
//	$html2pdf->setDefaultFont('Arial');
//	$html2pdf->writeHTML($html);
//	$html2pdf->output();
//	exit(); 
?>