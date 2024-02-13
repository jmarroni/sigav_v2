var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){
        $( "#codigo-barras" ).focus();

        jQuery("#concretar_venta").click(function(){    
            
            $.ajax({
                method: "POST",
                url: "nota_de_credito.php",
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
                url: "get_factura.php",
                datatype: 'json',
                data: {id: $(this).val()}
            })
            .done(function (msg) {
                $("#tablaProductos").html("");
                for (let index = 0; index < msg.items.length; index++) {
                    console.log(msg.items[index]);
                    addRow(msg.items[index]);
                }
                console.log(msg.items[0].tipo_pago);
                // TIPO PAGO
                if (msg.items[0].tipo_pago == 1) $("#efectivo").prop('checked',true);
                if (msg.items[0].tipo_pago == 2) $("#debito").prop('checked',true);
                if (msg.items[0].tipo_pago == 1612) $("#efectivo").prop('checked',true);
                if (msg.items[0].tipo_pago == 3) $("#credito").prop('checked',true);
                // IVA
                if (msg.items[0].iva == 1) $("#resp_i").prop('checked',true);
                if (msg.items[0].iva == 2) $("#mono").prop('checked',true);
                if (msg.items[0].iva == 3) $("#excento").prop('checked',true);
                if (msg.items[0].iva == 4) $("#final").prop('checked',true);
                console.log(msg.items[0].fecha.substring(1,10));
                $("#nombre-cliente").val(msg.items[0].nombre);
                $("#direccion-cliente").val(msg.items[0].direccion);
                $("#tipo").val(msg.items[0].tipo_documento);
                $("#documento-cliente").val(msg.items[0].documento);
                $("#fecha").val(msg.items[0].fecha.substring(0,10));
                $("#precio").val(msg.items[0].total);
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
