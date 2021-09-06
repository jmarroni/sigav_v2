<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ProductosStock;
use App\Traits\Usuarios;

class Pedido extends Model
{
    use ProductosStock;
    use Usuarios;
    
    protected $table ='pedidos';
    public $timestamps = false;
}
