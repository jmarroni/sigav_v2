<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {

    Route::post('login', ['uses' => 'Api\AuthController@login']); 

    Route::group([ 
      'middleware' => ['auth:api', 'cors']
    ], function() {
        Route::post('user', ['uses' =>'Api\AuthController@user']);
        Route::post('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::post('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::post('productosPorSucursal', ['uses' => 'Api\SucursalesController@productosPorSucursal']);
    });
});