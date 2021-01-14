jQuery("document").ready(function() {
  $("#nombre-producto").click(function(event){
});

jQuery("#nombre-producto").autocomplete({
                source: function (request, response) {
                        jQuery.get("/etiqueta.buscarProductos", {
                        query: request.term
                        }, function (data) {
                         response(data);
                        });
                        },
                minLength: 2,
                select: function( event, ui ) {
                    $("#nombre-producto").val(ui.item.value);
                    $("#producto_id").val(ui.item.id);
                }
            });


    $("#ver_etiquetas").click(function(){
        etiquetas = $("#producto_id").val() + '@' + $("#cantidad").val();
        // Agarro el id del producto
       // alert(etiquetas);
        var id_producto = $("#producto_id").val();
        // Borro el qr
        $("#qrcode").empty();
        // Verifico que no este vacio o sea nulo el producto buscado
        if (id_producto != "" && id_producto != null  && $("#cantidad").val()!="") {
            $.get("/etiqueta.getQr/"+ id_producto, function(data, status) {
                if (status === 'success') {

                    $("#mensajeqr").addClass("hidden");

                    if (data !== 'no existe' && data !== null) {
                        $("#sitiowebvacio").addClass("hidden");
                        $("#botonqr").removeClass("hidden");
                        new QRCode("qrcode", {
                            text:data,
                            width: 128,
                            height: 128,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    } else {
                        $("#sitiowebvacio").removeClass("hidden");
                        $("#botonqr").addClass("hidden");
                    }
                }
            });
        } else {
             swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Debe seleccionar un producto e ingresar la cantidad de etiquetas a imprimir",
                        'confirmButtonText': 'Listo'
                    });
            $("#botonqr").addClass("hidden");
            $("#sitiowebvacio").addClass("hidden");
            $("#mensajeqr").removeClass("hidden");
        }

        $("#nombre-producto").val('');
        $("#cantidad").val('');
        $("#producto_id").val('');
        $("#iframe_etiquetas").attr("src","/etiqueta.imprimir/" + etiquetas);
    });


});