<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Producto;

use App\Models\Stock;
use App\Models\Stock_log;

class ProductoController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getProductosPorSucursal(Request $request)
    {
        /* $productos = DB::table('productos')->
                join('categorias', 'categorias.id', 'productos.categorias_id')->
                join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                join('imagen_producto', 'imagen_producto.productos_id', 'productos.id')->
                select('productos.codigo_barras', 'productos.nombre', 'productos.precio_unidad', 'productos.costo', 'productos.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'imagen_producto.imagen_url AS imagen', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
                ->get(); */

                $productos = DB::table('productos')->
                join('categorias', 'categorias.id', 'productos.categorias_id')->
                join('proveedor', 'proveedor.id', 'productos.proveedores_id')->
                join('stock','stock.productos_id','productos.id')
                ->where("stock.sucursal_id",$request->sucursal_id)
                ->where("stock.stock",">","0")
                ->select('productos.id', 'productos.codigo_barras', 'productos.nombre', 'productos.descripcion','productos.precio_unidad', 'productos.costo','stock.stock', 'productos.stock_minimo', 'productos.usuario', 'productos.fecha', 'productos.precio_mayorista', 'productos.es_comodato', 'categorias.nombre AS categoria', 'proveedor.nombre AS nombre_proveedor', 'proveedor.apellido AS apellido_proveedor')
                ->OrderBy("productos.nombre")
                ->get();

                $imagenes = DB::table('imagen_producto')->
                select('imagen_producto.imagen_url', 'imagen_producto.productos_id')
                ->get();

                foreach ($productos as $producto) {
                    $array_imagenes = array();
                    foreach ($imagenes as $imagen) {
                        if ( $producto->id == $imagen->productos_id ) {
                            array_push($array_imagenes, $imagen->imagen_url);
                        }
                    }
                    $producto->id = null;
                    $producto->imagenes = $array_imagenes;
                }

                return response()->json($productos, 201);
            }

            public function updateStock(Request $request)
            {
                $arrayErrores=array();
                $sucursal=$request->sucursal_id;
                $usuario=$request->usuario_id;
                $stockActual=0;
                $error="";
                $productos = json_decode($request->arrayProductos, true);
                //Se validan que existan productos a actualizar
                if(count($productos)>0)
                {
                    $i=0;
                    foreach($productos as $producto)
                    {
                    //print_r($producto);
                        $idproducto= $producto['id'];
                        $cantidad=$producto['cantidad'];
                    //Se valida si existe registro en la tabla stock para ese producto
                        $stock = Stock::where("productos_id",'=',$idproducto)->where("sucursal_id",'=',$sucursal)->first();
                        if ($stock=="")
                        {
                            $arrayErrores[$i]['id']=$idproducto;
                            $arrayErrores[$i]['error']="No existe registro en la tabla stock para este producto";
                        }
                        else
                        {
                            $stockActual=$this->consultarStockProducto($idproducto,$sucursal);

                            $stockActual=$stockActual[0]->stock;
                            if ($stockActual>= $cantidad)
                            {
                                $stock_logs = new Stock_log();
                                $stock_logs->stock_anterior         = $stock->stock;
                                $stock_logs->stock_minimo_anterior  = $stock->stock_minimo;
                                $stock->stock= $stock->stock- $cantidad;
                                $stock->save();
                                $stock_logs->productos_id   = $idproducto;
                                $stock_logs->sucursal_id    = $sucursal;
                                $stock_logs->stock=$stock->stock;                          
                                $stock_logs->stock_minimo   = $stock->stock_minimo;
                                $stock_logs->usuario        = $usuario;
                                $stock_logs->tipo_operacion = 'Venta móvil';
                                $stock_logs->updated_at = date("Y-m-d H:i:s");
                                $stock_logs->created_at = date("Y-m-d H:i:s");
                                $stock_logs->save();
                            }/*End if*/
                            else/*Error porque no se puede descontar mas inventario que el disponible*/
                            {
                               $arrayErrores[$i]['id']=$idproducto;
                               $arrayErrores[$i]['error']="Cantidad a descontar mayor a stock disponible";
                           }/*End else*/
                       }/*End else*/

                       $i=$i+1;
                   }/*end foreach*/
               }/*End if*/
               if(count($arrayErrores)>0)
               {
                return response()->json(array("resultado" => $arrayErrores));
            }
            else
            {
             return response()->json(array("resultado" => "OK"));
         }

             //return response()->json($arrayErrores, 201);
     }/*End function*/

         //Función para consultar stock de un producto

     public function consultarStockProducto($producto_id,$sucursal_id)
     {
       $sucursal = $sucursal_id;
       $producto=$producto_id;
       $stock=Producto::join("stock","stock.productos_id", "=", "productos.id")
       ->join("sucursales","sucursales.id", "=", "stock.sucursal_id")
       ->where("stock.sucursal_id","=",$sucursal)
       ->where("stock.productos_id","=",$producto)
       ->select("stock.stock")
       ->get();
       return $stock;

   }


}
