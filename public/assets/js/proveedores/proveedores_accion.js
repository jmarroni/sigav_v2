jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#nombre").val()=="" || $("#apellido").val()=="" || $("#direccion").val()=="" || $("#ciudad").val()=="" || $("#provincia").val()=="" || $("#telefono").val()=="" || $("#categoria").val()=="0")
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
        var datos = $("#form-artesano").serialize();
          var url = "/proveedor.save/" + datos;
          //console.log(datos);
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
                        "text":"Ocurrió un error al guardar el proveedor",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
       }
    });
});

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

function modificarArtesano(identificador) {
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
        }
    });
     $.get("/getCategoriasProveedor/"+identificador , function(data, status) {
        if (status === 'success') {
            var arrayCategorias = data.split(',');
            //console.log(arrayCategorias);
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
