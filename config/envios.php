<?php

/*
 * Tamaños de envío predefinidos para productos (campo productos.envio_tamano).
 * Medidas en cm (largo × ancho × alto) y peso en gramos. Lo consume la API de
 * productos para el e-commerce; el alta/edición de producto sólo guarda el número.
 */
return [
    'tamanos' => [
        1 => ['nombre' => 'Chico',      'largo_cm' => 20, 'ancho_cm' => 15, 'alto_cm' => 5,  'peso_gramos' => 300,  'etiqueta' => '20 × 15 × 5 cm · 300 g', 'ejemplos' => 'Aros, collares, billeteras'],
        2 => ['nombre' => 'Mediano',    'largo_cm' => 25, 'ancho_cm' => 20, 'alto_cm' => 15, 'peso_gramos' => 1000, 'etiqueta' => '25 × 20 × 15 cm · 1 kg', 'ejemplos' => 'Mates, velas, tazas, cuencos, cuchillos'],
        3 => ['nombre' => 'Grande',     'largo_cm' => 40, 'ancho_cm' => 30, 'alto_cm' => 20, 'peso_gramos' => 3000, 'etiqueta' => '40 × 30 × 20 cm · 3 kg', 'ejemplos' => 'Floreros, bolsos, mochilas, tablas de asado, set matero con termo'],
        4 => ['nombre' => 'Voluminoso', 'largo_cm' => 50, 'ancho_cm' => 40, 'alto_cm' => 30, 'peso_gramos' => 2500, 'etiqueta' => '50 × 40 × 30 cm · 2,5 kg', 'ejemplos' => 'Ruanas, mantas, tejidos'],
        5 => ['nombre' => 'Especial',   'largo_cm' => 80, 'ancho_cm' => 50, 'alto_cm' => 40, 'peso_gramos' => 8000, 'etiqueta' => '80 × 50 × 40 cm · 8 kg', 'ejemplos' => 'Alfombras y piezas grandes'],
    ],
];
