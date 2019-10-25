
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);

    $("#proveedor").change(function(){
        if ($(this).val() != 0){
            $.post("list_categorias.php?proveedor=" + $(this).val(), function(data, status){  
                $("#categoria").html(data);
                $("#categoria").removeAttr("disabled");
            });
        }else{
            $("#categoria").html("<option value='0'>Seleccione un artesano</option>");
            $("#categoria").attr("disabled","disabled");
        }
    });

    $("#filtro").keyup(function() {
        $("tr[id^='articulo_']" ).each(function( index ) {
            $(this).hide();
        });
        $("tr[id^='articulo_" + $(this).val().toLowerCase() + "']" ).each(function( index ) {
            $(this).show();
        });
    });

});

function eliminar(identificador){
    if (confirm('Esta seguro que desea eliminar el producto?')){
        document.location.href="usuarios_post.php?identificador=" + identificador + "&action=eliminar";
    }
}

function modificar(identificador){
    $.post("get_usuarios.php?identificador=" +identificador, function(data, status){  
        var jsonData = JSON.parse(data);
        $("#id").val(jsonData.id);
        $("#usuario").val(jsonData.usuario);
        $("#rol").val(jsonData.rol_id);
        $("#nombre").val(jsonData.nombre);
        $("#apellido").val(jsonData.apellido);
        $("#telefono").val(jsonData.telefono);
        $("#sucursales").val(jsonData.sucursal_id);
        document.location.href="#bg-black-op";
    });
}
