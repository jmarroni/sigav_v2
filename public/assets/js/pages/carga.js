
jQuery("document").ready(function() {
    // CSRF token para peticiones POST/DELETE a rutas Laravel (web group).
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    // Proveedor preseleccionado por old() (alta rechazada): cargar sus categorías.
    if ($("#proveedor").val() && $("#proveedor").val() != 0) {
        $("#proveedor").change();
    }
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#anadir").click(function(event){
        event.preventDefault();
        // Chequeo previo de las imágenes en el navegador: evita mandar un POST de varios MB
        // que el servidor va a rechazar igual (y con el que se perdería lo tipeado).
        var errorImagen = validarImagenesLocal();
        if (errorImagen){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text": errorImagen,
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#producto").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"El nombre del producto no puede estar vacío.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#proveedor").val() == "0"){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe seleccionar el proveedor",
                'confirmButtonText': 'Listo'
            });
            return;
        }
         if ($("#categoria").val() == "" || $("#categoria").val() == 0 || $("#categoria").val() == null){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe seleccionar una categoría",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#costo").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe colocar el Precio de última Compra",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#precio_unidad").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe colocar al menos el precio minorista",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#descripcion").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe completar al menos la descripción en español",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        $(".form-horizontal").submit();
    });
    $(document).on("click", "button[id^='actualizar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        var stock         = $("#stock_" + identificador).val();
        var sucursal      = $("#sucursal").val();

        if (stock < 0 || stock === ''){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Recuerde que el stock debe ser mayor o igual a cero.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if (!sucursal || sucursal == 0){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Seleccione una sucursal antes de actualizar el stock.",
                'confirmButtonText': 'Listo'
            });
            return;
        }

        $.ajax({
            url: "/producto.actualizar.stock/" + identificador,
            method: "POST",
            data: { stock: stock, stock_minimo: 1, sucursal_id: sucursal },
            success: function(data){
                if(data && data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Actualización realizada con éxito !",
                        'confirmButtonText': 'Listo'
                    });
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error, por favor verifique los datos",
                        'confirmButtonText': 'Listo'
                    });
                }
            },
            error: function(){
                swal({
                    "title":"Error",
                    'icon': 'error',
                    "text":"Error, por favor verifique los datos",
                    'confirmButtonText': 'Listo'
                });
            }
        });
    });

    // Venta online: manda el estado deseado (el opuesto al actual), no "invertir".
    $(document).on("click", "button[id^='ventaonline_']", function() {
        var boton = $(this);
        var identificador = boton.attr("id").split("_")[1];
        var deseado = boton.attr("data-online") == "1" ? 0 : 1;

        boton.prop("disabled", true);
        $.ajax({
            url: "/productos/" + identificador + "/venta-online",
            method: "POST",
            data: { venta_online: deseado },
            success: function(data){
                if (data && data.ok) {
                    // Recarga la página actual de la grilla para que se vean la marca y el precio web.
                    window.tablaProductos.ajax.reload(null, false);
                    swal({
                        "title": "Perfecto !!",
                        "icon": "success",
                        "text": data.venta_online == 1
                            ? "El producto se publica en la venta online (con el recargo en la web)."
                            : "El producto ya no se publica en la venta online.",
                        "confirmButtonText": "Listo"
                    });
                } else {
                    boton.prop("disabled", false);
                }
            },
            error: function(xhr){
                boton.prop("disabled", false);
                swal({
                    "title": "Error",
                    "icon": "error",
                    "text": xhr.status == 403
                        ? "Solo los roles Carga y Administrador pueden cambiar la venta online."
                        : "No se pudo actualizar la venta online, intente de nuevo.",
                    "confirmButtonText": "Listo"
                });
            }
        });
    });

    $(document).on("click", "button[id^='editar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        modificar(identificador);
    });

 $(document).on("click", "button[id^='eliminar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        var sucursal      = $("#sucursal").val();
        if (!sucursal || sucursal == 0){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Seleccione una sucursal antes de eliminar el producto.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        swal({
            title: '¿Está seguro de eliminarlo?',
            icon: 'question',
            showCloseButton: true,
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText:'Si',
            cancelButtonText:'No',
            },
            function(isConfirm){
                if (isConfirm){
            var stock           = $("#stock_" + identificador).val();
            if (stock === '' || stock === undefined) { stock = 0; }
            $.ajax({
              url: "/producto.eliminar.stock/" + identificador,
              method: "DELETE",
              data: { stock: stock, stock_minimo: 1, sucursal_id: sucursal },
              success: function(data, status){
                if(data && data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Eliminación realizada con éxito !",
                        'confirmButtonText': 'Listo',

                    });
                   // Grilla server-side: refrescar la página actual de DataTables.
                   if (window.tablaProductos) {
                       window.tablaProductos.ajax.reload(null, false);
                   } else {
                       $("#"+identificador).remove();
                   }

                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error al realizar la eliminación",
                        'confirmButtonText': 'Listo'
                    });
                }
              },
              error: function(){
                swal({
                    "title":"Error",
                    'icon': 'error',
                    "text":"Error al realizar la eliminación",
                    'confirmButtonText': 'Listo'
                });
              }
            });
                }
            });
    });

    $("#proveedor").change(function(){
        if ($(this).val() != 0){
            $.post("/list_categorias.php?proveedor=" + $(this).val(), function(data, status){
                $("#categoria").html(data);
                $("#categoria").removeAttr("disabled");
                // Tras un alta rechazada (imagen inválida) el server repuebla el form con old():
                // la categoría elegida viene en data-old-categoria y se restaura una sola vez.
                var oldCategoria = $("#categoria").attr("data-old-categoria");
                if (oldCategoria) {
                    $("#categoria").val(oldCategoria).removeAttr("data-old-categoria");
                }
            });
        }else{
            $("#categoria").html("<option value='0'>Seleccione un proveedor</option>");
            $("#categoria").attr("disabled","disabled");
        }
    });

    $("#filtro").keyup(function() {
        $("tr[id^='articulo_']" ).each(function( index ) {
            $(this).hide();
        });
        $("tr[id^='articulo_" + $(this).val().toLowerCase() + "']" ).each(function( index ) {
            $(this).show();
        });
    });

    $("#sucursal").change(function(){
        document.location.href="carga?sucursal=" + $(this).val();
    });


});




function eliminar(identificador){
    document.location.href="carga_post.php?identificador=" + identificador + "&action=eliminar";
}

function modificar(identificador){
    if ($("#stock_" + identificador).val() < 0 || $("#stock_" + identificador).val() == ''){
        swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Recuerde que el stock debe ser mayor o igual a cero.",
            'confirmButtonText': 'Listo'
        });
    }else{
        $.get("/carga/" + identificador, function(data, status){  
            var jsonData = data;
            $("#id").val(jsonData.id);
            $("#producto").val(jsonData.nombre);
            $("#costo").val(jsonData.costo);
            $("#precio_unidad").val(jsonData.precio_unidad);
            $("#codigo_de_barras").val(jsonData.codigo_barras);
            $("#proveedor").val(jsonData.proveedores_id);
            $("#categoria").val(jsonData.categorias_id + '_' + jsonData.abreviatura);
            $("#descripcion").val(jsonData.descripcion);
            $("#descripcion_en").val(jsonData.descripcion_en);
            $("#descripcion_pr").val(jsonData.descripcion_pr);
            $("#material").val(jsonData.material);
            $("#envio_tamano").val(jsonData.envio_tamano != null ? String(jsonData.envio_tamano) : "");
            $("#precio_mayorista").val(jsonData.precio_mayorista);
            $("#descuento").val(jsonData.descuento != null ? jsonData.descuento : 0);
            if (jsonData.es_comodato) $("#es_comodato").prop('checked',true); else $("#es_comodato").prop('checked',false);;
            $("#proveedor").change();
            mostrarImagenesActuales(jsonData.imagenes || []);
            // Llevar la vista al formulario para que se note que se cargaron los datos.
            // (El anchor viejo #bg-black-op ya no existe en el rediseño.)
            var $form = $("form.sg-form").first();
            if ($form.length) {
                $("html, body").animate({ scrollTop: $form.offset().top - 20 }, 400);
                $("#producto").focus();
            }
           /* setTimeout(function(){
                $("#categoria").val(jsonData.categorias_id + '_' + jsonData.abreviatura);
            // $("#proveedor").attr("disabled","disabled");
            // $("#categoria").attr("disabled","disabled");
            },1000);   */
        });
    }
}

// Mismas reglas que ProductoController@validarImagenes (10 MB, jpg/png/gif/bmp).
var IMAGEN_MAX_BYTES = 10 * 1024 * 1024;
var IMAGEN_EXTENSIONES = /\.(jpe?g|png|gif|bmp)$/i;
function validarImagenesLocal(){
    var error = null;
    $("input[type=file][name^='imagen']").each(function(){
        var archivo = this.files && this.files[0];
        if (!archivo) return;
        var etiqueta = $("label[for='" + this.id + "']").text() || this.name;
        if (!IMAGEN_EXTENSIONES.test(archivo.name)) {
            error = etiqueta + ": el archivo debe ser jpg, png, gif o bmp.";
            return false;
        }
        if (archivo.size > IMAGEN_MAX_BYTES) {
            error = etiqueta + ": supera el máximo de 10 MB (" + (archivo.size / 1048576).toFixed(1) + " MB).";
            return false;
        }
    });
    return error;
}

// Miniaturas del producto que se está editando (vienen en GET /carga/{id} -> imagenes[]).
// Cada una abre la galería y tiene su botón para eliminarla.
// Se arma con DOM (no con HTML concatenado) para que las URLs no se interpreten como markup.
function mostrarImagenesActuales(imagenes){
    var $caja  = $("#imagenes_actuales");
    var $lista = $caja.find(".sg-imgs-actuales__lista").empty();
    if (!imagenes || imagenes.length === 0) {
        $caja.attr("hidden", true);
        return;
    }
    var productoId = $("#id").val();
    var items = $.map(imagenes, function(img){
        return { id: img.id, url: img.imagen_url, miniatura: img.miniatura || img.imagen_url };
    });
    $.each(items, function(indice, item){
        var $item = $("<div>", { "class": "sg-img-actual" });
        var $ver  = $("<button>", { type: "button", "class": "sg-thumb-link", title: "Ver imagen " + (indice + 1) });
        $ver.append($("<img>", { "class": "sg-thumb", src: item.miniatura, alt: "Imagen " + (indice + 1) }));
        $ver.on("click", function(){
            Galeria.abrir(items, indice, { titulo: $("#producto").val(), productoId: productoId });
        });
        var $borrar = $("<button>", { type: "button", "class": "sg-img-actual__borrar", title: "Eliminar imagen", "aria-label": "Eliminar imagen " + (indice + 1) })
            .append($("<i>", { "class": "fa fa-trash" }));
        $borrar.on("click", function(){ confirmarEliminarImagen(productoId, item.id); });
        $item.append($ver, $borrar);
        $lista.append($item);
    });
    $caja.removeAttr("hidden");
}

// Pide confirmación y borra la imagen; al terminar refresca el formulario, la galería y la grilla.
function confirmarEliminarImagen(productoId, imagenId, alTerminar){
    swal({
        title: "¿Eliminar esta imagen?",
        text: "Se borra del producto y no se puede recuperar.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }, function(confirmado){
        if (!confirmado) return;
        $.ajax({
            url: "/carga/" + productoId + "/imagenes/" + imagenId,
            method: "DELETE",
            success: function(data){
                var restantes = (data && data.imagenes) || [];
                mostrarImagenesActuales(restantes);
                if (window.tablaProductos) window.tablaProductos.ajax.reload(null, false);
                if (alTerminar) alTerminar(restantes);
            },
            error: function(xhr){
                swal({
                    title: "Error",
                    icon: "error",
                    text: xhr.status == 403 ? "No tiene permiso para eliminar imágenes."
                        : xhr.status == 404 ? "La imagen ya no existe (recargue la página)."
                        : "No se pudo eliminar la imagen, intente de nuevo.",
                    confirmButtonText: "Listo"
                });
            }
        });
    });
}

// ---------------------------------------------------------------------------
// Galería: visor de imágenes en la misma página (sin abrir otra pestaña).
// items = [{url, miniatura, id?}]. Si se pasa productoId, muestra "Eliminar".
// Teclado: ← / → navegan, Esc cierra.
// ---------------------------------------------------------------------------
var Galeria = (function(){
    var items = [], actual = 0, opciones = {}, $g, foco = null;

    function el(){ return $g || ($g = $("#sg-galeria")); }

    function pintar(){
        var item = items[actual];
        var $gal = el();
        $gal.find(".sg-galeria__img").attr({ src: item.url, alt: (opciones.titulo || "Imagen") + " — " + (actual + 1) });
        $gal.find(".sg-galeria__contador").text((actual + 1) + " / " + items.length);
        $gal.find(".sg-galeria__titulo").text(opciones.titulo || "");
        $gal.find(".sg-galeria__nav").prop("hidden", items.length < 2);
        $gal.find(".sg-galeria__borrar").prop("hidden", !(opciones.productoId && item.id));
        var $tira = $gal.find(".sg-galeria__tira").empty().prop("hidden", items.length < 2);
        $.each(items, function(i, it){
            var $b = $("<button>", { type: "button", "class": "sg-galeria__mini" + (i === actual ? " is-activa" : ""), "aria-label": "Ver imagen " + (i + 1) })
                .append($("<img>", { src: it.miniatura || it.url, alt: "" }));
            $b.on("click", function(){ ir(i); });
            $tira.append($b);
        });
    }

    function ir(i){
        if (!items.length) return;
        actual = (i + items.length) % items.length;
        pintar();
    }

    function abrir(lista, indice, opts){
        if (!lista || !lista.length) return;
        items = lista; opciones = opts || {}; actual = 0;
        foco = document.activeElement;
        ir(indice || 0);
        el().prop("hidden", false).addClass("is-abierta");
        $("body").addClass("sg-galeria-abierta");
        el().find(".sg-galeria__cerrar").trigger("focus");
    }

    function cerrar(){
        el().removeClass("is-abierta").prop("hidden", true);
        $("body").removeClass("sg-galeria-abierta");
        el().find(".sg-galeria__img").attr("src", "");
        if (foco && foco.focus) foco.focus();
    }

    $(document).on("click", "#sg-galeria .sg-galeria__cerrar", cerrar);
    $(document).on("click", "#sg-galeria", function(e){ if (e.target === this || $(e.target).hasClass("sg-galeria__escena")) cerrar(); });
    $(document).on("click", "#sg-galeria .sg-galeria__prev", function(){ ir(actual - 1); });
    $(document).on("click", "#sg-galeria .sg-galeria__next", function(){ ir(actual + 1); });
    $(document).on("click", "#sg-galeria .sg-galeria__borrar", function(){
        var item = items[actual];
        confirmarEliminarImagen(opciones.productoId, item.id, function(restantes){
            items = $.map(restantes, function(img){ return { id: img.id, url: img.imagen_url, miniatura: img.miniatura || img.imagen_url }; });
            if (!items.length) { cerrar(); return; }
            ir(Math.min(actual, items.length - 1));
        });
    });
    $(document).on("keydown", function(e){
        if (!el().hasClass("is-abierta")) return;
        if (e.key === "Escape") cerrar();
        else if (e.key === "ArrowLeft") ir(actual - 1);
        else if (e.key === "ArrowRight") ir(actual + 1);
    });

    // Miniatura de la grilla: abre la galería con todas las imágenes del producto.
    $(document).on("click", "a.js-galeria", function(e){
        var lista = [];
        try { lista = JSON.parse($(this).attr("data-galeria") || "[]"); } catch (err) { lista = []; }
        if (!lista.length) return; // sin datos: se deja el link normal
        e.preventDefault();
        abrir(lista, 0, { titulo: $(this).attr("data-titulo") });
    });

    return { abrir: abrir, cerrar: cerrar };
})();
