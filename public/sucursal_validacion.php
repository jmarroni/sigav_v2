<?php
/**
 * Validación del alta/edición de sucursal (lo usa sucursales_post.php).
 * Funciones puras, sin conexión ni side effects, para testearlas con PHPUnit.
 *
 * El código postal es obligatorio: la API del e-commerce lo informa como origen
 * del envío (ver Api\ProductoController::sucursalesConStock).
 */

if (! function_exists('sucursal_normalizar_cp')) {
    /**
     * Código postal argentino: 4 dígitos (8500) o CPA (R8500ABC).
     * Devuelve el CP sin espacios y en mayúsculas, o null si es inválido.
     */
    function sucursal_normalizar_cp($cp)
    {
        if (! is_string($cp)) {
            return null;
        }
        $cp = strtoupper(trim($cp));
        if (preg_match('/^(\d{4}|[A-Z]\d{4}[A-Z]{3})$/', $cp) !== 1) {
            return null;
        }
        return $cp;
    }
}

if (! function_exists('sucursal_error_validacion')) {
    /** Mensaje de error para mostrar al usuario, o null si los datos son válidos. */
    function sucursal_error_validacion(array $datos)
    {
        $errores = [];
        if (trim((string) ($datos['nombre'] ?? '')) === '') {
            $errores[] = 'El nombre de la sucursal es obligatorio.';
        }
        if (sucursal_normalizar_cp($datos['codigo_postal'] ?? null) === null) {
            $errores[] = 'El código postal es obligatorio (4 dígitos, ej. 8500, o CPA, ej. R8500ABC).';
        }
        return count($errores) > 0 ? implode(' ', $errores) : null;
    }
}
