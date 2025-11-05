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

//para actualizar el enlace simbólico a la carpeta storage
// SEGURIDAD: Protegido con middleware de autenticación
Route::get('/updateStorage', function(){
  Artisan::call('storage:link');
  return 'Storage link actualizado';
})->middleware('auth');

Route::get('/loginejemplo', function(){
	return view('login/login');
});

Route::get('signup', ['uses' =>'Api\AuthController@signup']);
//Producto
Route::resource('carga', 'ProductoController');
Route::get('carga/mensaje/{mensaje}','ProductoController@index');
Route::get('consultarStock/sucursal/{sucursal}','ProductoController@consultarStock');
// SEGURIDAD: Cambiado de GET a POST/DELETE para prevenir ataques CSRF
Route::post('producto.actualizar.stock/{id}','ProductoController@update_stock' );
Route::delete('producto.eliminar.stock/{id}','ProductoController@delete_stock' );
Route::get('buscarProductos', 'ProductoController@searchProductsSinStock');
//Proveedor
Route::get('proveedor', 'ProveedorController@index');
Route::post('proveedor.save', 'ProveedorController@save');
Route::get('proveedor/mensaje/{mensaje}','ProveedorController@index');
// SEGURIDAD: Cambiado de GET a DELETE para prevenir ataques CSRF
Route::delete('proveedor/{id}','ProveedorController@delete' );
Route::get('proveedor.checkProducts/{id}', 'ProveedorController@checkProducts');
Route::get('proveedor.getProveedor/{id}', 'ProveedorController@getProveedor');
Route::get('proveedor.getCategoriasProveedor/{id}', 'ProveedorController@getCategoriasProveedor');
Route::get('pagoProveedores', 'ProveedorController@indexPagoProveedores');
Route::get('pagoProveedores/mensaje/{mensaje}', 'ProveedorController@indexPagoProveedores');
Route::post('pagoProveedores.saveFactura', 'ProveedorController@saveFactura');
//Categoría
Route::get('categoria', 'CategoriaController@index');
Route::post('categoria.save', 'CategoriaController@save');
Route::get('categoria.getCategoria/{id}', 'CategoriaController@getCategoria');
Route::get('categoria.checkProducts/{id}', 'CategoriaController@checkProducts');
// SEGURIDAD: Cambiado de GET a DELETE/POST para prevenir ataques CSRF
Route::delete('categoria/{id}','CategoriaController@delete' );
Route::post('categoria.changeStatus/{id}','CategoriaController@changeStatus' );
Route::get('categoria/mensaje/{mensaje}','CategoriaController@index');
//Impresión de etiquetas
Route::get('etiqueta', 'EtiquetaController@index');
Route::get('etiqueta.getQr/{id}', 'EtiquetaController@getQr');
Route::get('etiqueta.buscarProductos', 'ProductoController@searchProducts');
Route::get('etiqueta.imprimirEtiquetas/{etiquetas}', 'EtiquetaController@printEtiquetas');
Route::get('etiqueta.imprimirQrs/{etiquetas}', 'EtiquetaController@printQrs');
Route::get('etiqueta.imprimirEtiquetasTransferencias/{id}', 'EtiquetaController@printEtiquetasTransferencias');
//Roles
Route::get('rol', 'RolController@index');
Route::post('rol.save', 'RolController@save');
Route::get('rol/mensaje/{mensaje}','RolController@index');
// SEGURIDAD: Cambiado de GET a DELETE para prevenir ataques CSRF
Route::delete('rol/{id}','RolController@delete' );
//Usuarios
Route::get('usuario', 'UsuarioController@index');
Route::post('usuario.save', 'UsuarioController@save');
Route::get('usuario/mensaje/{mensaje}','UsuarioController@index');
// SEGURIDAD: Cambiado de GET a DELETE para prevenir ataques CSRF
Route::delete('usuario/{id}','UsuarioController@delete' );
Route::get('usuario.getUsuario/{id}', 'UsuarioController@getUsuario');
//Transferencias sucursales
Route::get('transferencia', 'TransferenciaController@index');
//Route::post('transferencia.save', 'TransferenciaController@save');
Route::post('transferencia-saving', 'TransferenciaController@saving');
Route::get('transferencia/mensaje/{mensaje}','TransferenciaController@index');
Route::get('transferencia.cambiarstatus', 'TransferenciaController@changeStatus');
Route::get('transferencias.realizadas', 'TransferenciaController@listar');
Route::get('transferencias.realizadas/mensaje/{mensaje}', 'TransferenciaController@listar');
//Reportes
Route::get('cierreCajaReporte/mensaje/{mensaje}', 'ReporteController@cierreCajaReporte');
Route::get('cierreCajaReporte', 'ReporteController@cierreCajaReporte');
Route::post('cierreCajaAccion', 'ReporteController@cierreCajaAccion');
Route::get('logsProductos', 'ReporteController@logProductos');
Route::get('logsCategorias', 'ReporteController@logCategorias');
Route::get('logsTransferencias', 'ReporteController@logTransferencias');
Route::get('reporte.presupuesto', 'ReporteController@reportePresupuesto');
Route::get('reporte.notasCredito', 'ReporteController@reporteNotasCredito');
Route::get('reporte.stocks', 'ReporteController@reporteStock');
Route::get('reporte.pagoProveedores', 'ReporteController@reportePagoProveedores');
Route::get('reporte.transferencias', 'ReporteController@reporteTransferencias');
Route::get('reporte.factura/{desde?}/{hasta?}', 'ReporteController@factura');
Route::get('reporte.pedidos', 'ReporteController@reportePedidos');
Route::get('logsProductosCostosPrecios', 'ReporteController@logProductosCostosPrecios');
//Clientes
Route::get('cliente', 'ClienteController@index');
Route::get('cliente/mensaje/{mensaje}', 'ClienteController@index');
Route::post('cliente.save', 'ClienteController@save');
// SEGURIDAD: Cambiado de GET a DELETE para prevenir ataques CSRF
Route::delete('cliente/{id}','ClienteController@delete' );
Route::get('cliente.getCliente/{id}', 'ClienteController@getCliente');
Route::get('pedido', 'PedidoController@index');
Route::post('pedido.save', 'PedidoController@save');


Route::get('tipo/{type}', 'SweetController@notification');
