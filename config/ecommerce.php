<?php

/*
 * Sucursal de la que el e-commerce ve y descuenta stock (fila de la tabla `stock`).
 * Se define en .env con ECOMMERCE_SUCURSAL_ID. Sin valor, todo figura con stock 0
 * y las ventas se rechazan (falla cerrada, no se vende de más).
 */
return [
    'sucursal_id' => env('ECOMMERCE_SUCURSAL_ID'),
];
