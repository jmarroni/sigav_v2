<?php
namespace App\Traits;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\Stock_log;

trait ProductosStock {

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