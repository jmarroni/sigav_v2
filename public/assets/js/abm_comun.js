/*
 * Helpers compartidos por las pantallas ABM con el UI kit (.sigav-app):
 * categorías y proveedores. Usa jQuery (header.php) y SweetAlert 1.x (footer.php).
 */
var Abm = (function ($) {
    function csrf() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
    }

    /** Petición AJAX con CSRF; siempre resuelve con JSON o rechaza. */
    function pedir(opciones) {
        return $.ajax($.extend({ dataType: 'json', headers: { 'X-CSRF-TOKEN': csrf() } }, opciones));
    }

    function aviso(tipo, titulo, texto) {
        swal({ title: titulo, text: texto || '', type: tipo, confirmButtonText: 'Listo' });
    }

    /** Confirmación destructiva: ejecuta alConfirmar solo si el usuario acepta. */
    function confirmar(titulo, texto, alConfirmar) {
        swal({
            title: titulo,
            text: texto,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d64545',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            closeOnConfirm: true
        }, function (ok) { if (ok) { alConfirmar(); } });
    }

    /** Quita una fila con animación y actualiza el contador. */
    function quitarFila($fila, $contador) {
        $fila.addClass('is-leaving');
        setTimeout(function () {
            $fila.remove();
            if ($contador && $contador.length) {
                $contador.text(Math.max(0, parseInt($contador.text(), 10) - 1));
            }
        }, 260);
    }

    /** Filtro en vivo sobre las filas con data-buscar. */
    function buscador($input, $tabla, $sinResultados) {
        $input.on('input', function () {
            var q = $.trim($(this).val()).toLowerCase();
            var visibles = 0;
            $tabla.find('tbody tr[data-buscar]').each(function () {
                var coincide = q === '' || String($(this).data('buscar')).indexOf(q) !== -1;
                $(this).toggle(coincide);
                if (coincide) { visibles++; }
            });
            $sinResultados.toggleClass('sg-hidden', visibles > 0 || q === '');
        });
    }

    /** Alterna la tarjeta del formulario entre "alta" y "edición". */
    function modoEdicion(cfg, editando, nombre) {
        cfg.$card.toggleClass('is-editing', editando);
        cfg.$titulo.text(editando ? cfg.tituloEdicion : cfg.tituloAlta);
        cfg.$nota.text(editando && nombre ? 'Editando: ' + nombre : '');
        cfg.$cancelar.toggleClass('sg-hidden', !editando);
        cfg.$enviar.find('span').text(editando ? 'Guardar cambios' : cfg.textoAlta);
        cfg.$tabla.find('tr.is-selected').removeClass('is-selected');
        if (editando) {
            $('html, body').animate({ scrollTop: cfg.$card.offset().top - 70 }, 250);
        }
    }

    function ocultarAvisoExito() {
        setTimeout(function () { $('#add_success').slideUp(250); }, 4000);
    }

    return {
        pedir: pedir,
        aviso: aviso,
        confirmar: confirmar,
        quitarFila: quitarFila,
        buscador: buscador,
        modoEdicion: modoEdicion,
        ocultarAvisoExito: ocultarAvisoExito
    };
})(jQuery);
