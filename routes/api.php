<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {
<<<<<<< HEAD
    Route::get('login', ['uses' => 'Api\AuthController@login'])->name('login'); 
    Route::get('signup', 'Api\AuthController@signup');
=======
    Route::get('login', ['uses' => 'Api\AuthController@login']); 
>>>>>>> cbe5c9a8694a979f6e2abd6675b5c0d2ac458b7c

    Route::group([ 
      'middleware' => 'auth:api' 
    ], function() {
        Route::get('user', ['uses' =>'Api\AuthController@user']);
        Route::get('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::get('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::get('productosPorSucursal', ['uses' => 'Api\SucursalesController@productosPorSucursal']);
    });
});