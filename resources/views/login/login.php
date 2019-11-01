<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<script src="/assets/js/core/jquery.min.js"></script>
</head>
<body>
	<h1>Registrarse</h1>
	<form method="POST" action="<?php echo url('/api/auth/signup')?>">
		<?php csrf_field() ?>
		<input type="text" id="name" name="name" placeholder="name">
		<input type="email" id="email" name="email" placeholder="email">
		<input type="password" id="password" name="password" placeholder="password">
		<input type="checkbox" id="cbox">
		<button type="submit" id="registro">Registro</button>
		<button type="submit" id="login">Login</button>
		<button type="submit" id="user">Devolver usuario</button>
		<button type="submit" id="logout">Cerrar sesion</button>
		<button type="submit" id="productos">Productos</button>
	</form>

	<script>
		var token = '';

		$("#registro").click( function() {
			event.preventDefault();
			var name = $("#name").val();
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://localhost:8000/api/auth/signup',
				method: 'get',
				data: { name: name, email: email, password: password },
				dataType: 'json',
				success: function(msg) {
					console.log(msg);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#login").click( function() {
			event.preventDefault();
			var cbox = $("#cbox").is(":checked");;
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://localhost:8000/api/auth/login',
				method: 'get',
				data: { email: email, password: password, remember_me: cbox },
				dataType: 'json',
				success: function(msg) {
					token += msg.access_token;
					console.log(token);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#user").click( function() {
			event.preventDefault();
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://localhost:8000/api/auth/user',
				method: 'get',
				data: { email: email, password: password },
				// data: token,
				dataType: 'json',
				success: function(msg) {
					console.log(msg);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#logout").click( function() {
			event.preventDefault();
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://localhost:8000/api/auth/logout',
				method: 'get',
				data: { email: email, password: password },
				// data: token,
				dataType: 'json',
				success: function(msg) {
					console.log(msg);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#productos").click( function() {
			event.preventDefault();
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://localhost:8000/api/auth/productos',
				method: 'get',
				data: { email: email, password: password },
				dataType: 'json',
				success: function(msg) {
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

