<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
	protected $table ='roles';
	public $timestamps = false;
	
	public static function getRol(){

		$semilla = config('app.legacy_semilla');

		for ($i=0; $i < 99; $i++) {
			if (sha1($semilla.$i.$semilla)  == $_COOKIE["rol"]) return $i;
		}

		exit();
	}

}
