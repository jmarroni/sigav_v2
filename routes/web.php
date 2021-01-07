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
Route::resource('carga', 'ProductoController');
Route::get('carga/mensaje/{mensaje}','ProductoController@index');
Route::get('producto.actualizar.stock/{id}/{stock}/{stock_minimo}/{sucursal}','ProductoController@update_stock' );
Route::get('producto.eliminar.stock/{id}/{stock}/{stock_minimo}/{sucursal}','ProductoController@delete_stock' );
//Proveedor
Route::get('proveedor', 'ProveedorController@index');
Route::post('proveedor.save', 'ProveedorController@save');
Route::get('proveedor/mensaje/{mensaje}','ProveedorController@index');
Route::get('proveedor.delete/{id}','ProveedorController@delete' );
Route::get('proveedor.checkProducts/{id}', 'ProveedorController@checkProducts');
Route::get('proveedor.getProveedor/{id}', 'ProveedorController@getProveedor');
Route::get('getCategoriasProveedor/{id}', 'ProveedorController@getCategoriasProveedor');

Route::get('tipo/{type}', 'SweetController@notification');
Route::get('reporte.factura/{desde?}/{hasta?}', 'ReporteController@factura');