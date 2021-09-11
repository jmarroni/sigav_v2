<?php
namespace App\Traits;
use App\Models\Usuario;


trait Usuarios {

   //Función para consultar stock de un producto
   public function obtenerUsuarioByID($id)
   {
     $usuario=Usuario::where("usuarios.id","=",$id)->first();
     if ($usuario!=null)
        return $usuario->usuario;
     else
      return "";

}
}