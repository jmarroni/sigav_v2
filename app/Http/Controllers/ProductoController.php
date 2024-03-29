<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Imagen_producto;
use App\Models\Stock_log;
use App\Models\LogsCostosPrecios;
use App\Models\Stock;
use App\Models\SitesSucursalesOpencart;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use GuzzleHttp\Client;
use Curl\curl;


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
        $proveedores = Proveedor::orderBy('nombre')->get();
        $sucursales = Sucursales::all();
        $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
        $imagenes=Imagen_producto::all();
        $imagen=array();
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
        //->leftjoin("imagen_producto","imagen_producto.productos_id","=","productos.id")
        ->select("productos.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","categorias.nombre as nombreCategoria")
        ->get();

         //get();
        return view("productos.accion",compact("productos","proveedores","mensaje","sucursal","sucursales","imagenes","imagen"));
    }


    public function update_stock(Request $request,$id,$_stock,$stock_minimo,$sucursal)
    {
        $token="";
        $producto=Producto::where("id",$id)->first();
        $stock = Stock::where("productos_id",$id)->where("sucursal_id",$sucursal)->first();
        $stock_logs = new Stock_log();
        $stock_logs->productos_id   = $id;
        $stock_logs->sucursal_id    = $sucursal;
        $stock_logs->stock          = $_stock;
        $stock_logs->stock_minimo   = $stock_minimo;
        $stock_logs->usuario        = $_COOKIE["kiosco"];
        $stock_logs->tipo_operacion = 'Actualización';
        $stock_logs->created_at=date("Y-m-d H:i:s");
        $stock_logs->updated_at=date("Y-m-d H:i:s");
        $stock_logs->barra=$producto->codigo_barras;
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


        // $token=$this->loginTiendaOpencart($sucursal);
        // if ($token!=-1 && $token!=-2)
        // { 

        //  $resultado=$this->actualizarStockOpencart($token,$id,$_stock,$sucursal);
        //     if ($resultado==-1)
        //         {
        //             $mensaje='Producto guardado en Sigav pero falló la sincronización con opencart';
        //         }
        // }

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
        $productoApi=array();
        $id=0;
        $codigo_barras='';
        $sucursal=Sucursales::getSucursal();
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
            $stock_logs->sucursal_id    = $sucursal;
            $stock_logs->stock          = 0;
            $stock_logs->stock_minimo   = 0;
            $stock_logs->usuario        = $_COOKIE["kiosco"];
            $stock_logs->tipo_operacion = 'Actualización';
            $stock_logs->created_at=date("Y-m-d H:i:s");
            $stock_logs->updated_at=date("Y-m-d H:i:s");
            $stock_logs->barra=$productos->codigo_barras;
            $stock_logs->save();
            //Guardar auditoría de cambios de costos y precios
            if(($productos->costo!=$request->costo)||($productos->precio_unidad!=$request->precio_unidad))
            {
                $stockCostoPrecioLog= new LogsCostosPrecios();
                $stockCostoPrecioLog->updated_at = date("Y-m-d H:i:s");
                $stockCostoPrecioLog->created_at = date("Y-m-d H:i:s");
                $stockCostoPrecioLog->sucursal_id     = $sucursal;
                $stockCostoPrecioLog->productos_id    = $request->id;
                $stockCostoPrecioLog->usuario         = $_COOKIE["kiosco"];
                $stockCostoPrecioLog->costo_anterior=$productos->costo;
                $stockCostoPrecioLog->costo=$request->costo;
                $stockCostoPrecioLog->precio_anterior=$productos->precio_unidad;
                $stockCostoPrecioLog->precio=$request->precio_unidad;
                $stockCostoPrecioLog->operacion="Cambio de costo y/o precio desde módulo de carga";
                $stockCostoPrecioLog->barra=$productos->codigo_barras;
                $stockCostoPrecioLog->save();
            }
            $mensaje = "Actualizaci&oacute;n realizada exitosamente";
        }else{
            $productos = new Producto();
            $mensaje = "Alta realizada exitosamente";
        }
        // ALTA DEL PRODUCTO
        if ($request->codigo_de_barras != ""){ 
            $codigo_barras =  $request->codigo_de_barras;
            $productoApi[0]['codigo_barras']=$request->codigo_de_barras;
        }else 
        $codigo_barras = $request->categoria.substr("000000".$request->proveedor,-5).rand(111111,999999);
        $productoApi[0]['codigo_barras']=$request->categoria.substr("000000".$request->proveedor,-5).rand(111111,999999);
        $productos->codigo_barras       = $codigo_barras;
        $productos->nombre              = $request->producto;
        $productoApi[0]['nombre']       = $request->producto;
        $productos->precio_unidad       = $request->precio_unidad;
        $productoApi[0]['precio_unidad']= $request->precio_unidad;
        $productos->costo               = $request->costo;
        $productoApi[0]['costo']        = $request->costo;
        $productos->stock               = $request->stock;
        $productoApi[0]['stock']        = $request->stock;
        $productos->stock_minimo        = $request->stock_minimo;
        $productoApi[0]['stock_minimo'] = $request->stock_minimo;
        $productos->proveedores_id      = $request->proveedor;
        $productos->categorias_id       = ($request->categoria == "")?"---":$request->categoria;
        $productos->usuario             = $_COOKIE["kiosco"];
        $productos->fecha               = date("Y-m-d");
        $productos->precio_mayorista    = $request->precio_mayorista;
        $productos->es_comodato         = 1;
        $productos->descripcion         = $request->descripcion;
        $productoApi[0]['descripcion']  = $request->descripcion;
        $productos->descripcion_pr      = $request->descripcion_pr;
        $productos->descripcion_en      = $request->descripcion_en;
        $productos->material            = $request->material;
        $productos->precio_reposicion   = ($request->precio_reposicion == "")?0:$request->precio_reposicion;
        $productos->save();
        $productoApi[0]['id']=$productos->id;

       // $id=Producto::latest('id')->first()->id;
        $stock_logs->productos_id   = $productos->id;
        $stock_logs->sucursal_id    = $sucursal;
        $stock_logs->stock          = 0;
        $stock_logs->stock_minimo   = 0;
        $stock_logs->usuario        = $_COOKIE["kiosco"];
        $stock_logs->stock_minimo_anterior=0;
        $stock_logs->stock_anterior=0;
        $stock_logs->tipo_operacion = 'Alta';
        $stock_logs->created_at=date("Y-m-d H:i:s");
        $stock_logs->updated_at=date("Y-m-d H:i:s");
        $stock_logs->barra=$codigo_barras;
        $stock_logs->save();
         
        $imagenes = array();
        for($i = 0;$i < 7; $i ++){
            if ($request->hasFile('imagen'.$i)) {
                $path = $request->{"imagen".$i}->store('public/productos/images/'.$productos->id);
                $image_resize = Image::make(storage_path('app')."/".$path);
                $image_resize->fit(300, 300);
                $image_resize->save(str_replace("/".$productos->id."/","/".$productos->id."/thumb_300x300_",storage_path('app')."/".$path));
                $imagenes[] = $path;
                if ($i==0)
                {
                    $productoApi[0]['imagen']=$request->{"imagen".$i};
                }
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
        // $token=$this->loginTiendaOpencart($sucursal);
        // if ($token!=-1 && $token!=-2)
        // { 

        //  $resultado=$this->guardarProductoTiendaOpencart($token,$productoApi,$sucursal);
        //   // if ($resultado!=-1 && $resultado!=-2)
        //   //       {
        //   //           $mensaje='Producto guardado en Sigav pero falló la sincronización con opencart';
        //   //       }
        //     if ($resultado==-1)
        //         {
        //             $mensaje='Producto guardado en Sigav pero falló la sincronización con opencart';
        //         }
        // }
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
    $stock_logs->created_at=date("Y-m-d H:i:s");
    $stock_logs->updated_at=date("Y-m-d H:i:s");
    $stock_logs->barra=$producto->codigo_barras;
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
    if (isset($_GET["tipoBusqueda"]))$_GET["sucursal"]=(isset($_GET["sucursal"]))?intval($_GET["sucursal"]):Sucursales::getSucursal($_COOKIE["sucursal"]);

    if (isset($_GET["producto"]) && intval($_GET["producto"]) !== null && isset($_GET["sucursal"]))
    { 
        $lista_precio = (isset($_COOKIE["lista_precio"]))?$_COOKIE["lista_precio"]:1;
        $productos =    Producto::join("stock","stock.productos_id", "=", "productos.id")
        ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
        //->leftjoin("imagen_producto","imagen_producto.productos_id", "=", "productos.id")
        ->where("stock.sucursal_id",$_GET["sucursal"])
        ->where(function($q) {
            $q->where("productos.nombre","like", "%" . $_GET["producto"]. "%","OR")
            ->orWhere("productos.codigo_barras","like", "%" . $_GET["producto"] . "%");
        })
        ->select("productos.*","sucursales.id as idsucursal",/*"imagen_producto.imagen_url",*/"stock.stock","stock.stock as stockactual","stock.stock_minimo")
        ->OrderBy("productos.nombre")
        ->get();
        $i=0; 
        if (count($productos)>0)
        { 
         foreach($productos as $producto)
         {
            $datos[$i]["value"]         = $producto->nombre." (".$producto->codigo_barras.")";
            $datos[$i]["label"]         = $producto->nombre." (".$producto->codigo_barras.")";
            $datos[$i]["id"]            = $producto->id;
            $datos[$i]["costo"]         = $producto->costo;
            $datos[$i]["precio"]        = ($lista_precio == 1)?$producto->precio_unidad:$producto->precio_mayorista;
            $datos[$i]["stock"]         = $producto->stock;
            $datos[$i]["imagen"]         = ($producto->imagen!=NULL)?$producto->imagen:"http://mercado-artesanal.com.ar/assets/img/photos/no-image-featured-image.png";
            $datos[$i]["stock_minimo"]  = $producto->stock_minimo;
            $datos[$i]["codigo_barras"] = $producto->codigo_barras;
            $datos[$i]["stockactual"]   = $producto->stockactual;
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
                            $datos[$i]["imagen"]        =  (isset($producto->imagen))?"/assets/img/photos/no-image-featured-image.png".$producto->imagen:"/assets/img/photos/no-image-featured-image.png";
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
 public function searchProductsSinStock(request $request)
 {
    if (isset($_GET["term"]))
    {
        $productos = Producto::where("productos.nombre","like", "%" . $_GET["term"]. "%")
        ->orWhere("productos.codigo_barras","like", "%" . $_GET["term"] . "%")
        ->select("productos.*")
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
                $datos[$i]["imagen"]        = "/assets/img/photos/no-image-featured-image.png";
                $datos[$i]["codigo_barras"] = $producto->codigo_barras;
                $i=$i+1;
            } //endforeach
                } //endif
                else 
                {
                    $datos = array("data" => "no data");
                }
     } //endif


     return response()->json($datos);

 }
 public function consultarStock(request $request)
 {
     $sucursales = Sucursales::all();
     $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
     $productos="";
     if ($sucursal!=0)
     { 
         $productos=Producto::with(['stock_' => 
          function ($query) use ($sucursal)
          {
        //$query->where("sucursal_id","<>",0);
            $query->where("sucursal_id",$sucursal);
        }])
         ->leftjoin("stock","stock.productos_id", "=", "productos.id")
         ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
         ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
         ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
         ->where("stock.sucursal_id","=",$sucursal)
       // ->select("productos.*","proveedor.nombre as nombreProveedor","proveedor.apellido as apellidoProveedor","sucursales.nombre as sucursal")
         ->select("sucursales.nombre as sucursal","productos.nombre as nombre","productos.precio_unidad","productos.costo","proveedor.nombre AS nombreProveedor","stock.stock","stock.stock_minimo")
         ->OrderBy("sucursales.nombre","asc")
         ->OrderBy("sucursales.nombre","asc")
         ->get();
     }
     else
     { //Muestra los productos de todas las sucursales
         $productos=Producto::with(['stock_' => 
          function ($query) use ($sucursal)
          {
        //$query->where("sucursal_id","<>",0);
            $query->where("sucursal_id",$sucursal);
        }])
         ->leftjoin("stock","stock.productos_id", "=", "productos.id")
         ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
         ->leftjoin("categorias","categorias.id", "=", "productos.categorias_id")
         ->leftjoin("proveedor","proveedor.id", "=", "productos.proveedores_id")
         ->select("sucursales.nombre as sucursal","productos.nombre as nombre","productos.precio_unidad","productos.costo","proveedor.nombre AS nombreProveedor","stock.stock","stock.stock_minimo")
         ->OrderBy("sucursales.nombre","asc")
         ->get();
     }

     return response()->json($productos);

 }
 public function loginTiendaOpencart($sucursal)
 {
    $site=SitesSucursalesOpencart::where("id_sucursal",$sucursal)->first();
    if ($site!=null && $site!="" )
    { 
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $site->url.'index.php?route=api/login/index', [
            'form_params' => [
                'username' => $site->user,
                'key' => $site->password
            ]
        ]);

        $respuesta=$res->getBody();
        $respuesta=json_decode($respuesta, true);
        //var_dump($productoApi);
        if (isset($respuesta['success']) && isset($respuesta['api_token']))
        { 
            return $respuesta['api_token'];
        }
        else
            { //Falló la autenticación
                return -1;
            }
        }
        else
    { //No existe información de opencart para esa sucursal
        return -2;
    }

}
public function guardarProductoTiendaOpencart($token, $datosProducto,$sucursal)
{
   $site=SitesSucursalesOpencart::where("id_sucursal",$sucursal)->first();
   if ($site!=null && $site!="" )
   { 
    $client = new \GuzzleHttp\Client();
    $res = $client->request('POST', $site->url.'index.php?route=api/product/saveProducto', [
        'form_params' => [
            'route'=> 'api/product/saveProducto',
            'producto' => $datosProducto,
            'api_token'=> $token

        ],
    ]);
    $respuesta=$res->getBody();
    $respuesta=json_decode($respuesta, true);
    if (isset($respuesta['success']))
         {//Éxito

            return $respuesta['success'];
        }
        else
        {//Éxito

            return -1;
        }

    }
    else
        { //No existe información de opencart para esa sucursal
            return -2;
        } 
    }
    public function actualizarStockOpencart($token, $id,$cantidad,$sucursal)
    {
       $site=SitesSucursalesOpencart::where("id_sucursal",$sucursal)->first();
       if ($site!=null && $site!="" )
       { 
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $site->url.'index.php?route=api/product/actualizarStock', [
            'form_params' => [
                'route'=> 'api/product/actualizarStock',
                'id' => $id,
                'cantidad'=>$cantidad,
                'api_token'=> $token

            ],
        ]);
        $respuesta=$res->getBody();
        $respuesta=json_decode($respuesta, true);
        if (isset($respuesta['success']))
         {//Éxito

            return 1;
        }
        else
        {//Éxito

            return -1;
        }

    }
    else
        { //No existe información de opencart para esa sucursal
            return -2;
        } 
    }
    public function guardarImagenProductoOpencart($sucursal,$imagen )
    {
       $site=SitesSucursalesOpencart::where("id_sucursal",$sucursal)->first();
       if ($site!=null && $site!="" )
       { 
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $site->url.'index.php?route=api/product/saveImagenProducto', [
    'multipart' => [
        [
            'name'     => 'imagen',
            'contents' => $imagen,
        ],
    ]
]);
        $respuesta=$res->getBody();
        $respuesta=json_decode($respuesta, true);
        if (isset($respuesta['success']))
         {//Éxito

            return 1;
        }
        else
        {//Éxito

            return -1;
        }

    }
    else
        { //No existe información de opencart para esa sucursal
            return -2;
        } 
    }
}