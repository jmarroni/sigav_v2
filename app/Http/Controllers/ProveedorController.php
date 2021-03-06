<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Categoria;
use App\Models\Imagen_producto;
use App\Models\Stock_log;
use App\Models\Stock;
use App\Models\RelacionCategoriaProveedor;
use App\Models\FacturasProveedores;
use App\Models\DetalleFacturasProveedores;
use App\Models\FacturasProveedoresLogs;
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
        $proveedores = Proveedor::all();
        $sucursales = Sucursales::all();
        $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
        $productos = Producto::with(['stock_' => 
            function ($query) use ($sucursal)
            {
                $query->where("sucursal_id",$sucursal);
            }])->get();
        $total=count($productos);
        $categorias=Categoria::all();
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
                $datosProducto= explode(",", $producto);
                $detalleFactura=new DetalleFacturasProveedores();
                $detalleFactura->id_factura=$facturaProveedor->id;
                $detalleFactura->id_producto=$datosProducto[0];
                $detalleFactura->cantidad=$datosProducto[1];
                $detalleFactura->precio=$datosProducto[2];
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
                $stock->save();
                $stock_logs->save();
            }  //e
        return redirect('pagoProveedores/mensaje/'.base64_encode($mensaje));


    }

}
