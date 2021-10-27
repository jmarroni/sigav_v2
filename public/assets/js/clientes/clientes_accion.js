jQuery("document").ready(function() {

    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#enviar").click(function(event){
        event.preventDefault();
        if ($("#razon_social").val()=="" || $("#domicilio_legal").val()=="" || $("#codigo_postal").val()=="" || $("#telefono").val()=="" || $("#provincia").val()=="" || $("#localidad").val()=="" || $("#cuit").val()=="" || $("#condicion_iva").val()=="0" || $("#representante").val()=="")
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
        $urlConsulta="/cliente.consultarClientexCuit/"+$("#cuit").val();
        $.get($urlConsulta, function(data, status){  
                if(data== 0){
                   $(".form-horizontal").submit();
        var datos = $(".form-horizontal").serialize();
          var url = "/cliente.save/" + datos;
          console.log(datos);
            $.post(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Cliente guardado exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ocurrió un error al guardar el cliente",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
                        
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Ya existe un cliente con ese CUIT",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
    }
        
       
    });
});

function eliminarCliente(identificador){
    if (confirm('¿Está seguro que desea eliminar el cliente?')) {
        var url= "/cliente.delete/" + identificador;
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

function modificarCliente(identificador) {
    $.get("/cliente.getCliente/"+identificador , function(data, status) {
        if (status === 'success') {
             $("#id").val(data.id);
             $("#razon_social").val(data.razon_social);
             $("#domicilio_legal").val(data.domicilio_legal);
             $("#codigo_postal").val(data.codigo_postal);
             $("#telefono").val(data.telefono);
             $("#provincia").val(data.provincia);
             $("#localidad").val(data.localidad);
             $("#cuit").val(data.cuit);
             $("#condicion_iva").val(data.condicion_iva);
             $("#representante").val(data.representante);
             $("#email_representante").val(data.email_representante);
             $("#responsable_contratacion").val(data.responsable_contratacion);
             $("#email_constratacion").val(data.email_constratacion);
             $("#responsable_pagos").val(data.responsable_pagos);
             $("#email_pagos").val(data.email_pagos);
             $("#consulta_proveedores").val(data.consulta_proveedores);
             $("#entrega_retiros").val(data.entrega_retiros);
        }
    });
}
