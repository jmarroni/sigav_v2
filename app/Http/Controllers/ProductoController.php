<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Imagen_producto;
use App\Models\Stock_log;
use App\Models\Stock;
use Illuminate\Support\Facades\Storage;
use Image;


class ProductoController extends Controller
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
         $productos =//Producto::leftjoin("stock","stock.productos_id", "=", "productos.id")
        //                     ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
        //                     ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
        //                     ->where("stock.sucursal_id","=",$sucursal)
        //                     ->select("productos.*","stock.stock as stockReal","stock.stock_minimo as stockMinimoReal","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","categorias.nombre as nombreCategoria")
        //                     ->get();

         Producto::with(['stock_' => 
            function ($query) use ($sucursal)
            {
                $query->where("sucursal_id",$sucursal);
            }])
            ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
            ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
            ->select("productos.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","categorias.nombre as nombreCategoria")
            ->get();

         //get();
        return view("productos.accion",compact("productos","proveedores","mensaje","sucursal","sucursales"));
    }


    public function update_stock(Request $request,$id,$_stock,$stock_minimo,$sucursal)
    {
        $stock = Stock::where("productos_id",$id)->where("sucursal_id",$sucursal)->first();
        $stock_logs = new Stock_log();
        $stock_logs->productos_id   = $id;
        $stock_logs->sucursal_id    = $sucursal;
        $stock_logs->stock          = $_stock;
        $stock_logs->stock_minimo   = $stock_minimo;
        $stock_logs->usuario        = $_COOKIE["kiosco"];
        $stock_logs->tipo_operacion = 'Actualización';
        if (!(isset($stock))){
            $stock = new Stock();
            $stock_logs->stock_anterior         = "-1";
            $stock_logs->stock_minimo_anterior  = "-1";
        }else{
            $stock_logs->stock_anterior         = $stock->stock;
            $stock_logs->stock_minimo_anterior  = $stock->stock_minimo;
        }
        $stock->sucursal_id     = $sucursal;
        $stock->productos_id    = $id;
        $stock->stock           = $_stock;
        $stock->stock_minimo    = $stock_minimo;
        $stock->usuario         = $_COOKIE["kiosco"];
        $stock->save();
        $stock_logs->save();
        return response()->json(array("proceso" => "OK"));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $id=0;
        $stock_logs = new Stock_log();
        if ($request->id != ""){
            $productos = Producto::find($request->id);
            $stock = new Stock();
            $stock->sucursal_id     = Sucursales::getSucursal();
            $stock->productos_id    = $request->id;
            $stock->stock           = 0;
            $stock->stock_minimo    = 0;
            $stock->usuario         = $_COOKIE["kiosco"];
            $stock->save();
            $stock_logs->productos_id   = $request->id;
            $stock_logs->sucursal_id    = Sucursales::getSucursal();;
            $stock_logs->stock          = 0;
            $stock_logs->stock_minimo   = 0;
            $stock_logs->usuario        = $_COOKIE["kiosco"];
            $stock_logs->tipo_operacion = 'Actualización';
            $stock_logs->save();
            $mensaje = "Actualizaci&oacute;n realizada exitosamente";
        }else{
            $productos = new Producto();
            $mensaje = "Alta realizada exitosamente";
        }
        // ALTA DEL PRODUCTO
        if ($request->codigo_de_barras != ""){ 
            $codigo_barras =  $request->codigo_de_barras;
        }else 
        $codigo_barras = $request->categoria.substr("000000".$request->proveedor,-5).rand(111111,999999);
        $productos->codigo_barras       = $codigo_barras;
        $productos->nombre              = $request->producto;
        $productos->precio_unidad       = $request->precio_unidad;
        $productos->costo               = $request->costo;
        $productos->stock               = $request->stock;
        $productos->stock_minimo        = $request->stock_minimo;
        $productos->proveedores_id      = $request->proveedor;
        $productos->categorias_id       = ($request->categoria == "")?"---":$request->categoria;
        $productos->usuario             = $_COOKIE["kiosco"];
        $productos->fecha               = date("Y-m-d");
        $productos->precio_mayorista    = $request->precio_mayorista;
        $productos->es_comodato         = 1;
        $productos->descripcion         = $request->descripcion;
        $productos->descripcion_pr      = $request->descripcion_pr;
        $productos->descripcion_en      = $request->descripcion_en;
        $productos->material            = $request->material;
        $productos->precio_reposicion   = ($request->precio_reposicion == "")?0:$request->precio_reposicion;
        $productos->save();
       // $id=Producto::latest('id')->first()->id;
        $stock_logs->productos_id   = $productos->id;
        $stock_logs->sucursal_id    = 0;
        $stock_logs->stock          = 0;
        $stock_logs->stock_minimo   = 0;
        $stock_logs->usuario        = $_COOKIE["kiosco"];
        $stock_logs->stock_minimo_anterior=0;
        $stock_logs->stock_anterior=0;
        $stock_logs->tipo_operacion = 'Alta';
        $stock_logs->save();
        $imagenes = array();
        for($i = 0;$i < 7; $i ++){
            if ($request->hasFile('imagen'.$i)) {
                $path = $request->{"imagen".$i}->store('public/productos/images/'.$productos->id);
                $image_resize = Image::make(storage_path('app')."/".$path);
                $image_resize->fit(300, 300);
                $image_resize->save(str_replace("/".$productos->id."/","/".$productos->id."/thumb_300x300_",storage_path('app')."/".$path));
                $imagenes[] = $path;
            }
        }
        if (count($imagenes) > 0){
            Imagen_producto::where("productos_id",$productos->id)->delete();
            foreach($imagenes as $imagen){
                $imagen_producto = new Imagen_producto();
                $imagen_producto->imagen_url = env("APP_URL").str_replace("public","/storage",$imagen);
                $imagen_producto->productos_id = $productos->id;
                $imagen_producto->save();
            }
        }

        return redirect('carga/mensaje/'.base64_encode($mensaje));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        return response()->json(Producto::find($id));
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        
    }
    public function delete_stock(Request $request,$id,$_stock,$stock_minimo,$sucursal)
    {
     if (!isset($_COOKIE["kiosco"])) {
        if (!isset($_GET["apiKey"]) || $_GET["apiKey"] != "a0a035dc5213448bb1a130c27f2494c5")
            header('Location: /');
        else{
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/json');
        }
    }
    $stock = Stock::where("productos_id",$id)->where("sucursal_id",$sucursal)->first();
    $producto =Producto::find($id);
    $stock_logs = new Stock_log();
    $stock_logs->productos_id   = $id;
    $stock_logs->sucursal_id    = 0;
    $stock_logs->stock          = $_stock;
    $stock_logs->stock_minimo   = $stock_minimo;
    $stock_logs->usuario        = $_COOKIE["kiosco"];
    $stock_logs->tipo_operacion = 'Baja';
    $stock_logs->stock_anterior         = $producto->stock;
    $stock_logs->stock_minimo_anterior  = $producto->stock_minimo;
        //Elimina el producto
    Producto::destroy($id);
        //Elimina los stocks de las diferentes sucursales asociados a ese producto
    Stock::where('productos_id', '=', $id)->delete();
        //Se guarda el registro en la tabla de auditoría
    $stock_logs->save();
    return response()->json(array("proceso" => "OK"));
        // $mensaje="Eliminaci&oacute;n realizada exitosamente";
        // return redirect('carga/mensaje/'.base64_encode($mensaje));
}

public function searchProducts(request $request)
    {

       if (isset($_GET["producto"]) && intval($_GET["producto"]) !== null && isset($_GET["sucursal"]))
       { 
            $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
            $productos =    Producto::join("stock","stock.productos_id", "=", "productos.id")
                            ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
                            ->leftjoin("imagen_producto","imagen_producto.productos_id", "=", "productos.id")
                            ->where("stock.sucursal_id",$_GET["sucursal"])
                            ->where(function($q) {
                            $q->where("productos.nombre","like", "%" . $_GET["producto"]. "%","OR")
                            ->orWhere("productos.codigo_barras","like", "%" . $_GET["producto"] . "%");
                             })
                            ->select("productos.*","sucursales.id as idsucursal","imagen_producto.imagen_url","stock.stock","stock.stock_minimo")
                            ->OrderBy("productos.nombre")
                            ->get();
            $i=0; 
            if (count($productos)>0)
                { 
                     foreach($productos as $producto)
                        {
                            $datos[0]["value"]         = $producto->nombre." (".$producto->codigo_barras.")";
                            $datos[0]["label"]         = $producto->nombre." (".$producto->codigo_barras.")";
                            $datos[0]["id"]            = $producto->id;
                            $datos[0]["costo"]         = $producto->costo;
                            $datos[0]["precio"]        = ($lista_precio == 1)?$producto->precio_unidad:$producto->precio_mayorista;
                            $datos[0]["stock"]         = $producto->stock;
                            $datos[$i]["imagen"]         = ($producto->imagen!=NULL)?$producto->imagen:"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
                            $datos[0]["stock_minimo"]  = $producto->stock_minimo;
                            $datos[0]["codigo_barras"] = $producto->codigo_barras;
                            $i++;
                        }//endforeach
                }
            else
                { 
                    $datos = array("data" => "no data");
                }
    }
    
    if (!isset($_GET["producto"]) && isset($_GET["term"]))
       
    {
        $sucursal_seleccionada = (isset($_GET["sucursal"]))?intval($_GET["sucursal"]):Sucursales::getSucursal($_COOKIE["sucursal"]);
        $productos = Producto::leftjoin("stock","stock.productos_id", "=", "productos.id")
        ->leftjoin("sucursales","sucursales.id", "=", "stock.sucursal_id")
        ->leftjoin("imagen_producto","imagen_producto.productos_id", "=", "productos.id")
        ->where(function($query){
                    $query->where("productos.nombre","like", "%" . $_GET["term"]. "%")
                   ->orWhere("productos.codigo_barras","like", "%" . $_GET["term"] . "%");
               })
        ->where("stock.sucursal_id",$sucursal_seleccionada)
        ->select("productos.*","sucursales.id as idsucursal","imagen_producto.imagen_url","stock.stock AS stock_sucursal","stock.stock_minimo AS stock_sucursal")
        ->OrderBy("productos.id")
        ->get();
        $i=0;    
        $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
            if (count($productos)>0) 
                {
                    foreach($productos as $producto)
                        {
                            $datos[$i]["value"]         = utf8_encode($producto->nombre." (".$producto->codigo_barras.")");
                            $datos[$i]["label"]         = utf8_encode($producto->nombre." (".$producto->codigo_barras.")");
                            $datos[$i]["id"]            = $producto->id;
                            $datos[$i]["costo"]         = $producto->costo;
                            $datos[$i]["precio"]        = ($lista_precio == 1)?$producto->precio_unidad:$producto->precio_mayorista;
                            $datos[$i]["stock"]         = 0;
                            $datos[$i]["imagen"]        =  (isset($producto->imagen))?str_replace('/'.$producto->id.'/','/'.$producto->id.'/thumb_300x300_',$producto->imagen):"/assets/img/photos/no-image-featured-image.png";
                            $datos[$i]["stock"]         = $producto->stock_sucursal;
                                $datos[$i]["stock_minimo"]  = $producto->stock_sucursal;
                            $datos[$i]["codigo_barras"] = $producto->codigo_barras;
                             $datos[$i]["suc"]=$sucursal_seleccionada;
                                if (isset($request->sucursal) && ($request->sucursal != ""))
                                    {
                                    $stock =Stock::where("sucursal_id =".intval($request->sucursal)." AND productos_id = ".$producto->id)
                                        ->get();
                                     if (count($stock)>0) 
                                        {           
                                            $datos[$i]["stock"]         =   $stock->stock;
                                            $datos[$i]["stock_minimo"]  =   $stock->stock_minimo;
                                        }
                                        else
                                        {
                                            $datos[$i]["stock"]         =   0; 
                                            $datos[$i]["stock_minimo"]  =   0;
                                        }
                               
                                 } //endif
                                 $i++;
                            } //endforeach
                } //endif
                else 
                    {
                        $datos = array("data" => "no data");
                    }
     } //endif
 

 return response()->json($datos);

}

}