<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {
    Route::get('login', ['uses' => 'Api\AuthController@login']); 

    Route::group([ 
      'middleware' => 'auth:api' 
    ], function() {
        Route::get('user', ['uses' =>'Api\AuthController@user']);
        Route::get('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::get('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::get('productosPorSucursal', ['uses' => 'Api\SucursalesController@productosPorSucursal']);
    });
});