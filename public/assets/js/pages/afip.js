
jQuery("document").ready(function() {

    // Cambio de modo AFIP con confirmacion
    $("#modo_produccion").change(function(){
        var modo = $(this).prop("checked") ? 'produccion' : 'homologacion';
        var mensaje = modo === 'produccion'
            ? '¿Desea activar el modo PRODUCCION?\n\nLas facturas emitidas tendran validez fiscal real ante AFIP.\n\nAsegurese de tener configuradas las credenciales de produccion.'
            : '¿Desea activar el modo HOMOLOGACION?\n\nLas facturas emitidas seran de PRUEBA y NO tendran validez fiscal.';

        if (!confirm(mensaje)) {
            $(this).prop("checked", !$(this).prop("checked"));
            return;
        }

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: { modo_afip: modo }
        })
        .done(function( msg ) {
            location.reload();
        })
        .fail(function(error){
            console.log(error);
            alert('Error al cambiar el modo');
        });
    });

    // Guardar Access Token
    $("#guardar_access_token").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: {
                guardar_access_token: 1,
                access_token: $("#access_token").val()
            }
        })
        .done(function(msg) {
            alert(msg);
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Access Token');
            setTimeout(function(){ location.reload(); }, 1000);
        })
        .fail(function(error){
            console.log(error);
            alert('Error al guardar el access token');
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Access Token');
        });
    });

    // Guardar configuracion general
    $("#guardar_config").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: {
                guardar_config: 1,
                cuit: $("#cuit").val(),
                ptovta: $("#ptovta").val(),
                comprobante: $("#comprobante").val(),
                ingresos_brutos: $("#ingresos_brutos").val(),
                inicio_actividades: $("#inicio_actividades").val(),
                condicion_iva: $("#condicion_iva").val()
            }
        })
        .done(function(msg) {
            $("#add_success").show();
            $("#nombre-devuelto").html(msg);
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Configuracion General');
            setTimeout(function(){ location.reload(); }, 1500);
        })
        .fail(function(error){
            console.log(error);
            alert('Error al guardar la configuracion');
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Configuracion General');
        });
    });

    // Guardar credenciales HOMOLOGACION
    $("#guardar_homologacion").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: {
                guardar_homologacion: 1,
                cert_homologacion: $("#cert_homologacion").val(),
                key_homologacion: $("#key_homologacion").val()
            }
        })
        .done(function(msg) {
            $("#resultado_homologacion").html('<div class="alert alert-info">' + msg + '</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Credenciales Homologacion');
            setTimeout(function(){ location.reload(); }, 1500);
        })
        .fail(function(error){
            console.log(error);
            $("#resultado_homologacion").html('<div class="alert alert-danger">Error al guardar</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Credenciales Homologacion');
        });
    });

    // Guardar credenciales PRODUCCION
    $("#guardar_produccion").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: {
                guardar_produccion: 1,
                cert_produccion: $("#cert_produccion").val(),
                key_produccion: $("#key_produccion").val()
            }
        })
        .done(function(msg) {
            $("#resultado_produccion").html('<div class="alert alert-info">' + msg + '</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Credenciales Produccion');
            setTimeout(function(){ location.reload(); }, 1500);
        })
        .fail(function(error){
            console.log(error);
            $("#resultado_produccion").html('<div class="alert alert-danger">Error al guardar</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Credenciales Produccion');
        });
    });

    // Probar conexion HOMOLOGACION
    $("#probar_homologacion").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Probando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: { probar_conexion: 'homologacion' }
        })
        .done(function(msg) {
            var alertClass = msg.indexOf('Error') >= 0 ? 'alert-danger' : 'alert-success';
            $("#resultado_homologacion").html('<div class="alert ' + alertClass + '">' + msg + '</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Probar Conexion');
        })
        .fail(function(error){
            console.log(error);
            $("#resultado_homologacion").html('<div class="alert alert-danger">Error de conexion</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Probar Conexion');
        });
    });

    // Probar conexion PRODUCCION
    $("#probar_produccion").click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Probando...');

        $.ajax({
            method: "POST",
            url: "/guardar_certificados.php",
            data: { probar_conexion: 'produccion' }
        })
        .done(function(msg) {
            var alertClass = msg.indexOf('Error') >= 0 ? 'alert-danger' : 'alert-success';
            $("#resultado_produccion").html('<div class="alert ' + alertClass + '">' + msg + '</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Probar Conexion');
        })
        .fail(function(error){
            console.log(error);
            $("#resultado_produccion").html('<div class="alert alert-danger">Error de conexion</div>').show();
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Probar Conexion');
        });
    });

    // Emision online toggle
    $("#emision_online").change(function(){
        var emitir = $(this).prop("checked") ? 1 : 0;
        $.ajax({
            method: "POST",
            url: "/emitir_online.php",
            data: { emitir_online: emitir }
        })
        .done(function(msg) {})
        .fail(function(error){
            console.log(error);
        });
    });

    // Solicitar datos toggle
    $("#solicitar_datos").change(function(){
        var solicitar_datos = $(this).prop("checked") ? 1 : 0;
        $.ajax({
            method: "POST",
            url: "/solicitar_datos.php",
            data: { solicitar: solicitar_datos }
        })
        .done(function(msg) {})
        .fail(function(error){
            console.log(error);
        });
    });

    $("#close").click(function(){
        $(".modal").hide();
    });
});
