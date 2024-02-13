jQuery("document").ready(function() {
  $("#nombre-producto").click(function(event){
});

jQuery("#nombre-producto").autocomplete({
                source: function (request, response) {
                        jQuery.get("/etiqueta.buscarProductos", {
                        term: request.term
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

    var etiquetas="";
    var contador=0;
    $("#ver_etiquetas").click(function(){
        if (etiquetas != "") etiquetas += "-" + $("#producto_id").val() + '@' + $("#cantidad").val();
        else etiquetas = $("#producto_id").val() + '@' + $("#cantidad").val();
        // Agarro el id del producto
       // alert(etiquetas);
        var id_producto = $("#producto_id").val();
        // Borro el qr
        $("#qrcode").empty();
        // Verifico que no este vacio o sea nulo el producto buscado
        if (id_producto != "" && id_producto != null  && $("#cantidad").val()!="") {
            // $.get("/etiqueta.getQr/"+ id_producto, function(data, status) {
            //     if (status === 'success') {

            //         $("#mensajeqr").addClass("hidden");

            //         if (data !== 'no existe' && data !== null) {
            //             contador=contador+1;
            //             // var padre = document.getElementByID('qrcode');
            //             $('qrcode').append('<div id="s"></div>');
            //             //var hijo = document.createElement('div');
            //             // lista.appendChild(hijo);
            //             $("#sitiowebvacio").addClass("hidden");
            //             $("#botonqr").removeClass("hidden");
            //             new QRCode('qrcode', {
            //                 text:data,
            //                 width: 128,
            //                 height: 128,
            //                 colorDark : "#000000",
            //                 colorLight : "#ffffff",
            //                 correctLevel : QRCode.CorrectLevel.H
            //             });
            //         } else {
            //             $("#sitiowebvacio").removeClass("hidden");
            //             $("#botonqr").addClass("hidden");
            //         }
            //     }
            // });
        $("#nombre-producto").val('');
        $("#cantidad").val('');
        $("#producto_id").val('');
        $("#iframe_etiquetas").attr("src","/etiqueta.imprimirEtiquetas/" + etiquetas);
        $("#iframe_qrs").attr("src","/etiqueta.imprimirQrs/" + etiquetas);
        } else {
             swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Debe seleccionar un producto e ingresar la cantidad de etiquetas a imprimir",
                        'confirmButtonText': 'Listo'
                    });
            // $("#botonqr").addClass("hidden");
            // $("#sitiowebvacio").addClass("hidden");
            // $("#mensajeqr").removeClass("hidden");
        }

       
    });


});