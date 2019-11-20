<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect ("/login.php");
});

Route::get('/loginejemplo', function(){
	return view('login/login');
});

Route::get('signup', ['uses' =>'Api\AuthController@signup']);