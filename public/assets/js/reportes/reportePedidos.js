       jQuery("document").ready(function(){ 
              
      $("#btnBuscar").click(function(){
      document.location.href="reporte.pedidos?sucursal=" + $("#sucursal").val();
    });



});