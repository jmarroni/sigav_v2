<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestPassportController extends Controller
{
    public function login_logout(){
    	$url = "http://127.0.0.1:8000/api/auth/login?email=jmarroni@gmail.com&password=130702uade";

    	echo file_get_contents($url);
		exit();
    }
}
