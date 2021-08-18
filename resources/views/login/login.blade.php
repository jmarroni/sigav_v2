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

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/flat.min.css"> -->
    <!-- END Stylesheets -->
    <style type="text/css">
        #page-footer{         
            /*  poner footer abajo */
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
        }
    </style>>
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
                   <img src="/assets/perfil/20190524111724-54396137.jpeg" style="width: 50%;margin-left: 26%;margin-bottom: 14px;">

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
                                <label class="col-xs-12" for="usuario">Email</label>
                                <div class="col-xs-12">
                                    <input class="form-control" type="text" id="email" name="email" placeholder="Ingrese su email">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12" for="login1-password">Password</label>
                                <div class="col-xs-12">
                                    <input class="form-control" type="password" id="password" name="password" placeholder="Ingrese la clave...">
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
                                    <button class="btn btn-sm btn-primary" id="login" type="submit"><i class="fa fa-arrow-right push-5-r"></i> Log in</button>
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

    <div class="pull-left">
        <a class="font-w600" href="http://goo.gl/6LF10W" target="_blank">Jmarroni v1.0</a> &copy; <span class="js-year-copy"></span>
    </div>
</footer>
<!-- END Footer -->
</div>
<!-- END Page Container -->
<!-- OneUI Core JS: jQuery, Bootstrap, slimScroll, scrollLock, Appear, CountTo, Placeholder, Cookie and App.js -->
   <script src="/assets/js/core/jquery.min.js"></script>
  <script>
   $("#login").click( function() {
            event.preventDefault();
            var email = $("#email").val();
            var password = $("#password").val();

            $.ajax({
                url: '/api/auth/validaracceso',
                method: 'post',
                data: { email: email, password: password },
                dataType: 'json',
                success: function(msg) {
                    token += msg.access_token;
                    console.log(msg);
                },
                fail: function(msg) {
                    console.log(msg);
                }
            });
        });
   </script>

</body>
</html>
