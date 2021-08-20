<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {

    Route::get('login', ['uses' => 'Api\AuthController@index']); 
    Route::post('validaracceso', ['uses' => 'Api\AuthController@login']); 
    Route::post('signup', ['uses' => 'Api\AuthController@signup']); 

    //Ruta acceso servidor/api/auth/nombre
    Route::group([ 
      'middleware' => ['auth:api', 'cors']
    ], function() {
        Route::post('user', ['uses' =>'Api\AuthController@user']);
        Route::post('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::post('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::post('productosPorSucursal', ['uses' => 'Api\ProductoController@getProductosPorSucursal']);

    });
});