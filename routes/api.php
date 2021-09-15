<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {

    Route::get('login', ['uses' => 'Api\AuthController@index']); 
    Route::post('validaracceso', ['uses' => 'Api\AuthController@login']); 
    Route::post('signup', ['uses' => 'Api\AuthController@signup']); 


    Route::group([ 
      'middleware' => ['auth:api', 'cors']
    ], function() {
        Route::post('user', ['uses' =>'Api\AuthController@user']);
        Route::post('productos', ['uses' => 'Api\ProductoController@productos']);
        Route::post('sucursales', ['uses' => 'Api\SucursalesController@sucursales']);
        Route::post('productosPorSucursal', ['uses' => 'Api\ProductoController@getProductosPorSucursal']);
        Route::post('actualizarStock', ['uses' => 'Api\ProductoController@updateStock']);
        Route::post('consultarClientes', ['uses' => 'Api\ClienteController@consultarClientes']);
        Route::post('altaCliente', ['uses' => 'Api\ClienteController@altaCliente']);
        Route::post('guardarPedido', ['uses' => 'Api\PedidoController@savePedido']);
    });
});