jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#nombre").val()=="" ||  $("#abreviatura").val()=="")
        { 
           swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Debe completar todos los campos obligatorios (*)",
            'confirmButtonText': 'Listo'
        });
       }
       else
       { 
        $(".form-horizontal").submit();
        var datos = $("#form-categoria").serialize();
          var url = "/categoria.save/" + datos;
          console.log(datos);
            $.post(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Proveedor guardado exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ocurrió un error al guardar la categoría",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
       }
    });
});

function eliminarCategoria(identificador){
    if (confirm('¿Está seguro que desea eliminar la categoría?')) {
        $.get("/categoria.checkProducts/"+ identificador, function(data, status) {
            if (status === 'success') {
                if (data.proceso=='FAIL' ) {
                    $("#erroreliminar").fadeIn();
                    $("#erroreliminar").removeClass("hidden");

                    setTimeout( function() {
                        $("#erroreliminar").fadeOut();
                    }, 3000);
                } else {
                    var url= "/categoria.delete/" + identificador;
                    $.get(url, function(data, status){  
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
            });
                }
            }
        });
    }
}

function modificarCategoria(identificador) {
    $.get("/categoria.getCategoria/"+identificador , function(data, status) {
        if (status === 'success') {
            $("#id_categoria").val(data.id);
            $("#nombre").val(data.nombre);
            $("#abreviatura").val(data.abreviatura);
            $("#nombre").focus();
        }
    });
}
function cambiarStatus(identificador) {
    $.get("/categoria.changeStatus/"+identificador , function(data, status) {
        if (status === 'success') {
             if(data.proceso == "OK"){
                if(data.status == 0)
                    {
                         $("#status_"+identificador).text("Habilitar");
                         $("#spanHabilitada_"+identificador).text("Habilitado No");
                         $("#spanHabilitada_"+identificador).css("color","red")
                    }
                else
                    {
                         $("#status_"+identificador).text("Desabilitar");
                         $("#spanHabilitada_"+identificador).text("Habilitado Si");
                         $("#spanHabilitada_"+identificador).css("color","")
                    }
           swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Estatus cambiado con éxito!",
                        'confirmButtonText': 'Listo',
                       
                    });
            }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error al cambiar el estatus",
                        'confirmButtonText': 'Listo'
                    });
                }
        }
    });
}
