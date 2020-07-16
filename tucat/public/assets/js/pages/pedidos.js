var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){
        $( "#codigo-barras" ).focus();

        jQuery("#concretar_venta").click(function(){    
            var medio_de_pago = "0";
            if ($("#debito").prop("checked")) medio_de_pago = $("#debito").val();
            if ($("#efectivo").prop("checked")) medio_de_pago = $("#efectivo").val();
            if ($("#credito").prop("checked")) medio_de_pago = $("#credito").val();       
            $.ajax({
                method: "POST",
                url: "facturar.php?tipo=" + medio_de_pago + "&presupuesto=0",
                datatype: 'json'
            })
                .done(function (msg) {
                    if (msg.factura){
                        $("#factura_iframe").show();
                        $("#iframe").attr("src",msg.factura);
                        setTimeout(function(){
                            $("#tablaProductos").html("");
                            $("#total_ventas").html(0);
                            $("#iframe")[0].contentWindow.print();
                            
                        },2000);
                        
                    }  
                });
        });

        jQuery("#presupuesto").click(function(){    
            var medio_de_pago = "0";
            if ($("#debito").prop("checked")) medio_de_pago = $("#debito").val();
            if ($("#efectivo").prop("checked")) medio_de_pago = $("#efectivo").val();
            if ($("#credito").prop("checked")) medio_de_pago = $("#credito").val();       
            $.ajax({
                method: "POST",
                url: "cerrar_pedido.php",
                datatype: 'json'
            })
                .done(function (msg) {
                    if (msg.factura){
                        $("#factura_iframe").show();
                        $("#iframe").attr("src",msg.factura);
                        setTimeout(function(){
                            $("#tablaProductos").html("");
                            $("#total_ventas").html(0);
                        },1000);
                        
                    }  
                });
        });

        
        jQuery('#anadir_pedido').click(function(){
            var cliente = $("#cliente_alta").serialize();
            // primero me fijo si doy de alta el cliente o existe
            console.log('sssss');
            if ($("#clientes_id").val() == ''){
                 $.ajax({
                        method: "POST",
                        url: "cliente_post.php",
                        datatype: 'json',
                        data: cliente + '&externo=si'
                    }).done(function (msg) {
                        var jsonRespuesta = JSON.parse(msg);
                        $("#clientes_id").val(jsonRespuesta.id);
                        altaItem();
                    });
            }else{
                    altaItem();
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

        jQuery( "#nombre-producto" ).autocomplete({
            source: "search.php",
            minLength: 2,
            select: function( event, ui ) {
                $("#nombre-producto").val(ui.item.value);
                $("#producto_id").val(ui.item.id);
                precio = ui.item.precio;
                if ($("#cantidad").val() > 0 && precio > 0)
                $("#precio").html($("#cantidad").val() * precio);
            else
                $("#precio").html("0.00");
            }
        });

        jQuery( "#nombre-producto" ).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
    
            var $li = $('<li>'),
                $img = $('<img>');
        
        
            $img.attr({
              src: item.imagen,
              alt: item.label
            });
        
            $li.attr('data-value', item.label);
            $img.css('width', '50px');
            $img.css('padding', '2px');
            $li.append('<a href="#">');
            $li.find('a').append($img).append(item.label);    
        
            return $li.appendTo(ul);
          };

        jQuery( "#codigo-barras" ).change(function(){
            if ($(this).val() != ''){
            $.ajax({
                  url: "search_codigo.php?term=" + $(this).val(),
                  dataType : "json"
                }).done(function(response) {
                    if (response.length > 0) {
                        $("#nombre-producto").val(response[0].value);
                        $("#producto_id").val(response[0].id);
                        precio = response[0].precio;
                        if ($("#cantidad").val() > 0 && precio > 0)
                        $("#precio").html($("#cantidad").val() * precio);
                        jQuery('#anadir_venta').click();
                        $(this).val() = '';
                        $(this).focus();
                    }else{
                        $("#nombre-devuelto-error").html("Error producto no encontrado, por favor buscalo por nombre o ingresalo.");
                        $("#add_success_error").show('slow');
                        setTimeout(function(){ $("#add_success_error").hide('slow');jQuery("#nombre-devuelto-error").html(''); }, 3000);                    
                    }
                });
            }
        });


        var atributos = "";
        jQuery("span[id^='pendiente'").click(function(){
            atributos = $(this).attr('id').split('_');
            $("#etiqueta_caja").html("NRO. " + atributos[1] + " ACTUALMENTE " + $(this).html());
            $("#estados").modal('show');
            
        });

        jQuery("#cambiar_estado").click(function(){
            if ($("#estado_nuevo").val() != ""){
                $.ajax({
                    url: "pedidos_post.php?estado_nuevo=" + $("#estado_nuevo").val() + "&pedido=" + atributos[1],
                    dataType : "json"
                }).done(function(response) {
                        $("#pendiente_" + atributos[1]).html($("#estado_nuevo option:selected").text());
                        $("#estados").modal('hide');
                });
            }
        });
    });

    function altaItem(){
        var pedido = $("#item_pedido").serialize();
        $.ajax({
                method: "POST",
                url: "pedidos_post.php",
                datatype: 'json',
                data: pedido + '&clientes_id = ' + $("#clientes_id").val()
            })
            .done(function (msg) {
                document.location.href = '/pedidos.php';

            });
    }

    function addRow(jsonData){
       var rowAdd =  '<tr id="' + jsonData.ventas_id + '">' +
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
        '    <button onclick="eliminar(' + jsonData.ventas_id + ',' + jQuery("#cantidad").val() + ',' + jsonData.id + ')">Eliminar</button>' +
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
    }

    function eliminar(ventas,cantidad,producto_id){
        if (confirm("Seguro de eliminar el producto ? Preguntate porque no lo vendiste primero ;)")) {
                    $.ajax({
                        method: "POST",
                        url: "eliminar_venta.php",
                        data: {id: ventas, cantidad: cantidad, producto_id: producto_id}
                    })
                    .done(function (msg) {
                        $("#" + ventas).hide("slow");
                    }).fail(function(){$("#" + ventas).hide("slow");});
        }
    }