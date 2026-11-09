/*
 * ABM de proveedores / artesanos (/proveedor).
 * Rutas (routes/web.php): POST /proveedor.save (multipart, submit nativo para las imágenes),
 * GET /proveedor.getProveedor/{id}, GET /proveedor.getCategoriasProveedor/{id},
 * GET /proveedor.checkProducts/{id}, DELETE /proveedor/{id}.
 */
jQuery(function ($) {
    var SLOTS = [1, 2, 3];
    var OBLIGATORIOS = ['nombre', 'apellido', 'direccion', 'ciudad', 'provincia', 'telefono', 'titulo', 'descripcion'];
    var $tabla = $('#tabla-proveedores');
    var $form = $('#form-artesano');
    var form = {
        $card: $('#card-form'),
        $titulo: $('#form-titulo'),
        $nota: $('#form-nota'),
        $cancelar: $('#cancelar'),
        $enviar: $('#enviar'),
        $tabla: $tabla,
        tituloAlta: 'Nuevo proveedor',
        tituloEdicion: 'Modificar proveedor',
        textoAlta: 'Guardar proveedor'
    };

    Abm.ocultarAvisoExito();
    Abm.buscador($('#buscar'), $tabla, $('#sin-resultados'));

    if ($('#id_proveedor').val()) {
        Abm.modoEdicion(form, true, $.trim($('#nombre').val() + ' ' + $('#apellido').val()));
    }

    // El envío es el submit nativo del formulario: así viajan también las imágenes.
    $form.on('submit', function (event) {
        var faltan = OBLIGATORIOS.filter(function (campo) { return $.trim($('#' + campo).val()) === ''; });
        if (faltan.length > 0 || $('#categorias input:checked').length === 0) {
            event.preventDefault();
            Abm.aviso('warning', 'Verificar', 'Completá todos los campos obligatorios (*) y elegí al menos una categoría.');
            if (faltan.length > 0) { $('#' + faltan[0]).focus(); }
            return;
        }
        var mail = $.trim($('#mail').val());
        if (mail !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail)) {
            event.preventDefault();
            Abm.aviso('warning', 'Verificar', 'La dirección de correo no es válida.');
            $('#mail').focus();
            return;
        }
        form.$enviar.prop('disabled', true);
    });

    // Vista previa de la imagen elegida antes de guardar.
    SLOTS.forEach(function (orden) {
        $('#imagen' + orden).on('change', function () {
            mostrarPrevia(orden, this.files && this.files[0] ? URL.createObjectURL(this.files[0]) : null, true);
        });
    });

    form.$cancelar.on('click', limpiarFormulario);
    $tabla.on('click', '[data-accion="editar"]', function () { editar($(this).data('id')); });
    $tabla.on('click', '[data-accion="eliminar"]', function () { eliminar($(this).data('id'), $(this).data('nombre')); });

    function mostrarPrevia(orden, url, nueva) {
        var $slot = $('#slot_imagen' + orden);
        var $preview = $('#preview_imagen' + orden).empty();
        if (url) {
            $preview.append($('<img>').attr({ src: url, alt: 'Imagen ' + orden }));
        }
        $slot.toggleClass('has-image', !!url);
        $slot.find('.sg-slot__label').text(url ? (nueva ? 'Nueva imagen' : 'Cambiar imagen') : 'Elegir imagen');
    }

    function limpiarSlots() {
        // Un archivo elegido para otro proveedor no debe llegar a este.
        $form.find('input[type=file]').val('');
        SLOTS.forEach(function (orden) { mostrarPrevia(orden, null); });
    }

    function limpiarFormulario() {
        $form[0].reset();
        $('#id_proveedor').val('');
        $('#categorias input').prop('checked', false);
        limpiarSlots();
        Abm.modoEdicion(form, false);
    }

    function editar(id) {
        limpiarSlots();
        $.when(
            Abm.pedir({ url: '/proveedor.getProveedor/' + id, type: 'GET' }),
            Abm.pedir({ url: '/proveedor.getCategoriasProveedor/' + id, type: 'GET', dataType: 'text' })
        ).done(function (respProveedor, respCategorias) {
            var data = respProveedor[0];
            if (!data) { return Abm.aviso('error', 'Error', 'El proveedor ya no existe.'); }
            $('#id_proveedor').val(data.id);
            ['nombre', 'apellido', 'direccion', 'ciudad', 'provincia', 'telefono', 'mail', 'sitio_web', 'titulo', 'descripcion']
                .forEach(function (campo) { $('#' + campo).val(data[campo] || ''); });

            var elegidas = String(respCategorias[0] || '').split(',').filter(Boolean);
            $('#categorias input').each(function () {
                $(this).prop('checked', elegidas.indexOf(String($(this).val())) !== -1);
            });

            // Imágenes actuales: se ven en su slot; subir una nueva en ese slot la reemplaza.
            SLOTS.forEach(function (orden) {
                var actual = (data.imagenes || []).filter(function (img) { return img.orden == orden; })[0];
                mostrarPrevia(orden, actual ? actual.imagen_url : null, false);
            });

            Abm.modoEdicion(form, true, $.trim((data.nombre || '') + ' ' + (data.apellido || '')));
            $('#' + id).addClass('is-selected');
            $('#nombre').focus();
        }).fail(function () {
            Abm.aviso('error', 'Error', 'No se pudo cargar el proveedor.');
        });
    }

    function eliminar(id, nombre) {
        Abm.confirmar('¿Eliminar a "' + nombre + '"?', 'Se borran también sus categorías e imágenes. No se puede deshacer.', function () {
            Abm.pedir({ url: '/proveedor.checkProducts/' + id, type: 'GET' })
                .done(function (check) {
                    if (check.proceso === 'FAIL') {
                        return Abm.aviso('error', 'No se puede eliminar', 'El proveedor tiene productos cargados. Eliminá o reasigná sus productos primero.');
                    }
                    Abm.pedir({ url: '/proveedor/' + id, type: 'DELETE' })
                        .done(function (data) {
                            if (data.proceso !== 'OK') { return Abm.aviso('error', 'Error', 'No se pudo eliminar el proveedor.'); }
                            if ($('#id_proveedor').val() == id) { limpiarFormulario(); }
                            Abm.quitarFila($('#' + id), $('#contador'));
                        })
                        .fail(function () { Abm.aviso('error', 'Error', 'No se pudo eliminar el proveedor.'); });
                })
                .fail(function () { Abm.aviso('error', 'Error', 'No se pudo verificar el proveedor.'); });
        });
    }
});
