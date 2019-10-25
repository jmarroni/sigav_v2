<h1>Pasos para la instalacion del sistema</h1>

<ul>
<li>Descargar el repositorio de github</li>
<li>Clonar a base de datos</li>
<li>Colocar le usuario en el connection.php</li>
<li>Generar usuario admin, ej. INSERT INTO `usuarios` (`id`, `usuario`, `clave`, `rol_id`, `nombre`, `apellido`, `telefono`, `sucursal_id`) VALUES (NULL, 'jmarroni', 'fc93bf52212b8de1378e9cebac130e4af6690e65', '5', 'Juan Pablo', 'Marroni', '2920535353', '3');</li>
<li>.gitignore, crear los directorios que figuran en el mismo:
<br />- mkdir facturas
<br />- mkdir presupuesto
<br />- mkdir clientes
<br />- mkdir assets/perfil
<br />- mkdir upload_articles</li>
