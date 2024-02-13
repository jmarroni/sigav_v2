var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){

        $('#tabla_compras').DataTable({
            "language": {
               "url": "/assets/language/Spanish.json"
           },
           dom: 'Bfrtip',
           buttons: [
               'copy', 'csv', 'excel', 'pdf', 'print'
           ]
        });

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
            var formulario_optica = $("#datos_completos").serialize();
            formulario_optica = formulario_optica + "&cliente=" + $("#cliente").val();
      
            $.ajax({
                method: "POST",
                url: "cerrar_pedido_optica.php?" + formulario_optica,
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
            if($("#producto_id").val() != "") {
                if($("#cantidad").val() != "") {
                    $.ajax({
                        method: "POST",
                        url: "pedidos_post.php",
                        datatype: 'json',
                        data: {id: $("#producto_id").val(), cantidad: $("#cantidad").val(), nro_pedido: $("#pedido_nro").val(), cliente : $("#cliente").val()}
                    })
                        .done(function (msg) {
                            devolucion = msg;
                            jQuery("#nombre-devuelto").html('Pedido del producto ' + devolucion.nombre + ' ingresada correctamente');
                            $("#add_success").show('slow');
                            setTimeout(function(){ $("#add_success").hide('slow');
                            jQuery("#nombre-devuelto").html(''); }, 3000);
                            addRow(devolucion);
                            $("#codigo-barras").val('');
                            $("#nombre-producto").val('');
                            $("#producto_id").val('');
                            $("#precio").html("0.00");
                            $("#cantidad").val('1');
                            $("#pedido_nro").val(devolucion.pedido_nro);
                            $("#codigo-barras").focus();

                        });
                } else {
                    alert('Verifica la cantidad ingresada es incorrecta');
                }
            } else {
                alert('Verifica el nombre del producto no es correcto');
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

        jQuery("a[id^=detalle_]").click(function(e){
            e.preventDefault();
            var detalle = $(this).attr('id').split('_')[1];
            console.log(detalle);
            $("#d_esf_modal").val($("#d_esf_" + detalle).val());
            $("#d_eje_modal").val($("#d_eje_" + detalle).val());
            $("#d_dip_modal").val($("#d_dip_" + detalle).val());
            $("#d_alt_pel_modal").val($("#d_alt_pel_" + detalle).val());
            $("#producto_modal").html($("#producto_" + detalle).val());
            $("#armazon_modal").html($("#armazon_" + detalle).val());
            $("#i_esf_modal").val($("#i_esf_" + detalle).val());
            $("#i_eje_modal").val($("#i_eje_" + detalle).val());
            $("#i_dip_modal").val($("#i_dip_" + detalle).val());
            $("#i_alt_pel_modal").val($("#i_alt_pel_" + detalle).val());

            $("#estados").modal("show");
        });


        var atributos = "";
        jQuery("span[id^='pendiente'").click(function(){
            atributos = $(this).attr('id').split('_');
            $("#etiqueta_caja").html("NRO. " + atributos[1] + " ACTUALMENTE " + $(this).html());
            $("#estados").modal('show');
            
        });

        jQuery("cambiar_estado").click(function(){
            if ($("#estado_nuevo").val() != ""){
                $.ajax({
                    url: "pedidos_post.php?estado_nuevo=" + $("#estado_nuevo").val() + "&pedido=" + atributos,
                    dataType : "json"
                }).done(function(response) {
                        $("#pendiente_" + atributo).html($("#estado_nuevo option:selected").text());
                });
            }
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