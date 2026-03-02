<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
	protected $table ='roles';
	public $timestamps = false;
	
	public static function getRol(){


		for ($i=0; $i < 99; $i++) { 
			if (sha1("$%Reset20122017AnnaLuca#^".$i."$%Reset20122017AnnaLuca#^")  == $_COOKIE["rol"]) return $i;
		}
		
		exit();
	}

}
