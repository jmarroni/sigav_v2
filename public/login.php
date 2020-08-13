<?php
include('conection.php');
if ($_POST){
    
    $usuario    = $conn->real_escape_string($_POST["usuario"]);
    $clave      = $conn->real_escape_string($_POST["clave"]);

    $sql = "Select * FROM usuarios WHERE usuario = '$usuario' AND clave = '".sha1($clave.SEMILLA)."'";

    $resultado_stock = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
    if ($resultado_stock->num_rows > 0) {
        if ($row_stock = $resultado_stock->fetch_assoc()) {
            setcookie("kiosco",$row_stock["usuario"],time() + 3600*24,"/");
            setcookie("sucursal",setSucursal($row_stock["sucursal_id"]),time() + 3600*24,"/");
            setcookie("rol",setRol($row_stock["rol_id"]),time() + 3600*24,"/");
            if (!(isset($_COOKIE["lista_precio"]))) setcookie("lista_precio",1,time() + 3600*24,"/");
            switch ($row_stock["rol_id"]) {
                case '1':
                case '4':
                    header('Location: /ventas.php');
                    break;
                default:
                    header('Location: /ventas.php');
                    break;
            }
        }
        
    }else{
        $mensaje = "Error por favor verifica usuario y clave";
    }
}

if ($_GET){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    $usuario    = $conn->real_escape_string($_GET["usuario"]);
    $clave      = $conn->real_escape_string($_GET["clave"]);

    $sql = "Select * FROM usuarios WHERE usuario = '$usuario' AND clave = '".sha1($clave.SEMILLA)."'";

    $resultado_stock = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
    if ($resultado_stock->num_rows > 0) {
        if ($row_stock = $resultado_stock->fetch_assoc()) {
            echo '{"token": "'.sha1($row_stock["id"]).'", "sucursal": "'.$row_stock["sucursal_id"].'"}';
        }
        
    }else{
        echo '{"Error": "Usuario y/o Clave incorrectos"}';
    }
    exit();
}

$sql = "Select * FROM perfil";
$resultado_perfil = $conn->query($sql) or die("Error: " . $sql . "<br>" . $conn->error);
if ($resultado_perfil->num_rows > 0) {
    if ($row_perfil = $resultado_perfil->fetch_assoc()) {
        $logo = $row_perfil["logo"];
    }
}else{
    $logo = "/assets/img/photos/no-image-featured-image.png";
}
?>
<!DOCTYPE html>
<!--[if IE 9]>         <html class="ie9 no-focus" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-focus" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>Ingreso a SIGAV</title>

    <meta name="description" content="OneUI - Admin Dashboard Template &amp; UI Framework created by pixelcave and published on Themeforest">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="assets/img/favicons/favicon.png">

    <link rel="icon" type="image/png" href="assets/img/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="assets/img/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="assets/img/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="assets/img/favicons/favicon-160x160.png" sizes="160x160">
    <link rel="icon" type="image/png" href="assets/img/favicons/favicon-192x192.png" sizes="192x192">

    <link rel="apple-touch-icon" sizes="57x57" href="assets/img/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="assets/img/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/img/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/img/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/img/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/img/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon-180x180.png">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Web fonts -->
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">

    <!-- Bootstrap and OneUI CSS framework -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.css">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/flat.min.css"> -->
    <!-- END Stylesheets -->
</head>
<body>
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
<div id="page-container">
    <!-- Main Container -->
    <main id="main-container">


        <!-- Page Content -->
        <div class="content content-narrow">
<div class="row">
    <div class="col-lg-4 col-lg-offset-4">
                <img src="<?php echo $logo ?>" style="width: 50%;margin-left: 26%;margin-bottom: 14px;">
    </div>
    <div class="col-lg-4 col-lg-offset-4">
        <!-- Bootstrap Login -->
        <div class="block block-themed">
            <div class="block-header bg-primary">
                <ul class="block-options">
                    <li>
                        <button type="button" data-toggle="block-option" data-action="refresh_toggle" data-action-mode="demo"><i class="si si-refresh"></i></button>
                    </li>
                    <li>
                        <button type="button" data-toggle="block-option" data-action="content_toggle"><i class="si si-arrow-up"></i></button>
                    </li>
                </ul>
                <h3 class="block-title">Ingreso al sistema</h3>
            </div>
            <div class="block-content">
                <form class="form-horizontal push-5-t" action="login.php" method="post" >
                    <div class="form-group">
                        <label class="col-xs-12" for="usuario">Usuario</label>
                        <div class="col-xs-12">
                            <input class="form-control" type="text" id="usuario" name="usuario" placeholder="Ingrese nombre de usuario...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-12" for="login1-password">Password</label>
                        <div class="col-xs-12">
                            <input class="form-control" type="password" id="clave" name="clave" placeholder="Ingrese la clave...">
                        </div>
                    </div>
                    <?php if (isset($mensaje)){ ?>
                    <div class="form-group bg-danger" style="color:white;border-radius: 4px;">
                        <div class="col-xs-12">
                                <h3><?php echo $mensaje ?></h3>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-arrow-right push-5-r"></i> Log in</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- END Bootstrap Login -->
    </div>
</div>
        </div><!-- END Page Content -->
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer" class="content-mini content-mini-full font-s12 bg-gray-lighter clearfix">
s
        <div class="pull-left">
            <a class="font-w600" href="http://goo.gl/6LF10W" target="_blank">Jmarroni v1.0</a> &copy; <span class="js-year-copy"></span>
        </div>
    </footer>
    <!-- END Footer -->
</div>
<!-- END Page Container -->

<!-- OneUI Core JS: jQuery, Bootstrap, slimScroll, scrollLock, Appear, CountTo, Placeholder, Cookie and App.js -->
<script src="assets/js/core/jquery.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/core/jquery.slimscroll.min.js"></script>
<script src="assets/js/core/jquery.scrollLock.min.js"></script>
<script src="assets/js/core/jquery.appear.min.js"></script>
<script src="assets/js/core/jquery.countTo.min.js"></script>
<script src="assets/js/core/jquery.placeholder.min.js"></script>
<script src="assets/js/core/js.cookie.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
