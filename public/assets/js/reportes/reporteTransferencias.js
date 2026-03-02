       jQuery("document").ready(function(){ 
              
      $("#btnBuscar").click(function(){
      document.location.href="reporte.transferencias?sucursal=" + $("#sucursal").val();
    });



});