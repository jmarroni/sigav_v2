<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Models\EstadoTransferencia;
use App\Models\Transferencia_log;
use App\Models\imagen_producto;
use App\Models\RelacionTransferenciaProductos;
use Illuminate\Support\Facades\Storage;
use Image;


class TransferenciaController extends Controller
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
        $sucursales =Sucursales::OrderBy('nombre', 'desc')->get();
        return view("transferencias.accion",compact("mensaje","sucursales"));
    }


    public function save(Request $request)
    {
        $mensaje = "Transferencia guardada exitosamente";  
        $transferencia= new Transferencia();
        $transferencia->sucursal_origen_id=$request->sucursal_origen;
        $transferencia->sucursal_destino_id=$request->sucursal_destino;
        $transferencia->fecha=date("Y-m-d H:i:s");
        $transferencia->estado_id=1;
        //$transferencia->comentario=$request->comentario;
        $transferencia->usuario=$_COOKIE["kiosco"];
        $transferencia->save();
        $arrayproductos = explode("||", $request->arrayproductos);
        foreach ($arrayproductos as $producto)
            {
                $producto_stock = explode(",", $producto);
                $RelacionTransferenciaProductos= new RelacionTransferenciaProductos();
                $RelacionTransferenciaProductos->tranferencia_id=$transferencia->id;
                $RelacionTransferenciaProductos->producto_id=$producto_stock[0];
                $RelacionTransferenciaProductos->cantidad=$producto_stock[1];
                $RelacionTransferenciaProductos->usuario=$_COOKIE["kiosco"];
                $RelacionTransferenciaProductos->save();
            }  //endforeach
        $transferenciaLog= new Transferencia_log();
        $transferenciaLog->sucursal_origen_id=$request->sucursal_origen;
        $transferenciaLog->sucursal_destino_id=$request->sucursal_destino;
        $transferenciaLog->transferencia_id=$transferencia->id;
        $transferenciaLog->usuario=$_COOKIE["kiosco"];
        $transferenciaLog->tipo_operacion="ALTA";
        $transferenciaLog->created_at=date("Y-m-d H:i:s");
        $transferenciaLog->updated_at=date("Y-m-d H:i:s");
        $transferenciaLog->save();

        return redirect('transferencia/mensaje/'.base64_encode($mensaje));
    }

    public function list(Request $request)
    {
        $sucursal_activa = (Sucursales::getSucursal($_COOKIE["sucursal"]) !== null)?Sucursales::getSucursal($_COOKIE["sucursal"]):"";
        $transferenciasRealizadas=Transferencia::join("estado_transferencia as e","e.id","=","transferencias.estado_id")
        ->join("sucursales as so","so.id","=","transferencias.sucursal_origen_id")
        ->join("sucursales as sd","sd.id","=","transferencias.sucursal_destino_id")
        ->where("transferencias.sucursal_origen_id","=",$sucursal_activa,"OR")
        ->ORwhere("transferencias.sucursal_destino_id","=", $sucursal_activa)
        ->addselect("transferencias.*","e.nombre AS nombre_estado","e.id AS id_estado","so.nombre AS sucursal_origen_nombre","sd.nombre AS sucursal_destino_nombre","transferencias.fecha","transferencias.usuario")
        ->OrderBy("transferencias.id")
        ->get();
        $productos=RelacionTransferenciaProductos::join("productos as p","p.id","=","relacion_transferencias_productos.producto_id")
        ->select("relacion_transferencias_productos.*","p.*")
        ->get();
        $imagenes=Imagen_producto::all();
        $estados=EstadoTransferencia::all();
        return view("transferencias.transferenciasRealizadas",compact("sucursal_activa","transferenciasRealizadas","productos","imagenes","estados"));
    }

public function getTransferencia(Request $request, $id)
{ 
  return response()->json(Transferencia::find($id));
}
public function changeStatus(Request $request)
{ 

    $transferencia= Transferencia::find($request->id_transferencia);
    $transferencia->estado_id=$request->id_estado;
    if (isset($request->comentario) && $request->comentario!=null && $request->comentario!="")
       { 
    $transferencia->comentario=$request->comentario;
        }
    $transferencia->save();
    $transferenciaLog= new Transferencia_log();
    $transferenciaLog->sucursal_origen_id=$transferencia->sucursal_origen_id;
    $transferenciaLog->sucursal_destino_id=$transferencia->sucursal_destino_id;
    $transferenciaLog->transferencia_id=$transferencia->id;
    $transferenciaLog->usuario=$_COOKIE["kiosco"];
    $nombreEstado=EstadoTransferencia::find($request->id_estado);
    $transferenciaLog->tipo_operacion="Cambio estatus a ".$nombreEstado->nombre;
    $transferenciaLog->created_at=date("Y-m-d H:i:s");
    $transferenciaLog->updated_at=date("Y-m-d H:i:s");
    $transferenciaLog->save();
    //Se modifica el stock solo si la transferencia es recibida
    if ($request->id_estado==3)
         { 
    $productos=Transferencia::join("relacion_transferencias_productos as rtp","rtp.tranferencia_id","=","transferencias.id")
    ->join("productos as p","p.id","=","rtp.producto_id")
    ->where("transferencias.id","=",$request->id_transferencia)
    ->select("transferencias.sucursal_origen_id", "transferencias.sucursal_destino_id", "rtp.cantidad", "p.id", "p.nombre")
    ->get();
    if (count($productos)>0)
        { 
        foreach ($productos as $producto)
            { 
                //Actualizar stock en origen
                $stockorigen= Stock::where('productos_id',$producto->id)
                        ->where("sucursal_id",$producto->sucursal_origen_id)
                        ->first();
                //echo $stockorigen;
                //exit();
                $stockorigen->stock=$stockorigen->stock-$producto->cantidad;
                $stockorigen->save();
                 //Actualizar stock en destino
                $stockdestino=Stock::where('productos_id', '=', $producto->id)
                        ->where("sucursal_id","=",$producto->sucursal_destino_id)
                        ->first();
                    if($stockdestino!="" && $stockdestino!=null)
                        {
                            $stockdestino->stock=$stockdestino->stock+$producto->cantidad;
                            $stockdestino->save();
                        }
                    else
                        {
                            $stockdestino=new Stock();
                            $stockdestino->sucursal_id     = $producto->sucursal_destino_id;
                            $stockdestino->productos_id    = $producto->id;
                            $stockdestino->stock           = $producto->cantidad;
                            $stockdestino->stock_minimo    = 1;
                            $stockdestino->usuario         = $_COOKIE["kiosco"]; 
                            $stockdestino->created_at      = date("Y-m-d H:i:s");    
                            $stockdestino->updated_at      = date("Y-m-d H:i:s");           
                            $stockdestino->save();
                        }
            }
    } 

}
$mensaje = 'Estatus cambiando con éxito';
return response()->json(array("proceso" => "OK"));
}

}
