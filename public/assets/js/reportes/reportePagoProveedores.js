       jQuery("document").ready(function(){ 
              
      $("#btnBuscar").click(function(){
      document.location.href="reporte.pagoProveedores?sucursal=" + $("#sucursal").val();
    });



});