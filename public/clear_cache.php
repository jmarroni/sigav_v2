<?php
// Script temporal para limpiar opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache limpiado exitosamente.\n";
} else {
    echo "Opcache no disponible.\n";
}

// También invalidar específicamente facturar.php
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__DIR__ . '/facturar.php', true);
    echo "facturar.php invalidado del cache.\n";
}

echo "Listo. Ahora prueba facturar nuevamente.";
