
jQuery("document").ready(function() {
    // CSRF token para peticiones POST/DELETE a rutas Laravel (web group).
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
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
    $(document).on("click", "button[id^='actualizar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        var stock         = $("#stock_" + identificador).val();
        var sucursal      = $("#sucursal").val();

        if (stock < 0 || stock === ''){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Recuerde que el stock debe ser mayor o igual a cero.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
        if (!sucursal || sucursal == 0){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Seleccione una sucursal antes de actualizar el stock.",
                'confirmButtonText': 'Listo'
            });
            return;
        }

        $.ajax({
            url: "/producto.actualizar.stock/" + identificador,
            method: "POST",
            data: { stock: stock, stock_minimo: 1, sucursal_id: sucursal },
            success: function(data){
                if(data && data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Actualización realizada con éxito !",
                        'confirmButtonText': 'Listo'
                    });
                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error, por favor verifique los datos",
                        'confirmButtonText': 'Listo'
                    });
                }
            },
            error: function(){
                swal({
                    "title":"Error",
                    'icon': 'error',
                    "text":"Error, por favor verifique los datos",
                    'confirmButtonText': 'Listo'
                });
            }
        });
    });

    $(document).on("click", "button[id^='editar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        modificar(identificador);
    });

 $(document).on("click", "button[id^='eliminar_']", function( index ) {
        var identificador = $(this).attr("id").split("_")[1];
        var sucursal      = $("#sucursal").val();
        if (!sucursal || sucursal == 0){
            swal({
                "title":"Verificar",
                'icon': 'warning',
                "text":"Seleccione una sucursal antes de eliminar el producto.",
                'confirmButtonText': 'Listo'
            });
            return;
        }
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
            if (stock === '' || stock === undefined) { stock = 0; }
            $.ajax({
              url: "/producto.eliminar.stock/" + identificador,
              method: "DELETE",
              data: { stock: stock, stock_minimo: 1, sucursal_id: sucursal },
              success: function(data, status){
                if(data && data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Eliminación realizada con éxito !",
                        'confirmButtonText': 'Listo',

                    });
                   // Grilla server-side: refrescar la página actual de DataTables.
                   if (window.tablaProductos) {
                       window.tablaProductos.ajax.reload(null, false);
                   } else {
                       $("#"+identificador).remove();
                   }

                }else{
                    swal({
                        "title":"Error",
                        'icon': 'error',
                        "text":"Error al realizar la eliminación",
                        'confirmButtonText': 'Listo'
                    });
                }
              },
              error: function(){
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
            $("#categoria").html("<option value='0'>Seleccione un proveedor</option>");
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

    $("#sucursal").change(function(){
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
            $("#categoria").val(jsonData.categorias_id + '_' + jsonData.abreviatura);
            $("#descripcion").val(jsonData.descripcion);
            $("#descripcion_en").val(jsonData.descripcion_en);
            $("#descripcion_pr").val(jsonData.descripcion_pr);
            $("#material").val(jsonData.material);
            $("#precio_mayorista").val(jsonData.precio_mayorista);
            if (jsonData.es_comodato) $("#es_comodato").prop('checked',true); else $("#es_comodato").prop('checked',false);;
            $("#proveedor").change();
            document.location.href="#bg-black-op";
           /* setTimeout(function(){ 
                $("#categoria").val(jsonData.categorias_id + '_' + jsonData.abreviatura);
            // $("#proveedor").attr("disabled","disabled");
            // $("#categoria").attr("disabled","disabled");
            },1000);   */
        });
    }
}
