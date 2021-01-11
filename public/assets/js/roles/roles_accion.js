jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#rol").val()=="")
        { 
           swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Debe completar el nombre del rol",
            'confirmButtonText': 'Listo'
        });
       }
       else
       { 
        $(".form-horizontal").submit();
        var datos = $("#form-rol").serialize();
       // console.log(datos);
          var url = "/rol.save/" + datos;
            $.post(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Rol guardado exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ocurrió un error al guardar el rol",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
       }
    });
});

function eliminarRol(identificador){
    if (confirm('¿Está seguro que desea eliminar el rol?')) {
        var url= "/rol.delete/" + identificador;
        $.get(url, function(data, status){  
            if(data.proceso == "OK"){
                swal({
                    "title":"Perfecto !!",
                    'icon': 'success',
                    "text":"Eliminación realizada con éxito !",
                    'confirmButtonText': 'Listo',

                });
                $("#articulo_"+identificador).remove();
                //$("#form-rol")[0].reset();
                $("#rol").val("");
                $('#habilitado').removeAttr('checked');

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


function modificarRol(identificador,titulo,habilitado){
    $("#rol").val(titulo);
    $("#id").val(identificador);
    if (habilitado == 1){$("#habilitado").prop("checked",true);}else{$("#habilitado").prop("checked",false);}
    var secciones = $("#seccion_" + identificador).val().split("|");
    var i;
    for (i =0; i < secciones.length; i ++){
        $("#secciones_" + secciones[i]).prop("checked",true);
    }
    //document.location.href ="#formulario";
    $("#rol").focus();
}

