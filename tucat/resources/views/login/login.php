<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<script src="/assets/js/core/jquery.min.js"></script>
</head>
<body>
	<h1>Registrarse</h1>
	<form method="POST" action="<?php echo url('/oauth/clients')?>">
		<?php csrf_field() ?>
		<input type="text" id="name" name="name" placeholder="name">
		<input type="email" id="email" name="email" placeholder="email">
		<input type="password" id="password" name="password" placeholder="password">
		<!-- <button type="submit" id="registro">Registro</button> -->
		<button type="submit" id="login">Login</button>
		<button type="submit" id="user">Devolver usuario</button>
		<!-- <button type="submit" id="logout">Cerrar sesion</button> -->
		<button type="submit" id="productos">Productos</button>
		<button type="submit" id="sucursales">Sucursales</button>
		<button type="submit" id="productos_sucursal">Productos por sucursal</button>
	</form>

	<script>
		var token = '';
		var user_id = '';
		var sucursal = '';

		$("#registro").click( function() {
			event.preventDefault();
			var name = $("#name").val();
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://www.mercado-artesanal.com.ar/api/auth/signup',
				method: 'post',
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
			var email = $("#email").val();
			var password = $("#password").val();

			$.ajax({
				url: 'http://www.mercado-artesanal.com.ar/api/auth/login',
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

		$("#user").click( function() {
			event.preventDefault();

			$.ajax({
				url: 'http://www.mercado-artesanal.com.ar/api/auth/user',
				method: 'post',
				headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    },
				data: { token: token },
				dataType: 'json',
				success: function(msg) {
					user_id = msg.user.id;
					console.log(msg);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#sucursales").click( function() {
			event.preventDefault();

			$.ajax({
				url: 'http://www.mercado-artesanal.com.ar/api/auth/sucursales',
				method: 'post',
				headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    },
				data: { token: token, user_id: user_id},
				dataType: 'json',
				success: function(msg) {
					if (msg[0]) {
						sucursal = msg[0].nombre;
					}

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
				url: 'http://www.mercado-artesanal.com.ar/api/auth/logout',
				method: 'post',
				headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    },
				data: token,
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
				url: 'http://www.mercado-artesanal.com.ar/api/auth/productos',
				method: 'post',
				headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    },
				data: token,
				dataType: 'json',
				success: function(msg) {
					console.log(msg);
				},
				fail: function(msg) {
					console.log(msg);
				}
			});
		});

		$("#productos_sucursal").click( function() {
			event.preventDefault();

			$.ajax({
				url: 'http://www.mercado-artesanal.com.ar/api/auth/productosPorSucursal',
				method: 'post',
				headers: {
			        "Accept": "application/json",
			        "Authorization": "Bearer " + token
			    },
				data: { token: token, nombre_sucursal: sucursal, user_id: user_id },
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

