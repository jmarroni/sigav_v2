
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
        document.location.href="carga_post.php?identificador=" + identificador + "&action=eliminar";
    }
}

function modificar(identificador){
    $.get("get_productos.php?identificador=" +identificador, function(data, status){  
        var jsonData = data;
        $("#id").val(jsonData.id);
        $("#producto").val(jsonData.nombre);
        $("#costo").val(jsonData.costo);
        $("#precio_unidad").val(jsonData.precio_unidad);
        $("#codigo_de_barras").val(jsonData.codigo_barras);
        $("#proveedor").val(jsonData.proveedores_id);
        $("#stock").val(jsonData.stock);
        $("#stock_minimo").val(jsonData.stock_minimo);
        $("#precio_mayorista").val(jsonData.precio_mayorista);
        if (jsonData.es_comodato) $("#es_comodato").prop('checked',true); else $("#es_comodato").prop('checked',false);;
        $("#proveedor").change();
        document.location.href="#bg-black-op";
        setTimeout(function(){ 
            $("#categoria").val(jsonData.categorias_id + '_' + jsonData.abreviatura);
           // $("#proveedor").attr("disabled","disabled");
           // $("#categoria").attr("disabled","disabled");
        },1000);
    });
}
