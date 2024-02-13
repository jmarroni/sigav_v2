
jQuery("document").ready(function() {


    $("#test_certificado").click(function(){
        $.ajax({  // armar el post de la factura
        method: "POST",
        url: "/guardar_certificados.php",
        data: { cuit:$("#cuit").val(), 
                key: $("#key").val(), 
                certificado: $("#certificado").val(), 
                ptovta:$("#ptovta").val(),
                comprobante:$("#comprobante").val(),
                ingresos_brutos:$("#ingresos_brutos").val(),    
                inicio_actividades:$("#inicio_actividades").val(),
                condicion_iva:$("#condicion_iva").val()
            }
        })
        .done(function( msg ) {
            $("#add_success").show();
            $("#nombre-devuelto").html(msg);
        })
        .fail(function(error){
            console.log(error);
        });
    });

    $("#emision_online").change(function(){
        var emitir = 0;
        if ($(this).prop("checked")){
            emitir = 1;
        }
        $.ajax({  // armar el post de la factura
            method: "POST",
            url: "/emitir_online.php",
            data: { emitir_online:emitir}
            })
            .done(function( msg ) {})
            .fail(function(error){
                console.log(error);
            });
    });
    $("#solicitar_datos").change(function(){
        var solicitar_datos = 0;
        if ($(this).prop("checked")){
            solicitar_datos = 1;
        }
        $.ajax({  // armar el post de la factura
            method: "POST",
            url: "/solicitar_datos.php",
            data: { solicitar:solicitar_datos}
            })
            .done(function( msg ) {})
            .fail(function(error){
                console.log(error);
            });
    });
    $("#close").click(function(){
        $(".modal").hide();
    });
});
