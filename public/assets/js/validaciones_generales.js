jQuery("document").ready(function() {
  event.preventDefault();
  $(".letters").on("input", function(){
     var regexp = /[^a-zA-Z áéíóúÁÉÍÓÚüÜñÑ]/g;
     $(this).val($(this).val().toUpperCase());
     if($(this).val().match(regexp)){
       $(this).val( $(this).val().replace(regexp,'') );
   }
  });
  $(".numbers").on("input", function(){
     var regexp = /[^0-9]/g;
     if($(this).val().match(regexp)){
       $(this).val( $(this).val().replace(regexp,'') );
   }
  });
   $(".lettersNumbers").on("input", function(){
     $(this).val($(this).val().toUpperCase());
    });

});
function validarMail()
{
 event.preventDefault();
 var regex = /[\w-\.]{2,}@([\w-]{2,}\.)*([\w-]{2,}\.)[\w-]{2,4}/;

     if (regex.test($('#mail').val().trim()) && $('#mail').val()!="") 
         {
            //return true;
         } 
    else if ($('#mail').val()!="") 
        {
    swal({
        "title":"Verificar",
        'icon': 'warning',
        "text":"La dirección de correo no es válida",
        'confirmButtonText': 'Listo'
    });
    $('#mail').focus();
    //return false;
        }
}