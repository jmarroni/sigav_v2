
function eliminar(identificador,estado){
    if (confirm('Esta seguro que desea eliminar el cliente?')){
        document.location.href="cliente_post.php?identificador=" + identificador + "&action=" + estado;
    }
}

function modificar(identificador){
    $.post("get_cliente.php?identificador=" +identificador, function(data, status){  
        var jsonData = JSON.parse(data);
        $.each(jsonData,function(key,value){
            $("#" + key).val(value);
        });
        $("#boton").html('<i class="fa fa-check push-5-r"></i>Actualizar');
        document.location.href="#bg-black-op";
    });
}