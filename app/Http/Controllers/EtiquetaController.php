<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Categoria;
use App\Models\Categoria_log;
use App\Models\RelacionCategoriaProveedor;
use App\Models\RelacionTransferenciaProductos;
use Illuminate\Support\Facades\Storage;
use Image;


class EtiquetaController extends Controller
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
        $sucursal = (isset($request->sucursal)?$request->sucursal:Sucursales::getSucursal());
        $productos = Producto::with(['stock_' => 
            function ($query) use ($sucursal)
            {
                $query->where("sucursal_id",$sucursal);
            }])->get();
        $total=count($productos);
        $categorias=Categoria::all();

        return view("etiquetas.accion",compact("categorias","total","mensaje","productos"));
    }


    public function getQr(Request $request, $id)
    {
       $Producto = Producto::join("proveedor","proveedor.id", "=", "productos.proveedores_id")
       ->select("proveedor.sitio_web")
       ->where('productos.id', '=', $id)
       ->get();
        if (count($Producto)>0)
            return response()->json(array("sitio_web" =>$Producto));
        else 
            return response()->json(array("data" => "no existe"));
    }
    public function printEtiquetas(Request $request, $etiquetas)
    {
        if (!isset($_COOKIE["kiosco"])) 
            {
                header('Location: /');
            }
        $etiquetasimprimir=($etiquetas=="")?"":$etiquetas;  
        $arrEtiquetas = explode('-',$etiquetasimprimir);
        $productos=Producto::join("proveedor","proveedor.id", "=", "productos.proveedores_id")
        ->select("productos.*","proveedor.nombre as nombreproveedor","proveedor.apellido","proveedor.ciudad","proveedor.provincia","proveedor.direccion")
        ->OrderBy("productos.nombre")
        ->get();

        return view("etiquetas.imprimirEtiquetas",compact("etiquetasimprimir","arrEtiquetas","productos"));
    }
     public function printEtiquetasTransferencias(Request $request, $id)
    {
        if (!isset($_COOKIE["kiosco"])) 
            {
                header('Location: /');
            }

 $productos=Producto::join("proveedor","proveedor.id", "=", "productos.proveedores_id")
        ->join("relacion_transferencias_productos","relacion_transferencias_productos.producto_id","=","productos.id")
        ->select("productos.*","proveedor.nombre as nombreproveedor","proveedor.apellido","proveedor.ciudad","proveedor.provincia","proveedor.direccion","relacion_transferencias_productos.*")
        ->where('relacion_transferencias_productos.tranferencia_id', '=', $id)
        ->get();

        return view("etiquetas.imprimirEtiquetasTransferencias",compact("productos"));
    }
    public function printQrs(Request $request, $etiquetas)
    {
        if (!isset($_COOKIE["kiosco"])) 
            {
                header('Location: /');
            }
        $etiquetasimprimir=($etiquetas=="")?"":$etiquetas;  
        $arrEtiquetas = explode('-',$etiquetasimprimir);
        $productos = Producto::join("proveedor","proveedor.id", "=", "productos.proveedores_id")
       ->select("proveedor.sitio_web","productos.id")
       ->get();
        return view("etiquetas.imprimirQrs",compact("etiquetasimprimir","arrEtiquetas","productos"));
    }


}
