<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {

    Route::post('login', ['uses' => 'Api\AuthController@login'])->middleware('throttle:10,1');

    Route::group([ 
      // CORS lo aplica HandleCors (global) según config/cors.php.
      'middleware' => ['auth:api']
    ], function() {
        Route::post('user', ['uses' =>'Api\AuthController@user']);
        Route::post('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::post('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::post('productosPorSucursal', ['uses' => 'Api\SucursalesController@productosPorSucursal']);
    });
});