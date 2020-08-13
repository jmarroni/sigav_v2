var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){
        $( "#codigo-barras" ).focus();

        jQuery("#concretar_venta").click(function(){    
            
            $.ajax({
                method: "POST",
                url: "nota_de_debito.php",
                datatype: 'json',
                data: {id: $("#factura").val(), observaciones: $("#observacion").val()}
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
                    }else{
                        var error = '';
                        if (msg.error) error = msg.error;
                        else error = msg; 
                        alert('Sucedio un error en la facturacion, no se emitio factura, por favor comuniquese con el administrador o verifique el error que nos indica AFIP, : ' + msg.mensaje + '. Recargaremos la web .-');
                    } 
                });
        });


        jQuery("#factura").change(function(){
            $.ajax({
                method: "POST",
                url: "get_nota_credito.php",
                datatype: 'json',
                data: {id: $(this).val()}
            })
            .done(function (msg) {
                $("#precio").val(msg.total);
                $("#fecha").val(msg.fecha);
            });
        });

        jQuery("#cantidad").keyup(function(){
            if ($(this).val() > 0 && precio > 0)
                $("#precio").html($(this).val() * precio);
            else
                $("#precio").html("0.00");
        });


        
    });

    function addRow(jsonData){
       var rowAdd =  '<tr id="' + jsonData.ventas_id + '">' +
        '<td class="text-center">' +
        '   <div style="width: 180px;">' +
        '   <img class="img-responsive" src="' + jsonData.imagen + '" alt="">' +
        '   </div>' +
        '   </td>' +
        '   <td>' +
        '   <h4>' + jsonData.producto_nombre + '</h4>' +
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
        '    <span class="h1 font-w700 text-success">$ ' + (jsonData.precio_unidad * jsonData.cantidad) + '</span>' +
        '</td>' +
        '</tr>';
        $("#tablaProductos").append(rowAdd);
        //Actualizo el total
        //total_ventas = total_ventas + (jsonData.precio_unidad * jQuery("#cantidad").val());
        //$("#total_ventas").html(total_ventas);
    }
