## Laravel Passport: Instalación

1) Poner en la linea de comandos: 'composer require laravel/passport'

2) Agregar a config/app.php: 'Laravel\Passport\PassportServiceProvider::class,'.

3) Poner en la linea de comandos 'php artisan migrate'.

Opcional:
	Si el anterior paso no funciona hacer: php artisan migrate --path=vendor/laravel/passport/database/migrations

4) Agregar "Laravel\Passport\HasApiTokens" y "use Notifiable, HasApiTokens;" al modelo "App\User".

5) Agregar "use Laravel\Passport\Passport;" a AuthServiceProvider.php y "Passport::routes();" a la funcion boot.

6) Agregar a config/auth.php: 'driver' => 'passport'

7) Crear claves de cifrado necesarias para generar tokens de acceso seguro: 'php artisan passport:install'.

8) Agregar a routes/api.php:
Route::group([
    'prefix' => 'auth' 
], function () { 
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');

    Route::group([
      'middleware' => 'auth: api'
    ], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
    });
});

9) Crear carpeta 'api' en controllers y generar controlador 'php artisan make:Controlller AuthController' y poner los datos que se encuentran en el archivo de esta aplicación.

10) Despliegue, generar llaves: php artisan passport:keys

## Api SIGAV

Obtención de token:
    0001 - Token de acceso:

		Para poder utilizar la aplicación es necesario un token (el cual es válido por un dia) que se le pedira para todas las futuras peticiones que realizará a la api.
	Para obtenerlo debera ingresar a la URL:
		http://mercado-artesanal.com/api/auth/login?email=ejemplo@hotmail.com&password=123
	Donde el email y el password seran la informacion que usted nos proporciono con anterioridad.
	Como resultado obtendrá:
		{"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImUzNjhiN2U0MzM5OTU3YjNhYTNkZTQ5MDUxMjJjM2","token_type":"Bearer","expires_at":"2019-11-05 11:30:32"}
	Su token será el "access_token" por ello copielo, por otro lado tiene el tipo de token y la fecha de expiración del mismo.

Obtención de productos:
	Para obtenerlo deberá ingresar a la URL:
		http://mercado-artesanal.com/api/auth/productos?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImUzNjhiN2U0MzM5OTU3YjNhYTNkZTQ5MDUxMjJjM2
	En su aplicación deberá enviar el siguiente header (Ejemplo realizado con jquery AJAX):
		headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    }
	Como resultado obtendra un JSON con todos los datos de los productos, su categoria, su proveedor e imagenes, como se muestra a continuación:
		[{0: apellido_proveedor: "apellido", categoria: "categoria", codigo_barras: "123", costo: "0", es_comodato: 0, fecha: "2019-01-09 11:48:01", imagen: "http://mercado-artesanal.com.ar/imagen/1438.jpg", nombre: "producto", nombre_proveedor: "ejemplo", precio_mayorista: "0", precio_unidad: "10", stock: 2, stock_minimo: 0, usuario: "pepe"}
		{1: apellido_proveedor: "apellido", categoria: "categoria", codigo_barras: "123", costo: "0", es_comodato: 0, fecha: "2019-01-09 11:48:01", imagen: "http://mercado-artesanal.com.ar/imagen/1439.jpg", nombre: "producto", nombre_proveedor: "ejemplo", precio_mayorista: "0", precio_unidad: "10", stock: 2, stock_minimo: 0, usuario: "pepe"}]
