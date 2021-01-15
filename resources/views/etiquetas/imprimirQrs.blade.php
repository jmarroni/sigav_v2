<?php
use Spipu\Html2Pdf\Html2Pdf;
?>

@if ($etiquetasimprimir != "")
    <button class="btn btn-sm btn-minw btn-rounded btn-primary" onclick="window.print();" style="width:98%;height:30px;margin-top:25px;" type="button">
        <i class="fa fa-check push-5-r"></i>Imprimir Qr
    </button>
    <table>
     <?php $columnas=0; ?>
     @foreach ($arrEtiquetas as $etiqueta)
        <?php 
        $numetiqueta = explode('@',$etiqueta);
        ?>
        @if (count($productos)>0)
               
            @foreach($productos as $producto) 
               
                    @if ($producto->id==$numetiqueta[0] && $producto->sitio_web!="") 
                     @if ($columnas == 0 || $columnas % 5 == 0) <tr> @endif 
                     <?php $columnas=$columnas+1; ?>                    
                            <td style="border: 1px solid #CCC;width:170px;">  
                                <div style="margin-top:5px;">
                                     {!!QrCode::size(200)->generate($producto->sitio_web) !!}
                                </div>
                            </td>    
                       @if (($columnas % 5) == 0) </tr> @endif                          
                     @endif
               
            @endforeach
              

        @else
            <h1 style="text-align: center;">No hay Qr para este producto</h1>
        @endif 
    
     @endforeach
   @if (($columnas % 5) != 0) </tr>@endif
            </table>
@else
    <h1 style="text-align: center;">Por favor seleccione un producto</h1>
@endif 

