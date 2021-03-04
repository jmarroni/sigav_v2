       jQuery("document").ready(function(){ 
              
      $("#btnBuscar").click(function(){
      document.location.href="reporte.stocks?sucursal=" + $("#sucursal").val();
    });



});