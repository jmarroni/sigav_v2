jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);

    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#cien").val()=="" || $("#cincuenta").val()=="" || $("#veinte").val()=="" || $("#diez").val()=="" || $("#cinco").val()=="" )
        { 
           swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Debe especificar la cantidad recaudada de todas las denominaciones",
            'confirmButtonText': 'Listo'
        });
       }
       else
       { 
        $(".form-horizontal").submit();
        var datos = $("#form-caja").serialize();
       //console.log(datos);
          var url = "/cierreCajaAccion/" + datos;
            $.post(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Operación guardada exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ocurrió un error al guardar la operación",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
       }
    });
});