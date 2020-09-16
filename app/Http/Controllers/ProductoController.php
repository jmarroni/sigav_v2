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
        $productos = Producto::with(['stock_' => 
                                        function ($query) use ($sucursal)
                                        {
                                            $query->where("sucursal_id",$sucursal);
                                        }])->get();
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

        if ($request->id != ""){
            $productos = Producto::find($request->id);
            $stock = new Stock();
            $stock->sucursal_id     = Sucursales::getSucursal();
            $stock->productos_id    = $request->id;
            $stock->stock           = 0;
            $stock->stock_minimo    = 0;
            $stock->usuario         = $_COOKIE["kiosco"];
            $stock->save();
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

}
