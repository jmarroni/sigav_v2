<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'cert',
        'key',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Un POST que supera post_max_size (típico: varias fotos de celular en el alta
        // de producto) llegaba como "413 Content Too Large" genérico. Se vuelve al
        // formulario con un mensaje legible en vez de la página de error.
        // Solo para formularios web: la API sigue devolviendo 413.
        if ($exception instanceof PostTooLargeException && ! $request->expectsJson() && ! $request->is('api/*')) {
            $limite = ini_get('post_max_size');
            return redirect()->back()->with('error_carga',
                'No se pudo guardar: el envío supera el tamaño máximo permitido ('.$limite.' en total). '
                .'Reducí el tamaño de las imágenes o subí menos imágenes por vez.');
        }

        return parent::render($request, $exception);
    }
}
