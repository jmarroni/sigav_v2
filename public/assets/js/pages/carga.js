
jQuery("document").ready(function() {
    setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
    $("#anadir").click(function(event){
        event.preventDefault();
        if ($("#producto").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"El nombre del producto no puede estar vacío.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#proveedor").val() == "0"){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe seleccionar el proveedor",
                'confirmButtonText': 'Listo'
            });
            return;
        }
         if ($("#categoria").val() == "" || $("#categoria").val() == 0 || $("#categoria").val() == null){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe seleccionar una categoría",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#costo").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe colocar el Precio de última Compra",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#precio_unidad").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe colocar al menos el precio minorista",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if ($("#descripcion").val() == ""){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Debe completar al menos la descripción en español",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        $(".form-horizontal").submit();
    });
    $("button[id^='actualizar_']" ).click(function( index ) {
        if ($("#stock_" + identificador).val() < 0 || $("#stock_" + identificador).val() == ''){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Recuerde que el stock debe ser mayor o igual a cero.",
                'confirmButtonText': 'Listo'
            });
        }else{
            var identificador = $(this).attr("id").split("_")[1];
            var stock           = $("#stock_" + identificador).val();
            var stock_minimo    = $("#stock_minimo_" + identificador).val();
            var sucursal        = $("#sucursal").val();
            var url = "/producto.actualizar.stock/" + identificador + "/" + stock + "/" + stock_minimo + "/" + sucursal;
            $.get(url, function(data, status){  
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Actualización realizada con éxito !",
                        'confirmButtonText': 'Listo',
                        
                    });
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error, por favor verifique los datos",
                        'confirmButtonText': 'Listo'
                    });
                }
            });
        }
        
    });

    $("button[id^='editar_']" ).click(function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        modificar(identificador);
    });

 $("button[id^='eliminar_']" ).click(function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        swal({
            title: '¿Está seguro de eliminarlo?',
            icon: 'question',
            showCloseButton: true,
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText:'Si',
            cancelButtonText:'No',
            },
            function(isConfirm){
                if (isConfirm){
            var stock           = $("#stock_" + identificador).val();
            var stock_minimo    = $("#stock_minimo_" + identificador).val();
            var sucursal        = $("#sucursal").val();
            var url = "/producto.eliminar.stock/" + identificador + "/" + stock + "/" + stock_minimo + "/" + sucursal;
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
            });
    });

    $("#proveedor").change(function(){
        if ($(this).val() != 0){
            $.post("/list_categorias.php?proveedor=" + $(this).val(), function(data, status){  
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

    $("#sucursal").click(function(){
        document.location.href="carga?sucursal=" + $(this).val();
    });


});




function eliminar(identificador){
    document.location.href="carga_post.php?identificador=" + identificador + "&action=eliminar";
}

function modificar(identificador){
    if ($("#stock_" + identificador).val() < 0 || $("#stock_" + identificador).val() == ''){
        swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Recuerde que el stock debe ser mayor o igual a cero.",
            'confirmButtonText': 'Listo'
        });
    }else{
        $.get("/carga/" + identificador, function(data, status){  
            var jsonData = data;
            $("#id").val(jsonData.id);
            $("#producto").val(jsonData.nombre);
            $("#costo").val(jsonData.costo);
            $("#precio_unidad").val(jsonData.precio_unidad);
            $("#codigo_de_barras").val(jsonData.codigo_barras);
            $("#proveedor").val(jsonData.proveedores_id);
            $("#descripcion").val(jsonData.descripcion);
            $("#descripcion_en").val(jsonData.descripcion_en);
            $("#descripcion_pr").val(jsonData.descripcion_pr);
            $("#material").val(jsonData.material);
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
}
