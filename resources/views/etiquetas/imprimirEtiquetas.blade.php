<?php
use Spipu\Html2Pdf\Html2Pdf;
?>

@if ($etiquetasimprimir != "")
<button class="btn btn-sm btn-minw btn-rounded btn-primary" onclick="window.print();" style="width:100%;height:30px;margin-top:25px;" type="button">
    <i class="fa fa-check push-5-r"></i>Imprimir etiqueta
</button>
<table>
    <?php $columnas=0; ?>
    @foreach ($arrEtiquetas as $etiqueta)
    <?php 
    $numetiqueta = explode('@',$etiqueta);
    $cantidad = ($numetiqueta[1] != "")?intval($numetiqueta[1]):13;
    $i = 0
    ?>
    @if (count($productos)>0)
    @foreach($productos as $producto)  
    @if ($producto->id==$numetiqueta[0])                    
    @for ($i=0; $i < $cantidad; $i++)  
    @if ($columnas == 0 || $columnas % 3 == 0) <tr> @endif 
     <?php $columnas=$columnas+1;?>  
     <td style="border: 1px solid #CCC; width:9cm; height: 4.5cm">
        <div style="margin-top:5px;">
            <p style="text-align: center;font-family:'Montserrat';font-size:10px;font-weight: bold;">{{$producto->nombre}}</p>
            <p style="text-align: center;font-family:'Montserrat';font-size:10px;">{{$producto->nombreproveedor}} &nbsp;{{$producto->apellido}}</p>
            <p style="text-align: center;font-family:'Montserrat';font-size:10px;">
<!--  @if ($producto->direccion!="")
  <span> {{$producto->direccion}},</span>
@endif --> 
@if ($producto->ciudad!="")
  <span> {{$producto->ciudad}},</span>
@endif 
@if ($producto->provincia!="")
  <span> {{$producto->provincia}}.</span>
@endif         
            </p>
            <div style="margin-left: 40px"><img   alt="testing" src="/librarys/barcode.php?codetype=Code39&text=<?php echo $producto->codigo_barras;?>&print=true&size=32" /></div>
        </div>
    </td> 
@if (($columnas % 3) == 0) </tr> @endif 
@endfor                           
@endif

@endforeach

@else
<h1 style="text-align: center;">No hay etiquetas para este producto</h1>
@endif 

@endforeach
@if (($columnas % 3) != 0) </tr> @endif
</table>

@else
<h1 style="text-align: center;">Por favor seleccione un producto</h1>
@endif 

