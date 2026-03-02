var datos_a_transferir = new Array();
var columna = 0;
jQuery("document").ready(function() {


   setTimeout(function () {
    $("#add_success").hide('slow');
}, 3000);
   
   jQuery("#producto").autocomplete({
    source: function(request, response) {
        jQuery.get("/etiqueta.buscarProductos", {producto:  $("#producto").val(), sucursal: $("#sucursal_origen").val()
    }, function (data) {
        response(data);
    });
    },
    select: function( event, ui ) {
        $("#producto").val(ui.item.value);
        $("#producto_id").val(ui.item.id);
        $("#producto_imagen").val(ui.item.imagen);
        (ui.item.stock!=1)?$("#stock_disponible").text(ui.item.stock+" unidades"):$("#stock_disponible").text(ui.item.stock+" unidad");
        $("#cantidad_disponible").val(ui.item.stock);
    }
});

   jQuery("#sucursal_origen").change(function(){
    $("#sucursal_origen_label").val($("#sucursal_origen option:selected").text());

    if ( $("#sucursal_origen").val() === "") {
        $("#error_sucursal_origen").fadeIn();
        $("#error_sucursal_origen").removeClass("hidden");
    } else {
        $("#error_sucursal_origen").fadeOut();
    }

    if ( ($("#sucursal_origen").val() === $("#sucursal_destino").val()) && $("#sucursal_destino").val() !== "") {
        $("#error_sucursales_iguales").fadeIn();
        $("#error_sucursales_iguales").removeClass("hidden");
    } else {
        $("#error_sucursales_iguales").fadeOut();
    }
});

   jQuery("#sucursal_destino").change(function(){
    $("#sucursal_destino_label").val($("#sucursal_destino option:selected").text());

    if ( $("#sucursal_destino").val() === "") {
        $("#error_sucursal_destino").fadeIn();
        $("#error_sucursal_destino").removeClass("hidden");
    } else {
        $("#error_sucursal_destino").fadeOut();
    }

    if ( $("#sucursal_origen").val() === $("#sucursal_destino").val() && $("#sucursal_origen").val() !== "") {
        $("#error_sucursales_iguales").fadeIn();
        $("#error_sucursales_iguales").removeClass("hidden");
    } else {
        $("#error_sucursales_iguales").fadeOut();
    }
});

   jQuery("#add").click(function(){
    var agregarProducto = true;

    if ($("#producto_id").val() === "" || $("#producto").val() === "") {
        $("#error_producto").fadeIn();
        $("#error_producto").removeClass("hidden");
        agregarProducto = false;
    } else {
        $("#error_producto").fadeOut();
        if (verificarAgregado($("#producto_id").val())==1)
        {
           swal({
            "title":"Error",
            'icon': 'error',
            "text":"Ya ha agregado este producto",
            'confirmButtonText': 'Listo'
        });
           $("#producto").val('');
           $("#stock_a_transferir").val('');
           agregarProducto = false;
       }
   }

   if ($("#stock_a_transferir").val() <= 0) {
    $("#error_stock_a_transferir").fadeIn();
    $("#error_stock_a_transferir").removeClass("hidden");
    agregarProducto = false;
} else {
    $("#error_stock_a_transferir").fadeOut();
}

if ($("#cantidad_disponible").val()!=0 && $("#cantidad_disponible").val()!="")
{  
    if ($("#stock_a_transferir").val()!="" && $("#stock_a_transferir").val()!=0) 
    {  
        if( parseInt($("#stock_a_transferir").val()) > parseInt($("#cantidad_disponible").val()))
        {
           swal({
            "title":"Error",
            'icon': 'error',
            "text":"La cantidad a transferir debe ser menor o igual al stock disponible",
            'confirmButtonText': 'Listo'
        });
           agregarProducto = false;
           $("#stock_a_transferir").val("");
       }
   }
}
if (agregarProducto) {
    $("#tabla_add").html('<tr id="indice_' + columna + '_' + $("#producto_id").val() + '">' + 
        '<td class="text-center">' +
        '    <img class="img-avatar img-avatar48" src="' + $("#producto_imagen").val() + '" alt="">' +
        '</td>' +
        '<td class="font-w600">' + 
        $("#producto").val() + '</td>' +
        '<td>' + $("#stock_a_transferir").val() + '</td>' +
        '<td class="text-center">' +
        '    <div class="btn-group">' +
        '        <button class="btn btn-xs btn-default" onclick="eliminar(' + columna + ');" type="button" data-toggle="tooltip" title="" data-original-title="Eliminar"><i class="fa fa-times"></i></button>' +
        '    </div>' +
        '</td>' +
        '</tr>' + $("#tabla_add").html());
    
    datos_a_transferir.push(new Array($("#producto_id").val(),$("#stock_a_transferir").val(),columna));
    columna ++;
    $("#producto").val('');
    $("#stock_a_transferir").val('');
    $("#contenedor_confirmacion").html($("#tabla_responsive_productos").html());
            //$("#arrayproductos").val(datos_a_transferir);
        }
    });

   $("#btn_guardar").click(function(){
    var informacionValida=1;
    if($('#sucursal_origen').is(":visible"))
    {
        if($('#sucursal_origen').val()==0 || $('#sucursal_destino').val()==0)
           { swal({
            "title":"Error",
            'icon': 'error',
            "text":"Debe seleccionar la sucursal de origen y la de destino",
            'confirmButtonText': 'Listo'
        });
       $('#sucursal_origen').focus();
   } 
   informacionValida=0;
}
if (datos_a_transferir.length==0)
{
    informacionValida=0;
} 
if(informacionValida==1)
{
    $("#arrayproductos").val(datos_a_transferir.join('||'));
    var datos= $(".form-horizontal").serialize();
    if (confirm('¿Seguro desea realizar la transferencia de mercadería?')&& informacionValida==1){
        var url = "/transferencia-saving/";
        $.post(url,datos, function(data, status){  
            if (status === 'success') {
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Transferencia guardada exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                    $("#btn_guardar").hide();
                    window.open(data.comprobante,"blank");

                }
            }
        });
    } 
}
else
{
   swal({
     
    "title":"Error",
    'icon': 'error',
    "text":"Debe completar todos los datos de la transferencia!",
    'confirmButtonText': 'Listo',     
});
}

})
   $("#producto").click(function(){
       $("#error_producto").fadeOut();
       if($('#sucursal_origen').val()==0)
        { swal({
            "title":"Error",
            'icon': 'error',
            "text":"Debe seleccionar la sucursal de origen para obtener la lista de los productos disponibles para transferir",
            'confirmButtonText': 'Listo'
        });
    $('#sucursal_origen').focus();
}
}); 


   $("#stock_a_transferir").click(function(){
       $("#error_stock_a_transferir").fadeOut();
   }); 


   $(".wizard-next").click(function(){
       if($('#sucursal_origen').is(":visible"))
       {
        if($('#sucursal_origen').val()==0||$('#sucursal_destino').val()==0)
           { swal({
            "title":"Error",
            'icon': 'error',
            "text":"Debe seleccionar la sucursal de origen y la de destino",
            'confirmButtonText': 'Listo'
        });
       $('#sucursal_origen').focus();

   } 

}

});

});
function verificarAgregado(id)
{
    var existe=0;
    for (let index = 0; index < datos_a_transferir.length; index++) 
    {
        if (datos_a_transferir[index][0] == id){
         existe=1;
     }
 }
 return existe;
}
function eliminar(seleccion)
{
    for (let index = 0; index < datos_a_transferir.length; index++) 
    {
        if (datos_a_transferir[index][2] == seleccion){
            $("#indice_" + seleccion + '_' + datos_a_transferir[index][0]).html('');
            datos_a_transferir.splice(index,1);
        }
    }
    $("#contenedor_confirmacion").html($("#tabla_responsive_productos").html());
}
function cambiarEstado(identificador) {
 if(confirm("¿Esta seguro que quiere realizar este cambio? Los cambios serán permanentes")) {
    var estado = $("#estado"+identificador).val();
    var comentario = $("#comentario"+identificador).val();

    $.get('/transferencia.cambiarstatus', {id_transferencia: identificador, id_estado: estado, comentario: comentario }, 
        function(data, status) {
          if(data.proceso == "OK"){
            var mensaje = btoa("Estado modificado con &eacute;xito.-");
            document.location.href = "?mensaje=" + mensaje;
        }
    });
}
}
