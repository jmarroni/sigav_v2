<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

$habilitado_lista_precio = 0;
$select = "SELECT * FROM modulos_habilitacion WHERE `key` = 'lista_precios' AND habilitado = 1;";
$resultado_perfil = $conn->query($select) or die("Error: " . $select . "<br>" . $conn->error);

if ($resultado_perfil->num_rows > 0) {
    $habilitado_lista_precio = 1;
    if (isset($_GET["lista_precios"])) setcookie("lista_precio",$_GET["lista_precios"],time() + 3600,"/");
}else{
    setcookie("lista_precio","1",time() + 3600,"/");
}

$sql = "Select * FROM perfil";
$resultado_perfil = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
if ($resultado_perfil->num_rows > 0) {
    if ($row_perfil = $resultado_perfil->fetch_assoc()) {
        $logo = $row_perfil["logo"];
        $nombre_fantasia = $row_perfil["nombre"];
    }
}else{
    $nombre_fantasia = "SIGAV";
}

function getMes($numero){
    switch ($numero) {
        case '1': return "Enero";break;
        case '2': return "Febrero";break;
        case '3': return "Marzo";break;
        case '4': return "Abril";break;
        case '5': return "Mayo";break;
        case '6': return "Junio";break;
        case '7': return "Julio";break;
        case '8': return "Agosto";break;
        case '9': return "Septiembre";break;
        case '10': return "Octubre";break;
        case '11': return "Noviembre";break;
        case '12': return "Diciembre";break;                                                                                                
        default:
            # code...
            break;
    }
}
?>
<!DOCTYPE html>
<!--[if IE 9]>         <html class="ie9 no-focus" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-focus" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>SIGAV</title>

    <meta name="description" content="Administrador de Ventas del Centro Cultural">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="/assets/img/favicons/favicon.png">

    <link rel="icon" type="image/png" href="/assets/img/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="/assets/img/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/assets/img/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="/assets/img/favicons/favicon-160x160.png" sizes="160x160">
    <link rel="icon" type="image/png" href="/assets/img/favicons/favicon-192x192.png" sizes="192x192">

    <link rel="apple-touch-icon" sizes="57x57" href="/assets/img/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/assets/img/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/img/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/img/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/assets/img/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/assets/img/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/img/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicons/apple-touch-icon-180x180.png">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Web fonts -->
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">

    <!-- Bootstrap and OneUI CSS framework -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" id="css-main" href="/assets/css/oneui.css">
    <link rel="stylesheet" id="css-main" href="/assets/css/jquery.dataTables.min.css">
	<script src="/assets/js/core/jquery.min.js"></script>
    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="/assets/css/themes/flat.min.css"> -->
    <!-- END Stylesheets -->
</head>
<body style="font-size: 12px !important;">
<!-- Page Container -->
<!--
    Available Classes:

    'enable-cookies'             Remembers active color theme between pages (when set through color theme list)

    'sidebar-l'                  Left Sidebar and right Side Overlay
    'sidebar-r'                  Right Sidebar and left Side Overlay
    'sidebar-mini'               Mini hoverable Sidebar (> 991px)
    'sidebar-o'                  Visible Sidebar by default (> 991px)
    'sidebar-o-xs'               Visible Sidebar by default (< 992px)

    'side-overlay-hover'         Hoverable Side Overlay (> 991px)
    'side-overlay-o'             Visible Side Overlay by default (> 991px)

    'side-scroll'                Enables custom scrolling on Sidebar and Side Overlay instead of native scrolling (> 991px)

    'header-navbar-fixed'        Enables fixed header
-->
<div id="page-container" class="sidebar-l side-scroll header-navbar-fixed">
    <!-- Side Overlay-->
    <aside id="side-overlay">
        <!-- Side Overlay Scroll Container -->
        <div id="side-overlay-scroll">
            <!-- Side Header -->
            <div class="side-header side-content">
                <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
                <button class="btn btn-default pull-right" type="button" data-toggle="layout" data-action="side_overlay_close">
                    <i class="fa fa-times"></i>
                </button>
                <span class="font-w600"></span>
            </div>
            <!-- END Side Header -->

            <!-- Side Content -->
            <div class="side-content remove-padding-t">
                <!-- Quick Settings -->
                <div class="block pull-r-l">
                    <div class="block-header bg-gray-lighter">
                        <ul class="block-options">
                            <li>
                                <button type="button" data-toggle="block-option" data-action="content_toggle"></button>
                            </li>
                        </ul>
                        <h3 class="block-title">Configuraciones</h3>
                    </div>
                    <div class="block-content">
                        <!-- Quick Settings Form -->
                        <form class="form-bordered" action="base_pages_dashboard.html" method="post" onsubmit="return false;">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="configuracion_afip.php" style="color:black;"><div class="font-s13 font-w600">AFIP</div></a>
                                        <div class="font-s13 font-w400 text-muted">Configuracion facturaci&oacute;n electr&oacute;n ica</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="perfil.php" style="color:black;"><div class="font-s13 font-w600">PERFIL</div></a>
                                        <div class="font-s13 font-w400 text-muted">Logo, nombre fantasia, etc.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="configuracion_racks.php" style="color:black;"><div class="font-s13 font-w600">Dep&oacute;sito</div></a>
                                        <div class="font-s13 font-w400 text-muted">Configuraci&oacute;n de Racks</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="configuracion_servicios.php" style="color:black;"><div class="font-s13 font-w600">Servicios</div></a>
                                        <div class="font-s13 font-w400 text-muted">Configuraci&oacute;n de Servicios</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="configuracion_mail.php" style="color:black;"><div class="font-s13 font-w600">Mail Receptor</div></a>
                                        <div class="font-s13 font-w400 text-muted">Configuraci&oacute;n de Mail</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xs-10">
                                    <a href="#" id="cerrar_session" style="color:black;"><div class="font-s13 font-w600">Cerrar Sesi&oacute;n </div></a>
                                        <div class="font-s13 font-w400 text-muted">Salir del sistema.</div>
                                    </div>
                                </div>
                            </div>

                        </form>
                        <!-- END Quick Settings Form -->
                    </div>
                </div>
                <!-- END Quick Settings -->
            </div>
            <!-- END Side Content -->
        </div>
        <!-- END Side Overlay Scroll Container -->
    </aside>
    <!-- END Side Overlay -->

    <!-- Header -->
    <header id="header-navbar">
        <div class="content-mini content-mini-full content-boxed">
            <!-- Header Navigation Right -->
            <ul class="nav-header pull-right">
                <li class="js-header-search header-search remove-margin">
                    <?php if ($habilitado_lista_precio == 1){ ?>
                        <form class="form-horizontal" action="" method="post">
                            <div class="form-group">
                                <select class="form-control" id="lista_precios" name="lista_precios">
                                    <option value="1" <?php echo (isset($_GET["lista_precios"])&&($_GET["lista_precios"] == 1))?"selected='selected'":''; ?>>Lista Minorista</option>
                                    <option value="2" <?php echo (isset($_GET["lista_precios"])&&($_GET["lista_precios"] == 2))?"selected='selected'":''; ?>>Lista Mayorista</option>
                                </select>
                            </div>
                        </form>
                    <?php }else{ ?>
                    &nbsp;
                    <?php }?>
                </li>
                <li> 
                    <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
                    <button class="btn btn-default btn-image" data-toggle="layout" data-action="side_overlay_toggle" type="button">
                        <img src="/assets/img/avatars/avatar9.jpg" alt="Avatar"><i class="fa fa-ellipsis-v" style="margin-left:-10px;"></i>&nbsp;<?php echo $_COOKIE["kiosco"]; ?>&nbsp;
                    </button>
                </li>
            </ul>
            <!-- END Header Navigation Right -->

            <!-- Header Navigation Left -->
            <ul class="nav-header pull-left">
                <li class="header-content">
                    <a class="h5" href="/ventas.php">
                        <span class="h4 font-w600 text-primary-dark"></span><i class="fa fa-circle-o-notch text-primary"></i> <span class="h4 font-w600 text-primary-dark"> <?php echo $nombre_fantasia; ?></span>
                    </a>
                </li>
                <li>
                    <!-- Themes functionality initialized in App() -> uiHandleTheme() -->
                    <div class="btn-group">
                        <ul class="dropdown-menu sidebar-mini-hide font-s13">
                            <li>
                                <a data-toggle="theme" data-theme="default" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-default pull-right"></i> <span class="font-w600">Default</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="theme" data-theme="/assets/css/themes/amethyst.min.css" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-amethyst pull-right"></i> <span class="font-w600">Amethyst</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="theme" data-theme="/assets/css/themes/city.min.css" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-city pull-right"></i> <span class="font-w600">City</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="theme" data-theme="/assets/css/themes/flat.min.css" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-flat pull-right"></i> <span class="font-w600">Flat</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="theme" data-theme="/assets/css/themes/modern.min.css" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-modern pull-right"></i> <span class="font-w600">Modern</span>
                                </a>
                            </li>
                            <li>
                                <a data-toggle="theme" data-theme="/assets/css/themes/smooth.min.css" tabindex="-1" href="javascript:void(0)">
                                    <i class="fa fa-circle text-smooth pull-right"></i> <span class="font-w600">Smooth</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
            <!-- END Header Navigation Left -->
        </div>
    </header>
    <!-- END Header -->
    <!-- Main Container -->
    <main id="main-container">
        <!-- Sub Header -->
        <div class="bg-gray-lighter visible-xs">
            <div class="content-mini content-boxed">
                <button class="btn btn-block btn-default visible-xs push" data-toggle="collapse" data-target="#sub-header-nav">
                    <i class="fa fa-navicon push-5-r"></i>Menu
                </button>
            </div>
        </div>
        <div class="bg-primary-lighter collapse navbar-collapse remove-padding" id="sub-header-nav">
            <div class="content-mini content-boxed">
                <ul class="nav nav-pills nav-sub-header push">
                    <?php if (getRol() > 4 || getRol() == 1) { ?>
                        <li class="<?php echo $menu["ventas"]; ?>">
                            <a href="/ventas.php">
                                <i class="fa fa-dashboard push-5-r"></i>Ventas
                            </a>
                        </li>
                    <?php } ?>
                   
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-area-chart push-5-r"></i>Reportes <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reportes.php">Reporte de Ventas</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reportes_proveedores.php">Reporte de Proveedores</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol()==1 || getRol()==4 || getRol()==5) { ?>
                            <li>
                                <a href="/reporte.factura">Reporte de Facturaci&oacute;n</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4 || getRol()==3) { ?>
                            <li>
                                <a href="/reporte.stocks">Reporte de Stock</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reporte.presupuesto">Reporte de Presupuestos</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reporte.notasCredito">Reporte de Nota de credito</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reportes_servicios.php">Reporte de Servicios</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4 || getRol()==3) { ?>
                             <li>
                                <a href="/reporte.pagoProveedores">Reporte de Pago Proveedores</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 4 || getRol()==3) { ?>
                            <li>
                                <a href="/reporte.transferencias">Reporte de Transferencias</a>
                            </li>
                            <?php } ?>
                              <?php if (getRol() > 4) { ?>
                            <li>
                                <a href="/reporte.pedidos">Reporte de Pedidos</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                   
                   <?php  if (getRol() > 2) { ?>
                    <li class="dropdown <?php echo (isset($menu["proveedores"]))?$menu["proveedores"]:""; ?>">
                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-product-hunt push-5-r"></i>Proveedores<span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                            <a href="/pagoProveedores">Pago</a>
                            </li>
                        </ul>
                    </li>
                    <?php 
                    }
                    if (getRol() > 1) { ?>
                        <li class="dropdown <?php echo (isset($menu["actualizaciones"]))?$menu["actualizaciones"]:""; ?>"> 
                                <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-product-hunt push-5-r"></i>Carga / Impresiones<span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="/carga" >Productos</a>
                                    </li>
                                    <li>
                                        <a href="/proveedor">Proveedor</a>
                                    </li>
                                    <li>
                                        <a href="/categoria">Categor&iacute;as</a>
                                    </li>
                                    <li>
                                        <a href="/etiqueta">Impresi&oacute;n de etiquetas</a>
                                    </li>
                                </ul>
                            </li>
                    <?php } ?>
                    <li class="dropdown <?php echo (isset($menu["stock_sucursales"]))?$menu["stock_sucursales"]:""; ?>">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-address-card-o push-5-r"></i>Sucursales<span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (getRol() > 3) { ?>
                                <li>
                                    <a href="/sucursales.php">Sucursales</a>
                                </li>
                                <?php } ?>
                                <?php if (getRol() > 2) { ?>
                                <li>
                                    <a href="/transferencia">Transferencias</a>
                                </li>
                                <?php } ?>
                                <li>
                                    <a href="/transferencias.realizadas">Transferencias realizadas</a>
                                </li>
                            </ul>
                        </li>
                    <?php if (getRol() > 4) { ?>
                    <li class="dropdown <?php echo (isset($menu["usuario"]))?$menu["usuario"]:""; ?>">
                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-lock push-5-r"></i>Usuario/Permisos<span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="/usuario" >Usuarios</a>
                            </li>
                            <?php if (getRol() > 3) { ?>
                            <li>
                                <a href="/rol">Rol</a>
                            </li>
                            <?php } ?>
                            <?php if (getRol() > 2) { ?>
                            <li>
                                <a href="/seccion.php">Secci&oacute;n</a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li> 
                    <li class="dropdown <?php echo (isset($menu["clientes"]))?$menu["clientes"]:""; ?>">
                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-lock push-5-r"></i>Clientes<span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="/cliente" >Alta/Baja/Modificaci&oacute;n</a>
                            </li>
                            <li>
                                <a href="/pedido">Pedidos</a>
                            </li>
                            <li>
                                <a href="/relacion_cliente_servicios.php">Servicios Relacionados</a>
                            </li>
                            <li>
                                <a href="/estado_de_cuenta.php">Estado de Cuenta</a>
                            </li>
                        </ul>
                    </li>
                    <?php } ?>
                    <?php if (getRol() > 4 || getRol() == 1) { ?>
                    <li class="<?php echo (isset($menu["operaciones"]))?$menu["operaciones"]:""; ?>">
                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-lock push-5-r"></i>Operaciones<span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="/cierreCajaReporte" >Caja</a>
                            </li>
                            <li>
                                <a href="/devoluciones.php">Devoluciones</a>
                            </li>
                            <li>
                                <a href="/nota_debito.php">Nota D&eacute;bito</a>
                            </li>
                            <li>
                                <a href="/usuarios_api.php">Api</a>
                            </li>   
                               <li>
                                <a href="/logsProductos">Logs Productos stock</a>
                            </li>
                             <li>
                                <a href="/logsProductosCostosPrecios">Logs Productos Costos y Precios</a>
                            </li>   
                             </li>   
                               <li>
                                <a href="/logsCategorias">Logs Categor&iacute;as</a>
                            </li> 
                            <li>
                                <a href="/logsTransferencias">Logs Transferencias</a>
                            </li> 
                        </ul>
                    </li> 
                    
                    <?php } ?>
                    <?php if (getRol() > 4){ ?>
                    <li class="<?php echo (isset($menu["cta_corriente"]))?$menu["cta_corriente"]:""; ?>">
                        <a href="/cta_corriente.php">
                            <i class="fa fa-address-card-o push-5-r"></i>Obsequios
                        </a>
                    </li>      
                    <?php } ?>
                    <!--
                    <li>
                        <a href="bd_sales.html">
                            <i class="fa fa-paypal push-5-r"></i>Sales
                        </a>
                    </li>
                    <li>
                        <a href="bd_settings.html">
                            <i class="fa fa-cog push-5-r"></i>Settings
                        </a>
                    </li>-->
                </ul>
            </div>
        </div>
        <!-- END Sub Header -->
<script>

$("#lista_precios").change(function(){
    document.location.href = "?lista_precios=" + $(this).val();
});

$("#cerrar_session").click(function(){
    document.location.href = "/cerrar_session.php";
})
</script>
 
<!-- Smartsupp Live Chat script -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = '74260743aa31ca10a9aa0e8b4516650f28e02570';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>