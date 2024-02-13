<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Categoria;
use App\Models\Imagen_producto;
use App\Models\Stock_log;
use App\Models\LogsCostosPrecios;
use App\Models\Stock;
use App\Models\RelacionCategoriaProveedor;
use App\Models\FacturasProveedores;
use App\Models\DetalleFacturasProveedores;
use App\Models\FacturasProveedoresLogs;
use App\Models\RemitoFacturasProveedores;
use Spipu\Html2Pdf\Html2Pdf;
use storage;
use Image;


class ProveedorController extends Controller
{
  public function __construct(){
    if (!isset($_COOKIE["kiosco"]) || !isset($_COOKIE["sucursal"])) {
      redirect('/');
      exit();
    }
  }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $mensaje = $request->mensaje;
      $proveedores = Proveedor::orderBy('nombre')->get();
      $sucursales = Sucursales::all();
      $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
      $productos = Producto::with(['stock_' => 
        function ($query) use ($sucursal)
        {
          $query->where("sucursal_id",$sucursal);
        }])->get();
      $total=count($productos);
      $categorias=Categoria::orderBy('nombre')->get();
      $RCategoriasProveedor = Categoria::join("relacion_categoria_proveedor","relacion_categoria_proveedor.categoria_id", "=", "categorias.id")
      ->select("categorias.nombre", "categorias.habilitada","categorias.id","relacion_categoria_proveedor.proveedor_id")
      ->get();
      return view("proveedores.accion",compact("productos","proveedores","mensaje","sucursal","sucursales","categorias","RCategoriasProveedor","total"));
    }


    public function save(Request $request)
    {
        $accion=0;//Nuevo registro
        if ($request->id_proveedor != "")
        {
            $accion=1;//Modificar
            $proveedor = Proveedor::find($request->id_proveedor);
            $mensaje = "Modificación realizada exitosamente";
            RelacionCategoriaProveedor::where('proveedor_id', '=', $request->id_proveedor)->delete();        
          }
          else
          {
            $proveedor = new Proveedor();
            $mensaje = "Alta realizada exitosamente";
          }
          $proveedor->nombre = $request->nombre;
          $proveedor->apellido = $request->apellido;
          $proveedor->direccion = $request->direccion;
          $proveedor->ciudad = $request->ciudad;
          $proveedor->provincia = $request->provincia;
          $proveedor->telefono = $request->telefono;
          $proveedor->mail = $request->mail;
          $proveedor->usuario = $_COOKIE["kiosco"];
          $proveedor->sitio_web =$request->sitio_web;
          $proveedor->save();

        //Se recorren las categorías seleccionadas para ingresarlas en la tabla relacion_categoria_proveedor
          if (isset($request->categoria))
          {
            foreach ($request->categoria as $categoria)
            {
             $relacionCategoria= new RelacionCategoriaProveedor();
             if ($accion==1)
             {
              $relacionCategoria->proveedor_id=$request->id_proveedor;
            } 
            else
            {
              $relacionCategoria->proveedor_id= $proveedor->id;
            } 
            $relacionCategoria->categoria_id=$categoria;
            $relacionCategoria->save();
          }  
        }  
        return redirect('proveedor/mensaje/'.base64_encode($mensaje));
      }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

      return response()->json(Proveedor::find($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request,$id)
    {
     if (!isset($_COOKIE["kiosco"])) {
      if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
        header('Location: /');
      else{
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
      }
    }
     //Elimina las relaciones de las categorías anidadas a ese proveedor
    RelacionCategoriaProveedor::where('proveedor_id', '=', $id)->delete();
    //Elimina el proveedor
    Proveedor::destroy($id);

    return response()->json(array("proceso" => "OK"));
  }
//Verificar si el proveedor tiene productos cargados
  public function checkProducts(Request $request, $id)
  {
    $productos=Producto::where('proveedores_id', '=', $id)->get();
    if (count($productos)>0)
     return response()->json(array("proceso" => "FAIL"));
   else 
    return response()->json(array("proceso" => "OK"));
}

public function getProveedor(Request $request, $id)
{ 
  return response()->json(Proveedor::find($id));
}

public function getCategoriasProveedor(Request $request, $id)
{
  $categorias=RelacionCategoriaProveedor::select('categoria_id')->where('proveedor_id', '=', $id)->get();
  $idcategorias='';
  if (count($categorias)>0)
  {
    foreach($categorias as $categoria)
    {
      $idcategorias= $idcategorias.$categoria->categoria_id.',';
    }
  }
  return response($idcategorias);
}

public function indexPagoProveedores(Request $request)
{
  $mensaje = $request->mensaje;
  $proveedores = Proveedor::all();
  $sucursales = Sucursales::all();
  $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
  $productos = Producto::with(['stock_' => 
    function ($query) use ($sucursal)
    {
      $query->where("sucursal_id",$sucursal);
    }])->get();
  return view("proveedores.pagoProveedores",compact("productos","proveedores","mensaje","sucursal","sucursales"));
}
public function saveFactura(Request $request)
{
  $date=date("Y-m-d H:i:s");
  $htmlProductos="";
  $numProductos=0;
  $proveedor=Proveedor::where("id",$request->proveedor)->first();
  $sucursal=(Sucursales::getSucursal($_COOKIE["sucursal"]) !== null)?Sucursales::getSucursal($_COOKIE["sucursal"]):0;
  $mensaje = "Factura guardada exitosamente";
  $facturaProveedor = new FacturasProveedores();
  $facturaLogs=  new FacturasProveedoresLogs();


  $facturaProveedor->numero_factura=$request->numerofactura;
  $facturaProveedor->fecha=$request->fecha;
  $facturaProveedor->id_sucursal=$sucursal;
  $facturaProveedor->usuario=$_COOKIE["kiosco"];
  $facturaProveedor->id_proveedor=$request->proveedor;
  $facturaProveedor->monto=$request->montoTotal;
  if (isset($request->factura))
  {  
    $extension=$request->file('factura')->extension();
    $date=date('Y-m-d-h-s');
    $name='factura-Numero'.$request->numerofactura.'-'.$date.'.'.$extension;
    $path=$request->file('factura')->storeAs('public/facturas/proveedores',$name);
    $facturaProveedor->ruta_archivo=$path;
  }

  $facturaProveedor->save();
  $facturaLogs->numero_factura=$request->numerofactura;
  $facturaLogs->fecha=date("Y-m-d H:i:s");
  $facturaLogs->id_sucursal=$sucursal;
  $facturaLogs->usuario=$_COOKIE["kiosco"];
  $facturaLogs->id_proveedor=$request->proveedor;
  $facturaLogs->monto=$request->montoTotal;
  $facturaLogs->save();

        //Se guardan los productos de la factura
  $arrayproductos = explode("||", $request->detalleProductos);
  foreach ($arrayproductos as $producto)
  {
    $numProductos=$numProductos+1;
    $datosProducto= explode(",", $producto);
    $detalleFactura=new DetalleFacturasProveedores();
    $detalleFactura->id_factura=$facturaProveedor->id;
    $detalleFactura->id_producto=$datosProducto[0];
    $detalleFactura->cantidad=$datosProducto[1];
    $detalleFactura->costo=$datosProducto[2];
    $detalleFactura->precio=$datosProducto[3];
    $detalleFactura->save();

    $stock = Stock::where("productos_id",$datosProducto[0])->where("sucursal_id",$sucursal)->first();
    $stock_logs = new Stock_log();
    $stock_logs->productos_id   = $datosProducto[0];
    $stock_logs->sucursal_id    = $sucursal;
    $stock_logs->stock_minimo   = $datosProducto[1];
    $stock_logs->usuario        = $_COOKIE["kiosco"];
    $stock_logs->tipo_operacion = 'Ingreso factura';
    $stock_logs->updated_at = date("Y-m-d H:i:s");
    $stock_logs->created_at = date("Y-m-d H:i:s");
    if (!(isset($stock))){
      $stock = new Stock();
      $stock_logs->stock          = $datosProducto[1];
      $stock_logs->stock_anterior         = "-1";
      $stock_logs->stock_minimo_anterior  = "-1";
      $stock->stock           = $datosProducto[1];
      $stock->stock_minimo=1;
    }else{
      $stock_logs->stock          =  $stock->stock + $datosProducto[1];
      $stock_logs->stock_anterior         = $stock->stock;
      $stock_logs->stock_minimo_anterior  = $stock->stock_minimo;
      $stock->stock           = $stock->stock + $datosProducto[1];
    }
    $stock->updated_at = date("Y-m-d H:i:s");
    $stock->created_at = date("Y-m-d H:i:s");
    $stock->sucursal_id     = $sucursal;
    $stock->productos_id    = $datosProducto[0];
    $stock->usuario         = $_COOKIE["kiosco"];
    //Guardar auditoría de cambio de costos o precios
    $stockCostoPrecioLog= new LogsCostosPrecios();
    $stockCostoPrecioLog->updated_at = date("Y-m-d H:i:s");
    $stockCostoPrecioLog->created_at = date("Y-m-d H:i:s");
    $stockCostoPrecioLog->sucursal_id     = $sucursal;
    $stockCostoPrecioLog->productos_id    = $datosProducto[0];
    $stockCostoPrecioLog->usuario         = $_COOKIE["kiosco"];
    $stockCostoPrecioLog->operacion="Ingreso de factura Nº ".$request->numerofactura;
    $stock->save();
    $stock_logs->save();

    $cambia=0;
    $productos=Producto::where("id",$datosProducto[0])->first();
    $stockCostoPrecioLog->costo_anterior=$productos->costo;
    $stockCostoPrecioLog->costo=$productos->costo;
    $stockCostoPrecioLog->precio_anterior=$productos->precio_unidad;
    $stockCostoPrecioLog->precio=$productos->precio_unidad;
    if(isset($productos) && floatval($productos->costo)< floatval($datosProducto[2]))
    {
      $stockCostoPrecioLog->costo_anterior=$productos->costo;
      $stockCostoPrecioLog->costo=floatval($datosProducto[2]);
      $productos->costo=floatval($datosProducto[2]);     
      $cambia=1;
    }
    if(isset($productos) && floatval($productos->precio_unidad)!= floatval($datosProducto[3]))
    {
      $stockCostoPrecioLog->precio_anterior=$productos->precio_unidad;
      $stockCostoPrecioLog->precio=floatval($datosProducto[3]);
      $productos->precio_unidad=floatval($datosProducto[3]);
      $cambia=1;
    }
    if(isset($productos)  && $cambia==1)
       {
      $productos->save();
      $stockCostoPrecioLog->save();
       }

    $htmlProductos .= utf8_encode("<tr>
      <td style='border-bottom: 1px solid #000;width: 20px'><i>$numProductos</i></td>
      <td style='border-bottom: 1px solid #000;width: 150px'><i>$productos->codigo_barras</i></td>
      <td style='border-bottom: 1px solid #000;width: 150px'><i>$productos->nombre</i></td>
      <td style='border-bottom: 1px solid #000;width: 65px'>".$datosProducto[1]."</td>
      <td style='border-bottom: 1px solid #000;width: 80px'>".number_format(floatval($datosProducto[2]),2,",",".")."</td>
       <td style='border-bottom: 1px solid #000;width: 80px'>".number_format(floatval($datosProducto[3]),2,",",".")."</td>
      <td style='border-bottom: 1px solid #000;width: 150px'>".number_format(floatval($datosProducto[2])*floatval($datosProducto[1]),2,",",".")."</td>
      
      </tr>
      ");
            }  //e
            $remito = new RemitoFacturasProveedores();
            $remito->id_factura_proveedor =$facturaProveedor->id;
            $remito->fecha_generacion = date('Y-m-d-h-s');
            $remito->usuario = $_COOKIE["kiosco"];
            $remito->save();
            $numRemito=$remito->id;
            $TotalFactura=number_format(floatval($request->montoTotal),2,",",".");
            $html = utf8_encode("
              <style>
              h3{
                font-size:1em;
              }
              </style>
              <table>
              <tr>
              <td>  
              <b></b>
              </td>
              </tr>
              <tr>
              <td style='border: 2px solid #000;height: 100px;font-size: 14px;width: 720px;text-align: left;'>
              <b style='margin-left:420'>COMPROBANTE NRO:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$numRemito<br />
              <b>DOCUMENTO</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
              <br/>
              <b>PROVEEDOR</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$proveedor->nombre &nbsp;$proveedor->apellido<br />
              <b>FECHA DE EMISI&Oacute;N</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$date<br />
              <b>N&Uacute;MERO DE ITEMS</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$numProductos<br />
              <b>TOTAL $</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $TotalFactura<br />

              </td>
              </tr> </table>");

            $html.="<table><tr>
            <td style='border-bottom: 1px solid #000;width: 20px'><b>Nº</b></td>
            <td style='border-bottom: 1px solid #000;width: 150px'><b>Barra</b></td>
            <td style='border-bottom: 1px solid #000;width: 150px'><b>Nombre</b></td>
            <td style='border-bottom: 1px solid #000;width: 65px'><b>Cantidad</b></td>
            <td style='border-bottom: 1px solid #000;width: 80px'><b>Costo</b></td>
            <td style='border-bottom: 1px solid #000;width: 80px'><b>Precio</b></td>
            <td style='border-bottom: 1px solid #000;width: 150px'><b>Subtotal</b></td>
            </tr>
            ";

            $html .=$htmlProductos;

            $html .= utf8_encode("</table>");

            $html2pdf = new HTML2PDF('P', 'A4', 'pt', true, 'UTF-8');
            $html2pdf->setDefaultFont('Arial');

            $html2pdf->writeHTML("<page>".str_replace("DOCUMENTO","ORIGINAL",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page><page>".str_replace("DOCUMENTO","DUPLICADO",$html)."<br><br><hr style='border-style: dotted;' /><br><br></page>");

            $nombre_factura="comprobante".$numRemito.".pdf";
            $html2pdf->Output(__DIR__.'/../../../public/comprobantesProveedores/'.$nombre_factura, "F");

            $nombre_factura='/comprobantesProveedores/'.$nombre_factura;
            $remito->archivo=$nombre_factura;
            $remito->save();

            return response()->json(array("proceso" => "OK","comprobante" => $nombre_factura));



          }

        }