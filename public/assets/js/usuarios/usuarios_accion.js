jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#usuario").val()=="" || $("#clave").val()=="" || $("#rol").val()=="0" || $("#nombre").val()=="" || $("#apellido").val()=="" || $("#telefono").val()=="" || $("#sucursales").val()=="0")
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
        var datos = $("#form-usuario").serialize();
          var url = "/usuario.save/" + datos;
          //console.log(datos);
            $.post(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Usuario guardado exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ocurrió un error al guardar el usuario",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
       }
    });
});

function modificarUsuario(identificador){

    $.get("/usuario.getUsuario/" +identificador, function(data, status){  
        if (status === 'success') {
        $("#id_usuario").val(data.id);
        $("#usuario").val(data.usuario);
        $("#rol").val(data.rol_id);
        $("#nombre").val(data.nombre);
        $("#apellido").val(data.apellido);
        $("#telefono").val(data.telefono);
        $("#sucursales").val(data.sucursal_id);
        $("#usuario").focus();
    }
    });
}
function eliminarUsuario(identificador){
    if (confirm('¿Está seguro que desea eliminar el usuario?')) {
        var url= "/usuario.delete/" + identificador;
        $.get(url, function(data, status){  
            if(data.proceso == "OK"){
                swal({
                    "title":"Perfecto !!",
                    'icon': 'success',
                    "text":"Eliminación realizada con éxito !",
                    'confirmButtonText': 'Listo',

                });
                $("#usuario_"+identificador).remove();
                //$("#form-rol")[0].reset();

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