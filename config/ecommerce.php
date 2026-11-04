<?php

return [
    /*
     * Sucursal de la que el e-commerce ve y descuenta stock (fila de la tabla `stock`).
     * Se define en .env con ECOMMERCE_SUCURSAL_ID. Sin valor, todo figura con stock 0
     * y las ventas se rechazan (falla cerrada, no se vende de más).
     */
    'sucursal_id' => env('ECOMMERCE_SUCURSAL_ID'),

    /*
     * Recargo (% entero, 0 a 100) sobre precio_unidad para lo que se publica y se vende
     * por la API de e-commerce. El resultado se redondea al peso entero más cercano.
     * Un valor fuera de rango o no entero corta la API con error (no publica precios mal).
     */
    'recargo_online_pct' => env('ECOMMERCE_RECARGO_PCT', 10),
];
