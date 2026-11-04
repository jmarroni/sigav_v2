var precio = 0;
    var devolucion = '';
    var total_ventas = 0;
    jQuery("document").ready(function(){
        $( "#codigo-barras" ).focus();

        // Emite la nota de crédito. El botón queda deshabilitado mientras la
        // petición está en vuelo y DESPUÉS de emitir: un segundo clic no puede
        // generar otra nota (incidente 2026-09-23). Solo se rehabilita cuando
        // el servidor confirma que NO se emitió nada.
        var ncEnVuelo = false;
        jQuery("#concretar_venta").click(function(){
            var $btn = $(this);
            if (ncEnVuelo || $btn.prop("disabled")) return;
            if (!$("#factura").val() || $("#factura").val() == "0") {
                alert("Seleccione la factura a anular.");
                return;
            }
            ncEnVuelo = true;
            $btn.prop("disabled", true);
            $("#factura").prop("disabled", true);

            $.ajax({
                method: "POST",
                url: "nota_de_credito.php",
                dataType: 'json',
                data: {id: $("#factura").val(), observaciones: $("#observacion").val()}
            })
            .done(function (msg) {
                    ncEnVuelo = false;
                    $("#factura").prop("disabled", false);
                    if (msg && msg.factura){
                        $("#factura_iframe").show();
                        $("#iframe").attr("src",msg.factura);
                        setTimeout(function(){
                            $("#tablaProductos").html("");
                            $("#total_ventas").html(0);
                            $("#iframe")[0].contentWindow.print();
                        },2000);
                    } else if (msg && msg.emitida_sin_pdf) {
                        alert(msg.mensaje);
                    } else {
                        var mensaje = (msg && msg.mensaje) ? msg.mensaje : ((msg && msg.error) ? msg.error : 'Error desconocido');
                        alert('No se emitió la nota de crédito: ' + mensaje);
                        // Solo se rehabilita si el servidor confirmó que NO se emitió nada.
                        if (msg && msg.error !== 'No se pudo confirmar la emisión') $btn.prop("disabled", false);
                    }
                })
            .fail(function () {
                // Resultado desconocido: no rehabilitar.
                ncEnVuelo = false;
                alert('Error de comunicación al emitir la nota de crédito. NO vuelva a intentar hasta verificar en el reporte de notas de crédito si se emitió; si se repite, avise al administrador.');
            });
        });

        // Al cambiar de factura se vuelve a habilitar la emisión.
        jQuery("#factura").change(function(){
            if (!ncEnVuelo) $("#concretar_venta").prop("disabled", false);
            $.ajax({
                method: "POST",
                url: "get_factura.php",
                datatype: 'json',
                data: {id: $(this).val()}
            })
            .done(function (msg) {
                $("#tablaProductos").html("");
                for (let index = 0; index < msg.items.length; index++) {
                    addRow(msg.items[index]);
                }
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
    }
