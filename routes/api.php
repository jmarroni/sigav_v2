<?php

use Illuminate\Http\Request;

Route::group([ 
    'prefix' => 'auth'
], function() {
    Route::get('login', ['uses' => 'Api\AuthController@login'])->name('login'); 
    Route::get('signup', 'Api\AuthController@signup');

    Route::group([ 
      'middleware' => 'auth:api' 
    ], function() {
        Route::get('user', ['uses' =>'Api\AuthController@user']);
        Route::get('productos', ['uses' => 'Api\ProductoController@show']);
        Route::get('logout', ['uses' => 'Api\AuthController@logout']);
    });
});