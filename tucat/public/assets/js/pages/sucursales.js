
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);


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
        document.location.href="sucursales_post.php?identificador=" + identificador + "&action=eliminar";
    }
}

function modificar(identificador){
    $.post("get_sucursales.php?identificador=" +identificador, function(data, status){  
        var jsonData = JSON.parse(data);
        $("#id").val(jsonData.id);
        $("#Fecha_alta").val(jsonData.fecha_alta);
        $("#Fecha_baja").val(jsonData.fecha_baja);
        $("#nombre").val(jsonData.nombre);
        $("#direccion").val(jsonData.direccion);
        $("#provincia").val(jsonData.provincia);
        $("#codigo_postal").val(jsonData.codigo_postal);
        $("#pto_vta").val(jsonData.pto_vta);
        document.location.href="#bg-black-op";
    });
}
