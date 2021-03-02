    var detalleProductos = new Array();
    var totalFactura = 0;
    jQuery("document").ready(function(){

     setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
     $( "#codigo-barras" ).focus();

     jQuery('#anadir_producto').click(function(){
        if($("#cantidad").val() != 0 && $("#precio").val() > 0 && $("#codigo_barras").val()!="") {
            addRow();
            $("#codigo-barras").val('');
            $("#nombre-producto").val('');
            $("#producto_id").val('');
            $("#precio").val('');
            $("#cantidad").val('');
            $("#codigo-barras").focus();
        } else {
            alert('Debe completar todos los datos del producto');
        }
    });
     
     jQuery("#cantidad").keypress(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#precio").val()!="")
            {
            subtotal=$(this).val()*$("#precio").val();
            $("#subtotal").val(subtotal);
        }
    });

     jQuery("#precio").keypress(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#cantidad").val() !="")
            {
            subtotal=$(this).val()*$("#cantidad").val();
            $("#subtotal").val(subtotal);
        }
    });

     jQuery("#cantidad").keyup(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#precio").val()!="")
            {
            subtotal=$(this).val()*$("#precio").val();
            $("#subtotal").val(subtotal);
        }
    });

     jQuery("#precio").keyup(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#cantidad").val() !="")
            {
            subtotal=$(this).val()*$("#cantidad").val();
            $("#subtotal").val(subtotal);
        }
    });


     function log( message ) {
        $( "<div>" ).text( message ).prependTo( "#log" );
        $( "#log" ).scrollTop( 0 );
    }

    jQuery("#nombre-producto").autocomplete({
        source: function(request, response) {
            jQuery.get("/etiqueta.buscarProductos", {term:  $("#nombre-producto").val()
        }, function (data) {
            response(data);
        });
        },
        select: function( event, ui ) {
         $("#codigo-barras").val(ui.item.codigo_barras);
         $("#nombre-producto").val(ui.item.value);
         $("#producto_id").val(ui.item.id);
         $("#imagen-producto").val(ui.item.imagen);
               //$("#precio").val("0.00");
               //$("#subtotal").val("0.00");

           }
       });
    jQuery("#codigo-barras").autocomplete({
        source: function(request, response) {
            jQuery.get("/etiqueta.buscarProductos", {term:  $("#codigo-barras").val()
        }, function (data) {
            response(data);
        });
        },
        select: function( event, ui ) {
         $("#nombre-producto").val(ui.item.value);
         $("#producto_id").val(ui.item.id);
         $("#imagen-producto").val(ui.item.imagen);
               //$("#precio").val("0.00");
               //$("#subtotal").val("0.00");

           }
       });
    $("#btnguardar").click(function(){
      var nFilas = $("#tablaProductos tr").length;
      if($('#proveedor').val()!=0 && $('#numerofactura').val()!="" && nFilas>0)
      {

        $("#detalleProductos").val(detalleProductos.join('||'));
        $("#montoTotal").val($("#totalFactura").text());
        $(".form-horizontal").submit();

        var url = "/pagoProveedores.saveFactura/";
        $.post(url, function(data, status){  
            if (status === 'success') {
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"factura guardada exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                }
            }
        });
    } 
    else
    {
       swal({

        "title":"Error",
        'icon': 'error',
        "text":"Debe completar todos los datos de la factura!",
        'confirmButtonText': 'Listo',     
    });
   }

})

});





    function addRow(){
     var rowAdd =  '<tr id=producto' + $("#producto_id").val() + '>' +
     '<td class="text-center">' +
     '   <div style="width: 50px;">' +
     '   <img class="img-responsive" src="' + $("#imagen-producto").val() + '" alt="">' +
     '   </div>' +
     '</td>' +
     '<td>' +
     '   <h4>' + $("#nombre-producto").val() + '</h4>' +
     '</td>' +
     '<td>' +
     '    <p class="remove-margin-b">Cantidad: <span class="text-gray-dark">$ ' + $("#cantidad").val() + '</span></p>' +
     '</td>' +
     '<td>' +
     '    <p class="remove-margin-b">Precio: <span class="text-gray-dark">$ ' + $("#precio").val() + '</span></p>' +
     '</td>' +
     '<td class="text-center">' +
     '    <span class="h1 font-w700 text-success">$ ' + $("#subtotal").val() + '</span>' +
     '</td>' +
     '<td class="text-center">' +
     '    <button onclick="eliminar('+$("#producto_id").val() + ',' + $("#cantidad").val() + ',' + $("#precio").val()+')" class="btn btn-xs btn-default" type="button">Eliminar</button>' +
     '</td>' +
     '</tr>';
     $("#tablaProductos").append(rowAdd);
        //Actualizo el total
        totalFactura = totalFactura + parseFloat($("#subtotal").val());
        $("#totalFactura").html(totalFactura);
        detalleProductos.push(new Array($("#producto_id").val(),$("#cantidad").val(),$("#precio").val()));
    }

    function eliminar(producto_id,cantidad,precio){
        var subtotal=cantidad*precio;
        var totalanterior= parseFloat($("#totalFactura").text());
        var totalnuevo=totalanterior-subtotal;
        $("#totalFactura").html(totalnuevo);
        $("#producto"+producto_id).remove();

    }
    

