/*
 * ABM de categorías (/categoria).
 * Rutas (routes/web.php): POST /categoria.save, GET /categoria.getCategoria/{id},
 * GET /categoria.checkProducts/{id}, POST /categoria.changeStatus/{id}, DELETE /categoria/{id}.
 */
jQuery(function ($) {
    var $tabla = $('#tabla-categorias');
    var $form = $('#form-categoria');
    var form = {
        $card: $('#card-form'),
        $titulo: $('#form-titulo'),
        $nota: $('#form-nota'),
        $cancelar: $('#cancelar'),
        $enviar: $('#enviar'),
        $tabla: $tabla,
        tituloAlta: 'Nueva categoría',
        tituloEdicion: 'Modificar categoría',
        textoAlta: 'Guardar categoría'
    };

    Abm.ocultarAvisoExito();
    Abm.buscador($('#buscar'), $tabla, $('#sin-resultados'));

    // Si el servidor rechazó una edición, el formulario vuelve en modo edición.
    if ($('#id_categoria').val()) {
        Abm.modoEdicion(form, true, $('#nombre').val());
    }

    $form.on('submit', function (event) {
        if ($.trim($('#nombre').val()) === '' || $.trim($('#abreviatura').val()) === '') {
            event.preventDefault();
            Abm.aviso('warning', 'Verificar', 'Completá el nombre y la abreviatura.');
            return;
        }
        form.$enviar.prop('disabled', true);
    });

    form.$cancelar.on('click', limpiarFormulario);

    $tabla.on('click', '[data-accion="editar"]', function () { editar($(this).data('id')); });
    $tabla.on('click', '[data-accion="eliminar"]', function () { eliminar($(this).data('id'), $(this).data('nombre')); });
    $tabla.on('click', '[data-accion="estado"]', function () { cambiarEstado($(this)); });

    function limpiarFormulario() {
        $form[0].reset();
        $('#id_categoria').val('');
        Abm.modoEdicion(form, false);
    }

    function editar(id) {
        Abm.pedir({ url: '/categoria.getCategoria/' + id, type: 'GET' })
            .done(function (data) {
                if (!data) { return Abm.aviso('error', 'Error', 'La categoría ya no existe.'); }
                $('#id_categoria').val(data.id);
                $('#nombre').val(data.nombre);
                $('#abreviatura').val(data.abreviatura);
                Abm.modoEdicion(form, true, data.nombre);
                $('#' + id).addClass('is-selected');
                $('#nombre').focus();
            })
            .fail(function () { Abm.aviso('error', 'Error', 'No se pudo cargar la categoría.'); });
    }

    function eliminar(id, nombre) {
        Abm.confirmar('¿Eliminar "' + nombre + '"?', 'Esta acción no se puede deshacer.', function () {
            Abm.pedir({ url: '/categoria.checkProducts/' + id, type: 'GET' })
                .done(function (check) {
                    if (check.proceso === 'FAIL') {
                        return Abm.aviso('error', 'No se puede eliminar', 'Hay productos o proveedores asociados a esta categoría. Podés deshabilitarla.');
                    }
                    Abm.pedir({ url: '/categoria/' + id, type: 'DELETE' })
                        .done(function (data) {
                            if (data.proceso !== 'OK') { return Abm.aviso('error', 'Error', 'No se pudo eliminar la categoría.'); }
                            if ($('#id_categoria').val() == id) { limpiarFormulario(); }
                            Abm.quitarFila($('#' + id), $('#contador'));
                        })
                        .fail(function () { Abm.aviso('error', 'Error', 'No se pudo eliminar la categoría.'); });
                })
                .fail(function () { Abm.aviso('error', 'Error', 'No se pudo verificar la categoría.'); });
        });
    }

    function cambiarEstado($pill) {
        var id = $pill.data('id');
        $pill.prop('disabled', true);
        Abm.pedir({ url: '/categoria.changeStatus/' + id, type: 'POST' })
            .done(function (data) {
                if (data.proceso !== 'OK') { return Abm.aviso('error', 'Error', 'No se pudo cambiar el estado.'); }
                var habilitada = parseInt(data.status, 10) === 1;
                $pill.toggleClass('sg-pill--on', habilitada).toggleClass('sg-pill--off', !habilitada)
                    .text(habilitada ? 'Habilitada' : 'Deshabilitada')
                    .attr('title', 'Click para ' + (habilitada ? 'deshabilitar' : 'habilitar'));
                $('#' + id).toggleClass('is-off', !habilitada);
            })
            .fail(function () { Abm.aviso('error', 'Error', 'No se pudo cambiar el estado.'); })
            .always(function () { $pill.prop('disabled', false); });
    }
});
