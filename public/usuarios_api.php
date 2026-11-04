<?php
// La administración de usuarios de la API se migró a Laravel (UsuarioApiController).
header('Location: /usuarios-api', true, 301);
exit();
