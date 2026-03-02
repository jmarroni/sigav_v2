    var detalleProductos = new Array();
    var totalFactura = 0;
    jQuery("document").ready(function(){

       setTimeout(function () {
        $("#add_success").hide('slow');
    }, 3000);
       $( "#codigo-barras" ).focus();

       jQuery('#anadir_producto').click(function(){
        if($("#cantidad").val() != 0 && $("#precio").val() > 0 && $("#codigo_barras").val()!="") {
            if (verificarAgregado($("#producto_id").val())==1)
            {
               swal({
                "title":"Error",
                'icon': 'error',
                "text":"Ya ha agregado este producto",
                'confirmButtonText': 'Listo'
            });

           }
           else
           {          
            addRow();
            $("#codigo-barras").val('');
            $("#nombre-producto").val('');
            $("#producto_id").val('');
            $("#costo").val('');
            $("#precio").val('');
            $("#cantidad").val('');
            $("#subtotal").val('');
            $("#codigo-barras").focus();
        }
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

       jQuery("#costo").keypress(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#cantidad").val() !="")
        {
            subtotal=$(this).val()*$("#cantidad").val();
            $("#subtotal").val(subtotal);
        }
    });

       jQuery("#cantidad").keyup(function(){
        var subtotal=0;
        if ($(this).val() !="" && $("#costo").val()!="")
        {
            subtotal=$(this).val()*$("#costo").val();
            $("#subtotal").val(subtotal);
        }
    });

       jQuery("#costo").keyup(function(){
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
            jQuery.get("/buscarProductos", {term:  $("#nombre-producto").val()
        }, function (data) {
            response(data);
        });
        },
        select: function( event, ui ) {
           $("#codigo-barras").val(ui.item.codigo_barras);
           $("#nombre-producto").val(ui.item.value);
           $("#producto_id").val(ui.item.id);
         //$("#imagen-producto").val("/assets/img/photos/no-image-featured-image.png");
               //$("#precio").val("0.00");
               //$("#subtotal").val("0.00");

           }
       });
    jQuery("#codigo-barras").autocomplete({
        source: function(request, response) {
            jQuery.get("/buscarProductos", {term:  $("#codigo-barras").val()
        }, function (data) {
            response(data);
        });
        },
        select: function( event, ui ) {
           $("#codigo-barras").val(ui.item.codigo_barras);
           $("#nombre-producto").val(ui.item.value);
           $("#producto_id").val(ui.item.id);
         //$("#imagen-producto").val("/assets/img/photos/no-image-featured-image.png");
               //$("#precio").val("0.00");
               //$("#subtotal").val("0.00");

           }
       });
    $("#btnguardar").click(function(){
      var nFilas = $("#tablaProductos tr").length;
      if($('#proveedor').val()!=0 && $('#numerofactura').val()!="" && nFilas>0 && $('#fecha').val()!="")
      {

        $("#detalleProductos").val(detalleProductos.join('||'));
        $("#montoTotal").val($("#totalFactura").text());
        var datos=new FormData(document.getElementById("formPago"));
        //var datos= $(".form-horizontal").serialize();
        console.log(datos);

        var url = "/pagoProveedores.saveFactura/";
        // $.post(url, datos, function(data, status){  
            $.ajax({
                url: url,
                type: "post",
                dataType: "html",
                data: datos,
                cache: false,
                contentType: false,
         processData: false
            })
            .done(function(data){
            // if (status === 'success') {
                var res=JSON.parse(data);
                console.log(res.proceso);
                console.log(res.comprobante);
                if(res.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Factura guardada exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                $("#iframeComprobante").attr("src",res.comprobante);
                $("#btnguardar").hide();
                   
                }
            //}
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
       '   <img class="img-responsive" src="/assets/img/photos/no-image-featured-image.png" alt="">' +
       '   </div>' +
       '</td>' +
       '<td>' +
       '   <h4>' + $("#nombre-producto").val() + '</h4>' +
       '</td>' +
       '<td>' +
       '    <p class="remove-margin-b">Cantidad: <span class="text-gray-dark">$ ' + $("#cantidad").val() + '</span></p>' +
       '</td>' +
       '<td>' +
       '    <p class="remove-margin-b">Costo: <span class="text-gray-dark">$ ' + $("#costo").val() + '</span></p>' +
       '</td>' +
       '<td class="text-center">' +
       '    <span class="h1 font-w700 text-success">$ ' + $("#subtotal").val() + '</span>' +
       '</td>' +
       '<td>' +
       '    <p class="remove-margin-b">Precio: <span class="text-gray-dark">$ ' + $("#precio").val() + '</span></p>' +
       '</td>' +
       '<td class="text-center">' +
       '    <button onclick="eliminar('+$("#producto_id").val() + ',' + $("#cantidad").val() + ',' + $("#costo").val()+')" class="btn btn-xs btn-default" type="button">Eliminar</button>' +
       '</td>' +
       '</tr>';
       $("#tablaProductos").append(rowAdd);
        //Actualizo el total
        totalFactura = totalFactura + parseFloat($("#subtotal").val());
        $("#totalFactura").html(totalFactura);
        detalleProductos.push(new Array($("#producto_id").val(),$("#cantidad").val(),$("#costo").val(),$("#precio").val()));
    }

    function eliminar(producto_id,cantidad,costo){
        var subtotal=cantidad*costo;
        var totalanterior= parseFloat($("#totalFactura").text());
        var totalnuevo=totalanterior-subtotal;
        $("#totalFactura").html(totalnuevo);
        $("#producto"+producto_id).remove();
        eliminarProducto(producto_id);
    }

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


