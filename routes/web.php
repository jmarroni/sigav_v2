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

<<<<<<< HEAD
Route::get('/loginejemplo', function(){
	return view('login/login');
});

Route::get('/test_passsport', "TestPassportController@login_logout");
=======
Route::get('signup', 'Api\AuthController@signup');
>>>>>>> cbe5c9a8694a979f6e2abd6675b5c0d2ac458b7c
