    // Id de la venta
    var venta_id = "";  

    var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    //Array para guardar los productos que se van agregando a la factura para poder buscar si ya un producto ha sido agregado
    var detalleProductos = new Array();
    jQuery("document").ready(function(){

        $("#nombre-cliente").keyup(function(e){
            if(e.keyCode == 8){
                $("#clientes_id").val("");
            }
        });

        $( "#codigo-barras" ).focus();

        jQuery("#concretar_venta").click(function(){    
            var medio_de_pago = "0";
            if ($("#debito").prop("checked")) medio_de_pago = $("#debito").val();
            if ($("#efectivo").prop("checked")) medio_de_pago = $("#efectivo").val();
            if ($("#credito").prop("checked")) medio_de_pago = $("#credito").val(); 
            if ($("#transferencia").prop("checked")) medio_de_pago = $("#transferencia").val(); 
            
            var iva = "0";
            if ($("#resp_i").prop("checked"))   iva = $("#resp_i").val();
            if ($("#mono").prop("checked"))     iva = $("#mono").val();
            if ($("#excento").prop("checked"))  iva = $("#excento").val();  
            if ($("#final").prop("checked"))    iva = $("#final").val();  
            $.ajax({
                method: "GET",
                url: "facturar.php?tipo=" + medio_de_pago + 
                "&presupuesto=0&nombre=" + $("#nombre-cliente").val() + 
                '&documento='  + $("#documento-cliente").val() + 
                '&tipo-documento='  + $("#tipo").val() +
                '&fecha-facturacion='  + $("#fecha").val() + 
                '&iva=' + iva +
                '&venta_id=' + venta_id +
                '&clientes_id=' + $("#clientes_id").val() + 
                '&direccion=' + $("#direccion-cliente").val(),                  
                datatype: 'json'
            })
            .done(function (msg) {
                if (msg.factura){
                    $("#factura_iframe").show();
                    $("#iframe").attr("src",msg.factura);
                    venta_id = '';
                    setTimeout(function(){
                        $("#tablaProductos").html("");
                        $("#total_ventas").html(0);
                        total_ventas=0;
                        $("#cantidad").html(0);
                        $("#precio").html(0);
                        $("#iframe")[0].contentWindow.print();
                    }, 2000);
                     total_ventas=0;
                        $("#precio").html("0.00");
                        $("#cantidad").val('1');
                }else{
                    var error = '';
                    if (msg.error) error = msg.error;
                    else error = msg; 
                    alert('Sucedió un error en la facturación, no se emitió factura, por favor comuníquese con el administrador o verifique el error que nos indica AFIP, : ' + msg.mensaje + '. Recargaremos la web .-');
                    document.location.reload();
                } 
            });
        });


        jQuery("#enviar_mail").click(function(){
            if ($("#mail_factura").val() == ""){
                alert('Debe completar el email para realizar el envio');
            }else{
                $.ajax({
                    method: "GET",
                    url: "enviar_por_mail.php?mail=" + $("#mail_factura").val() + "&factura=" + $("#iframe").attr("src"),
                    datatype: 'json'
                })
                .done(function (msg) {
                    if (msg.indexOf("Message has been sent") > -1){
                        $("#mensaje_enviado").show();
                        setTimeout(function(){
                            $("#mensaje_enviado").hide();
                        },4000);
                    }
                });
            }
        });

        jQuery("#presupuesto").click(function() {  
            var medio_de_pago = "0";
            $("#presupuesto").hide();
            $("#espere_venta_activa").show();
            if ($("#debito").prop("checked")) medio_de_pago = $("#debito").val();
            if ($("#efectivo").prop("checked")) medio_de_pago = $("#efectivo").val();
            if ($("#credito").prop("checked")) medio_de_pago = $("#credito").val();       
            if ($("#transferencia").prop("checked")) medio_de_pago = $("#transferencia").val();
            var iva = "0";
            if ($("#resp_i").prop("checked"))   iva = $("#resp_i").val();
            if ($("#mono").prop("checked"))     iva = $("#mono").val();
            if ($("#excento").prop("checked"))  iva = $("#excento").val();
            if ($("#final").prop("checked"))    iva = $("#final").val();
            var descontar_stock=0;
            if ($("#descontar_stock").prop("checked")) descontar_stock = 1;  
            //alert(descontar_stock);


            $.ajax({
                url: "facturar.php?tipo=" + medio_de_pago + 
                "&presupuesto=1&nombre=" + $("#nombre-cliente").val() + 
                '&documento='  + $("#documento-cliente").val() + 
                '&tipo-documento='  + $("#tipo").val() +
                '&fecha-facturacion='  + $("#fecha").val() + 
                '&iva=' + iva +
                '&clientes_id=' + $("#clientes_id").val() +
                "&direccion=" + $("#direccion-cliente").val() +
                '&venta_id=' + venta_id+
                '&descontar_stock=' + descontar_stock,
                datatype: 'json'
            })
            .done(function (msg) {
                if (msg.factura){
                    $("#espere_venta_activa").hide();
                    $("#presupuesto").show();
                    venta_id = '';
                    $("#factura_iframe").show();
                    $("#iframe").attr("src",msg.factura);
                    setTimeout(function(){
                        $("#tablaProductos").html("");
                        $("#total_ventas").html(0);
                        total_ventas=0;
                        $("#precio").html("0.00");
                        $("#cantidad").val('1');
                    },1000);
                }  
            });
        });

        
        jQuery('#anadir_venta').click(function(){
            var cant=$("#cantidad").val()!=''?$("#cantidad").val():0;
            var stock=$("#stockactual").val()!=''?$("#stockactual").val():0;
            cant=parseInt(cant);
            stock=parseInt(stock);
            if((cant !=0 && cant<= stock) &&  ($("#producto_id").val()!="")) {
              // alert('paso');
              if (verificarAgregado($("#producto_id").val())==1)
              {
                 alert('Ya ha agregado este producto');
                    $("#codigo-barras").val('');
                    $("#nombre-producto").val('');
                    //$("#producto_id").val('');
                    $("#precio").val('1');
                    $("#cantidad").val('1');
                    $("#stockactual").val('');
                    $("#codigo-barras").focus();

             }
             else
             { 
                $.ajax({
                    method: "POST",
                    url: "ventas_post.php",
                    datatype: 'json',
                    data: {id: $("#producto_id").val(), venta_id: venta_id, cantidad: $("#cantidad").val(), nombreproducto: $("#nombre-producto").val(), precio: $("#precio").val() }
                })
                .done(function (msg) {
                    devolucion = msg;
                    venta_id = msg.ventas_id;
                    jQuery("#nombre-devuelto").html('Venta del producto ' + msg.nombre + ' ingresada correctamente');
                    $("#add_success").show('slow');
                    setTimeout(function(){ $("#add_success").hide('slow');jQuery("#nombre-devuelto").html(''); }, 3000);
                    addRow(devolucion);
                    $("#codigo-barras").val('');
                    $("#nombre-producto").val('');
                    $("#producto_id").val('');
                    $("#precio").val('1');
                    $("#cantidad").val('1');
                    $("#stockactual").val('');
                    $("#codigo-barras").focus();
                });
            } 
        } else{
        if(($("#producto_id").val()!="") && (cant>stock)) {
           alert('No puede facturar una cantidad mayor al stock disponible');
       }
       
    if ($("#producto_id").val()=="" || $("#cantidad").val() == ""  || $("#precio").val() == ""){
        alert('Debe completar todos los datos del producto para agregarlo a la factura');
    }
    if ( $("#cantidad").val() == 0 || $("#precio").val() == 0){
        alert('La cantidad y monto deben ser mayores a cero');
    }
}
});

        jQuery("#cantidad").keyup(function(){
            if ($(this).val() > 0 && precio > 0)
                $("#precio").html($(this).val() * precio);
            else
                $("#precio").html("0.00");
        });


        function log( message ) {
            $( "<div>" ).text( message ).prependTo( "#log" );
            $( "#log" ).scrollTop( 0 );
        }

        jQuery("#nombre-producto").autocomplete({
            source: function(request, response) {
                jQuery.get("/etiqueta.buscarProductos", {producto:  $("#nombre-producto").val(), tipoBusqueda:1
            }, function (data) {
                response(data);
            });
            },
            select: function( event, ui ) {
             $("#codigo-barras").val(ui.item.codigo_barras);
             $("#nombre-producto").val(ui.item.value);
             $("#producto_id").val(ui.item.id);
             $("#stockactual").val(ui.item.stockactual);
             //precio = ui.item.precio;
               //if ($("#cantidad").val() > 0 && precio > 0)
                $("#precio").val(ui.item.precio);
           // else
               // $("#precio").val("0.00");
            
        }
    });
        jQuery("#codigo-barras").autocomplete({
            source: function(request, response) {
                jQuery.get("/etiqueta.buscarProductos", {producto:  $("#codigo-barras").val(), tipoBusqueda:1
            }, function (data) {
                response(data);
            });
            },
            select: function( event, ui ) {
             $("#nombre-producto").val(ui.item.value);
             $("#producto_id").val(ui.item.id);
             $("#stockactual").val(ui.item.stockactual);
            //precio = ui.item.precio;
               //if ($("#cantidad").val() > 0 && precio > 0)
                $("#precio").val(ui.item.precio);
            //else
              //  $("#precio").val("0.00");
            
        }
    });




        // jQuery( "#nombre-producto" ).autocomplete({
        //     source: "search.php?term=" + $(this).val(),
        //     minLength: 2,
        //     select: function( event, ui ) {
        //         $("#nombre-producto").val(ui.item.value);
        //         $("#producto_id").val(ui.item.id);
        //         precio = ui.item.precio;
        //         if ($("#cantidad").val() > 0 && precio > 0)
        //         $("#precio").val($("#cantidad").val() * precio);
        //     else
        //         $("#precio").val("0.00");
        //     }
        // });

        jQuery("#nombre-cliente").autocomplete({
            source: "get_cliente.php",
            minLength: 2,
            select: function( event, ui ) {
                $("#nombre-cliente").val(ui.item.razon_social);
                $("#clientes_id").val(ui.item.id);
                $("#direccion-cliente").val(ui.item.domicilio_legal + " " + ui.item.localidad + " " + ui.item.provincia);
                $("#documento-cliente").val(ui.item.cuit);

                switch (ui.item.condicion_iva) {
                    case '1': $("#resp_i").prop("checked","true");break;
                    case '2': $("#mono").prop("checked","true");break;
                    case '3': $("#excento").prop("checked","true");break;
                    case '4': $("#final").prop("checked","true");break;
                    default:
                    $("#final").prop("checked","true");
                    break;
                }
            }
        });

        // jQuery( "#nombre-producto" ).data( "ui-autocomplete" )._renderItem = function( ul, item ) {

        //     var $li = $('<li>'),
        //         $img = $('<img>');
        
        
        //     $img.attr({
        //       src: item.imagen,
        //       alt: item.label
        //     });
        
        //     $li.attr('data-value', item.label);
        //     $img.css('width', '50px');
        //     $img.css('padding', '2px');
        //     $li.append('<a href="#">');
        //     $li.find('a').append($img).append(item.label);    
        
        //     return $li.appendTo(ul);
        //   };

        // jQuery( "#codigo-barras" ).change(function(){
        //     if ($(this).val() != ''){
        //     $.ajax({
        //           url: "search_codigo.php?term=" + $(this).val(),
        //           dataType : "json"
        //         }).done(function(response) {
        //             if (response.length > 0) {
        //                 $("#nombre-producto").val(response[0].value);
        //                 $("#producto_id").val(response[0].id);
        //                 precio = response[0].precio;
        //                 if ($("#cantidad").val() > 0 && precio > 0)
        //                 $("#precio").val($("#cantidad").val() * precio);
        //                 jQuery('#anadir_venta').click();
        //                 $(this).val() = '';
        //                 $(this).focus();
        //             }else{
        //                 $("#nombre-devuelto-error").html("Error producto no encontrado, por favor buscalo por nombre o ingresalo.");
        //                 $("#add_success_error").show('slow');
        //                 setTimeout(function(){ $("#add_success_error").hide('slow');jQuery("#nombre-devuelto-error").html(''); }, 3000);                    
        //             }
        //         });
        //     }
        // });
    });

    function addRow(jsonData){
     var rowAdd =  '<tr id="' + jsonData.id + '">' +
     '<td class="text-center">' +
     '   <div style="width: 180px;">' +
     '   <img class="img-responsive" src="' + jsonData.imagen + '" alt="">' +
     '   </div>' +
     '   </td>' +
     '   <td>' +
     '   <h4>' + jsonData.nombre + '</h4>' +
     '<p class="remove-margin-b">Producto Vendido a las ' + jsonData.fecha + '</p>' +
     '<a class="font-w600" href="javascript:void(0)">Por ' + jsonData.usuario + '</a>' +
     '    </td>' +
     '    <td>' +
     '    <p class="remove-margin-b">Precio: <span class="text-gray-dark">$ ' + jsonData.precio_unidad + '</span></p>' +
     '    <p>Quedan en Stock: <span class="text-gray-dark">' + jsonData.stock_sucursal + '</span></p>' +
     '    <button onclick="eliminar(' + jsonData.ventas_id + ',' + jQuery("#cantidad").val() + ',' + jsonData.id + ','+jsonData.precio_unidad+')">Eliminar</button>' +
     '<button class="btn btn-xs btn-default" type="button">' +
     '    </td>' +
     '    <td class="text-center">' +
     '    <span class="h1 font-w700 text-success">$ ' + (jsonData.precio_unidad * jQuery("#cantidad").val()) + '</span>' +
     '</td>' +
     '</tr>';
     $("#tablaProductos").append(rowAdd);
        //Actualizo el total
        total_ventas = total_ventas + (jsonData.precio_unidad * jQuery("#cantidad").val());
        $("#total_ventas").html(total_ventas);
        detalleProductos.push(new Array($("#producto_id").val(),$("#cantidad").val(),$("#precio").val()));
    }

    function eliminar(ventas,cantidad,producto_id,precio){
        if (confirm("Seguro de eliminar el producto? Preguntate porque no lo vendiste primero ;)")) {
            $.ajax({
                method: "POST",
                url: "eliminar_venta.php",
                data: {id: venta_id, cantidad: cantidad, producto_id: producto_id}
            })
            .done(function (msg) {
                $("#" + producto_id).hide("slow");
                total_ventas = total_ventas - (parseFloat(precio)*parseFloat(cantidad));
                $("#total_ventas").html(total_ventas);
                eliminarProducto(producto_id);

            }).fail(function() { 
                $("#" + producto_id).hide("slow");
                total_ventas = total_ventas - (parseFloat(precio)*parseFloat(cantidad));
                $("#total_ventas").html(total_ventas);
                eliminarProducto(producto_id);
            });
        }
    }

    $("#emision_online").change(function(){
        var emitir = 0;
        if ($(this).prop("checked")){
            emitir = 1;
        }
        $.ajax({  // armar el post de la factura
            method: "POST",
            url: "/emitir_online.php",
            data: { emitir_online:emitir}
        })
        .done(function( msg ) {
            if (emitir){
                $("#presupuesto").hide();
                $("#emitir_online").show();
                $("#concretar_venta").show();
            }else{
                $("#presupuesto").show();
                $("#emitir_online").hide();
                $("#concretar_venta").hide();
            }
        })
        .fail(function(error){
            console.log(error);
        });
    });

    $("#descontar_stock").change(function(){
        if ($(this).prop("checked")){
         document.cookie = "descontarstock=1";
     } 
     else
     {
      document.cookie = "descontarstock=0";
  }

});

    function verificarAgregado(id)
    {
        var existe=0;
        for (let index = 0; index < detalleProductos.length; index++) 
        {
            if (detalleProductos[index][0] == id){
             existe=1;
         }
     }
     return existe;
 }
 function eliminarProducto(id)
 {
    var existe=0;
    for (let index = 0; index < detalleProductos.length; index++) 
    {
        if (detalleProductos[index][0] == id){
         detalleProductos.splice(index,1);
         }
    }
}
