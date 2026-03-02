# API REST - Sistema de Gestión de Inventario

## Autenticación

La API utiliza **OAuth 2.0** con Laravel Passport. Todos los endpoints (excepto login) requieren un token Bearer.

---

## Endpoints

### 1. Login - Obtener Token

```
POST /api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "email": "usuario@ejemplo.com",
    "password": "contraseña"
}
```

**Respuesta exitosa (200):**
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6...",
    "token_type": "Bearer",
    "expires_at": "2026-01-07 12:00:00"
}
```

**Respuesta error (401):**
```json
{
    "message": "Unauthorized"
}
```

> **Nota:** El token expira en 24 horas.

---

### 2. Listar Productos

```
POST /api/auth/productos
```

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Body:** *(vacío o {})*

**Respuesta exitosa (201):**
```json
[
    {
        "id": 1,
        "codigo_barras": "7790001234567",
        "nombre": "Lentes de Sol Ray-Ban",
        "precio_unidad": 45000.00,
        "costo": 30000.00,
        "stock": 25,
        "stock_minimo": 5,
        "usuario": "admin",
        "fecha": "2026-01-05",
        "precio_mayorista": 38000.00,
        "es_comodato": 0,
        "categoria": "Lentes de Sol",
        "nombre_proveedor": "Juan",
        "apellido_proveedor": "Pérez",
        "imagenes": [
            "http://dominio.com/storage/productos/imagen1.jpg",
            "http://dominio.com/storage/productos/imagen2.jpg"
        ]
    },
    {
        "id": 2,
        "codigo_barras": "7790009876543",
        "nombre": "Armazón Oakley",
        "precio_unidad": 35000.00,
        "costo": 22000.00,
        "stock": 15,
        "stock_minimo": 3,
        "usuario": "admin",
        "fecha": "2026-01-04",
        "precio_mayorista": 30000.00,
        "es_comodato": 0,
        "categoria": "Armazones",
        "nombre_proveedor": "María",
        "apellido_proveedor": "García",
        "imagenes": []
    }
]
```

---

### 3. Listar Sucursales del Usuario

```
POST /api/auth/sucursales
```

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Body:**
```json
{
    "user_id": 1
}
```

**Respuesta exitosa (201):**
```json
[
    {
        "nombre": "Sucursal Centro"
    },
    {
        "nombre": "Sucursal Norte"
    },
    {
        "nombre": "Sucursal Shopping"
    }
]
```

---

### 4. Productos por Sucursal

```
POST /api/auth/productosPorSucursal
```

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Body:**
```json
{
    "user_id": 1,
    "nombre_sucursal": "Sucursal Centro"
}
```

**Respuesta exitosa (201):**
```json
[
    {
        "codigo_barras": "7790001234567",
        "id": 1,
        "nombre": "Lentes de Sol Ray-Ban",
        "precio": 45000.00,
        "costo": 30000.00,
        "usuario": "admin",
        "fecha": "2026-01-05",
        "descripcion": "Lentes de sol polarizados",
        "descripcion_en": "Polarized sunglasses",
        "descripcion_pr": "Óculos de sol polarizados",
        "material": "Acetato",
        "precio_mayorista": 38000.00,
        "nombre_proveedor": "Juan",
        "apellido_proveedor": "Pérez",
        "imagenes": [
            "http://dominio.com/storage/productos/imagen1.jpg"
        ],
        "categoria": "Lentes de Sol",
        "cantidad": 25,
        "condicion": "new"
    }
]
```

---

## Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | Login exitoso |
| 201 | Datos obtenidos correctamente |
| 401 | No autenticado / Token inválido |
| 422 | Error de validación |

---

## Ejemplos de Uso

### cURL

```bash
# 1. Login
curl -X POST https://tu-dominio.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@ejemplo.com","password":"contraseña"}'

# 2. Listar productos
curl -X POST https://tu-dominio.com/api/auth/productos \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"

# 3. Listar sucursales
curl -X POST https://tu-dominio.com/api/auth/sucursales \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1}'

# 4. Productos por sucursal
curl -X POST https://tu-dominio.com/api/auth/productosPorSucursal \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "nombre_sucursal": "Sucursal Centro"}'
```

### JavaScript (Fetch)

```javascript
// Login
const login = await fetch('https://tu-dominio.com/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'usuario@ejemplo.com',
        password: 'contraseña'
    })
});
const { access_token } = await login.json();

// Listar productos
const productos = await fetch('https://tu-dominio.com/api/auth/productos', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${access_token}`,
        'Content-Type': 'application/json'
    }
});
const data = await productos.json();
console.log(data);
```

### Python

```python
import requests

BASE_URL = 'https://tu-dominio.com'

# Login
response = requests.post(f'{BASE_URL}/api/auth/login', json={
    'email': 'usuario@ejemplo.com',
    'password': 'contraseña'
})
token = response.json()['access_token']

headers = {
    'Authorization': f'Bearer {token}',
    'Content-Type': 'application/json'
}

# Listar productos
productos = requests.post(f'{BASE_URL}/api/auth/productos', headers=headers)
print(productos.json())

# Listar sucursales
sucursales = requests.post(f'{BASE_URL}/api/auth/sucursales',
    headers=headers,
    json={'user_id': 1}
)
print(sucursales.json())

# Productos por sucursal
por_sucursal = requests.post(f'{BASE_URL}/api/auth/productosPorSucursal',
    headers=headers,
    json={'user_id': 1, 'nombre_sucursal': 'Sucursal Centro'}
)
print(por_sucursal.json())
```

### PHP

```php
<?php
$baseUrl = 'https://tu-dominio.com';

// Login
$ch = curl_init("$baseUrl/api/auth/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'usuario@ejemplo.com',
    'password' => 'contraseña'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $response['access_token'];

// Listar productos
$ch = curl_init("$baseUrl/api/auth/productos");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$productos = json_decode(curl_exec($ch), true);
curl_close($ch);

print_r($productos);
```

---

## Notas

- El token expira en **24 horas**
- Todos los endpoints retornan **JSON**
- Las fechas están en formato **YYYY-MM-DD**
- Los precios están en **pesos argentinos**
