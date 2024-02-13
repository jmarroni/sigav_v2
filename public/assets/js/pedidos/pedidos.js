 var detalleProductos = new Array();
 var numItem=0;
  var montoTotal = 0;
  jQuery("document").ready(function(){
   jQuery('#anadir_producto').click(function(){
    if($("#item").val() != "" && $("#costo").val() !="") {          
        addRow();
        $("#item").val('');
        $("#costo").val('');
    }
 else {
    alert('Debe completar todos los datos del item');
}
});
    $("#enviar").click(function(event){
        event.preventDefault();
        var nFilas = $("#tablaItems tr").length;
        if ($("#tipodocumento").val()=="" || $("#paciente").val()=="" || $("#domicilio").val()=="" || $("#telefono").val()=="" || $("#doctor").val()=="" || $("#obra_social").val()==""  || $("#numero_asociado").val()=="" || $("#fecha_recepcion").val()=="" || $("#pedido").val()=="" || $("#retira").val()=="")
        { 
           swal({
            "title":"Verificar",
            'icon': 'warning',
            "text":"Debe completar todos los campos obligatorios (*)",
            'confirmButtonText': 'Listo'
        });
       }
       else
       { 
        $("#detalleProductos").val(detalleProductos.join('||'));
        $("#montoTotal").val($("#totalFactura").text());
        var datos = $("#pedido-form").serialize();
        datos=datos+
        "&l_d_esf="+$("#l_d_esf").text()+
        "&l_d_cil="+$("#l_d_cil").text()+
        "&l_d_eje="+$("#l_d_eje").text()+
        "&l_d_dip="+$("#l_d_dip").text()+
        "&l_producto="+$("#l_producto").text()+
        "&l_armazon="+$("#l_armazon").text()+
        "&c_producto="+$("#c_producto").text()+
        "&c_armazon="+$("#c_armazon").text()+
        "&l_i_esf="+$("#l_i_esf").text()+
        "&l_i_cil="+$("#l_i_cil").text()+
        "&l_i_eje="+$("#l_i_eje").text()+
        "&l_i_dip="+$("#l_i_dip").text()+
        "&c_d_esf="+$("#c_d_esf").text()+
        "&c_d_cil="+$("#c_d_cil").text()+
        "&c_d_eje="+$("#c_d_eje").text()+
        "&c_d_dip="+$("#c_d_dip").text()+
        "&c_i_esf="+$("#c_i_esf").text()+
        "&c_i_cil="+$("#c_i_cil").text()+
        "&c_i_eje="+$("#c_i_eje").text()+
        "&c_i_dip="+$("#c_i_dip").text();
        //console.log(datos);
       // console.log(detalleProductos[0][0]);
         var url = "/pedido.save/";
        $.post(url,datos, function(data, status){  
            //var res=JSON.parse(data);
             //console.log(data.proceso);
                //console.log(data.comprobante);
            // if (status === 'success') {
                if(data.proceso == "OK"){
                    swal({
                        "title":"Perfecto !!",
                        'icon': 'success',
                        "text":"Pedido guardado exitosamente!",
                        'confirmButtonText': 'Listo',     
                    });
                    $("#iframeComprobante").attr("src",data.comprobante);
                    $("#enviar").hide();
                    //window.open(data.comprobante,"blank");

                }
           // }
        });
          
       }
    });
});
   function addRow(){
    numItem=numItem+1;
     var rowAdd =  '<tr id=item' + numItem + '>' +
     '<td>' +
     '   <h4>' + $("#item").val() + '</h4>' +
     '</td>' +
     '<td>' +
     '    <p class="remove-margin-b">Costo: <span class="text-gray-dark">$ ' + $("#costo").val() + '</span></p>' +
     '</td>' +
     '<td class="text-center">' +
       '    <button onclick="eliminar('+numItem+ ',' + $("#costo").val() + ')" class="btn btn-xs btn-default" type="button">Eliminar</button>' +
       '</td>' +
     '</tr>';
     $("#tablaItems").append(rowAdd);
        //Actualizo el total
        montoTotal = montoTotal + parseFloat($("#costo").val());
        $("#totalFactura").html(montoTotal);
        detalleProductos.push(new Array(numItem,$("#item").val(),$("#costo").val()));
    }
 function eliminar(id,costo){
        var subtotal=costo;
        var totalanterior= parseFloat($("#totalFactura").text());
        montoTotal=montoTotal-subtotal;
        var totalnuevo=totalanterior-subtotal;
        $("#totalFactura").html(totalnuevo);
        $("#item"+id).remove();
        eliminarItem(id);
    }
    function verificarAgregado(nombre)
    {
        var existe=0;
        for (let index = 0; index < detalleProductos.length; index++) 
        {
            if (detalleProductos[index][1] == nombre){
             existe=1;
         }
     }
     return existe;
 }
 function eliminarItem(id)
 {
    var existe=0;
    for (let index = 0; index < detalleProductos.length; index++) 
    {
        if (detalleProductos[index][0] == id){
         detalleProductos.splice(index,1);
         }
    }
}