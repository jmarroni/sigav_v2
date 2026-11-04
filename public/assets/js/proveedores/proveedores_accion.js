jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);

    // El envío es el submit nativo del formulario: así viajan también las imágenes.
    $("#form-artesano").on("submit", function(event) {
        if ($("#nombre").val()=="" || $("#apellido").val()=="" || $("#direccion").val()=="" || $("#ciudad").val()=="" || $("#provincia").val()=="" || $("#telefono").val()=="" || $("#categoria").val()=="0" || $("#titulo").val().trim()=="" || $("#descripcion").val().trim()=="")
        {
            event.preventDefault();
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe completar todos los campos obligatorios (*)",
                'confirmButtonText': 'Listo'
            });
        }
    });

    // Vista previa de la imagen elegida antes de guardar.
    [1, 2, 3].forEach(function(orden) {
        $("#imagen" + orden).on("change", function() {
            mostrarPrevia(orden, this.files && this.files[0] ? URL.createObjectURL(this.files[0]) : null);
        });
    });
});

function mostrarPrevia(orden, url) {
    var contenedor = $("#preview_imagen" + orden).empty();
    if (url) {
        contenedor.append($("<img>").attr({ src: url, alt: "Imagen " + orden }).css({ "max-width": "120px" }));
    }
}

function eliminarArtesano(identificador){
    if (confirm('¿Está seguro que desea eliminar el proveedor?')) {
        $.get("/proveedor.checkProducts/"+ identificador, function(data, status) {
            if (status === 'success') {
                if (data.proceso=='FAIL' ) {
                    $("#erroreliminar").fadeIn();
                    $("#erroreliminar").removeClass("hidden");

                    setTimeout( function() {
                        $("#erroreliminar").fadeOut();
                    }, 3000);
                } else {
                    var url= "/proveedor.delete/" + identificador;
                    $.ajax({ url: url, type: 'DELETE', headers: {'X-CSRF-TOKEN': $('input[name="_token"]').first().val()}, success: function(data, status){
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Eliminación realizada con éxito !",
                        'confirmButtonText': 'Listo',

                    });
                   $("#"+identificador).remove();

                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error al realizar la eliminación",
                        'confirmButtonText': 'Listo'
                    });
                }
            }, error: function() { swal({ "title": "Error", 'icon': 'error', "text": "Error al realizar la eliminación", 'confirmButtonText': 'Listo' }); }});
                }
            }
        });
    }
}

function modificarArtesano(identificador) {
    // Un archivo elegido para otro artesano no debe llegar a este: se limpian los inputs.
    $("input[type=file]").val("");
    [1, 2, 3].forEach(function(orden) { mostrarPrevia(orden, null); });
    $.get("/proveedor.getProveedor/"+identificador , function(data, status) {
        if (status === 'success') {
            $("#id_proveedor").val(data.id);
            $("#nombre").val(data.nombre);
            $("#apellido").val(data.apellido);
            $("#direccion").val(data.direccion);
            $("#ciudad").val(data.ciudad);
            $("#provincia").val(data.provincia);
            $("#telefono").val(data.telefono);
            $("#mail").val(data.mail);
            $("#sitio_web").val(data.sitio_web);
            $("#titulo").val(data.titulo);
            $("#descripcion").val(data.descripcion);
            // Imágenes actuales: se ven en su slot; subir una nueva en ese slot la reemplaza.
            [1, 2, 3].forEach(function(orden) {
                var actual = (data.imagenes || []).filter(function(img) { return img.orden == orden; })[0];
                mostrarPrevia(orden, actual ? actual.imagen_url : null);
            });
        }
    });
    $.get("/proveedor.getCategoriasProveedor/"+identificador , function(data, status) {
        if (status === 'success') {
            var arrayCategorias = data.split(',');
            $("#categoria option").attr("selected", false);
            for (var i=0; i < arrayCategorias.length; i++)
            {
                if (arrayCategorias[i]!='')
                    {
                        $("#categoria option[value="+arrayCategorias[i]+"]").attr("selected",true);
                    }
            }
        }
    });
}
