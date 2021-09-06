<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\Stock_log;
use App\Models\Pedido;
use App\Models\DetallePedido;

class PedidoController extends Controller
{
   

 public function savePedido(Request $request)
    {
                $arrayErrores=array();
                $sucursal=$request->sucursal_id;
                $usuario=$request->usuario_id;
                $cliente=$request->cliente_id;
                $stockActual=0;
                $error="";
                $monto=0;
                $productos = json_decode($request->arrayProductos, true);
                //Se validan que existan productos en el pedido
                if(count($productos)>0)
                {
                    $i=0;
                    $pedidoCabecera=new Pedido();
                    $pedidoCabecera->id_sucursal=$sucursal;
                    $pedidoCabecera->id_cliente=$cliente;
                    $pedidoCabecera->id_usuario=$usuario;
                    $pedidoCabecera->fecha=date("Y-m-d H:i:s");
                    $pedidoCabecera->monto=0;
                    $pedidoCabecera->estado=0;
                    $pedidoCabecera->save();
                    foreach($productos as $producto)
                    {
                    //print_r($producto);
                        $idproducto= $producto['id'];
                        $cantidad=$producto['cantidad'];
                        $costo=$producto['costo'];
                        $precio=$producto['precio'];
                    //Se valida si existe registro en la tabla stock para ese producto
                        $stock = Stock::where("productos_id",'=',$idproducto)->where("sucursal_id",'=',$sucursal)->first();
                        if ($stock=="")
                        {
                            $arrayErrores[$i]['id']=$idproducto;
                            $arrayErrores[$i]['error']="No existe registro en la tabla stock para este producto";
                        }
                        else
                        {
                            $objPedido= new Pedido();
                            $detallePedido=new DetallePedido();
                            $stockActual=$objPedido->consultarStockProducto($idproducto,$sucursal);
                            $nomUsuario=$objPedido->obtenerUsuarioByID($usuario);
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
                                $stock_logs->usuario        = $nomUsuario;
                                $stock_logs->tipo_operacion = 'Pedido móvil';
                                $stock_logs->updated_at = date("Y-m-d H:i:s");
                                $stock_logs->created_at = date("Y-m-d H:i:s");
                                $stock_logs->save();
                                $detallePedido->id_pedido=$pedidoCabecera->id;
                                $detallePedido->id_producto=$idproducto;
                                $detallePedido->cantidad=$cantidad;
                                $detallePedido->costo=$costo;
                                $detallePedido->precio=$precio;
                                $detallePedido->save();
                                $subtotal=$costo*$cantidad;
                                $monto=$monto+$subtotal;

                               // $pedido
                            }/*End if*/
                            else/*Error porque no se puede descontar mas inventario que el disponible*/
                            {
                               $arrayErrores[$i]['id']=$idproducto;
                               $arrayErrores[$i]['error']="Cantidad a descontar mayor a stock disponible";
                           }/*End else*/
                       }/*End else*/

                       $i=$i+1;
                   }/*end foreach*/
                   $pedidoCabecera->monto=$monto;
                   $pedidoCabecera->save();
               }/*End if*/
               if(count($arrayErrores)>0)
               {
                return response()->json(array("resultado" => $arrayErrores));
            }
            else
            {
             return response()->json(array("resultado" => "OK"));
         }

}

}
