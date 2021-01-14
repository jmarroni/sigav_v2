<?php
use Spipu\Html2Pdf\Html2Pdf;
?>

@if (intval($etiquetasimprimir) != "")
    <button class="btn btn-sm btn-minw btn-rounded btn-primary" onclick="window.print();" style="width:100%;height:30px;margin-top:25px;" type="button">
        <i class="fa fa-check push-5-r"></i>Imprimir etiqueta
    </button>
     @foreach ($arrEtiquetas as $etiqueta)
        <?php 
        $numetiqueta = explode('@',$etiqueta);
        $cantidad = ($numetiqueta[1] != "")?intval($numetiqueta[1]):13;
        $i = 0
        ?>
        @if (count($productos)>0)
               
            @foreach($productos as $producto) 
               
                    @if ($producto->id==$numetiqueta[0])
                    <table>    
                     <tr>                    
                          @for ($i=0; $i < $cantidad; $i++)  
                                @if (($i % 3) == 0) </tr><tr>  @endif
                            <td style="border: 1px solid #CCC;width:320px;">
                                <div style="margin-top:5px;">
                                    <img alt="testing" src="/librarys/barcode.php?codetype=Code39&text=<?php echo $producto->codigo_barras;?>&print=true&size=40" />
                                </div>
                            </td> 

                            @endfor 
                         
                           </tr>                           
                     @endif
               
            @endforeach
            </table>

        @else
            <h1 style="text-align: center;">No hay etiquetas para este producto</h1>
        @endif 
    
     @endforeach
  
@else
    <h1 style="text-align: center;">Por favor seleccione un producto</h1>
@endif 

