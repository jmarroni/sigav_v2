# Diseño: Configuración de cuenta Mercado Pago + vista de movimientos (cobros)

- **Fecha:** 2026-06-30
- **Estado:** Aprobado (diseño) — pendiente plan de implementación
- **Autor:** desarrollo@bairesai.com (con Claude Code)

## Problema

SIGAV v2 no tiene ninguna integración con Mercado Pago hoy (verificado: no hay
referencias a "mercadopago" en `app/`, `public/`, `routes/`, `config/`,
`database/migrations/` ni `composer.json`). Cada sucursal cobra con su propia cuenta
de Mercado Pago, pero esos cobros viven únicamente del lado de MP — no hay forma de
verlos ni de relacionarlos con la facturación del sistema.

### Objetivo de esta entrega

1. Una pantalla de configuración por sucursal, en el mismo espíritu que la de AFIP,
   donde cargar el Access Token de Mercado Pago de esa sucursal y **probar la
   conexión**.
2. Una pantalla de **movimientos** que muestre los cobros (pagos aprobados/pendientes/
   rechazados) recibidos por Mercado Pago, sincronizados a una tabla local mediante un
   botón manual.
3. Dejar el terreno preparado (un campo de estado) para que la **próxima** etapa
   pueda construir la acción de "facturar este movimiento" sin otra migración.

**Explícitamente fuera de alcance de esta entrega:** generar la factura a partir de un
movimiento. Eso es la siguiente etapa.

## Decisiones tomadas (brainstorming)

| Decisión | Resultado |
|---|---|
| Alcance de la cuenta MP | **Una cuenta por sucursal** (no una global) |
| Qué se sincroniza | **Cobros recibidos** (Payments API), no todo el "account money" |
| Credenciales | Solo **producción** por ahora (no se replica el esquema homo/prod de AFIP) |
| Sincronización | **Botón manual** "Sincronizar ahora" (sin cron/jobs en esta entrega) |
| Permiso para configurar credenciales | **rol_id ≥ 2**, igual que AFIP (cualquier admin, no restringido a su propia sucursal) |
| Selector de sucursal en movimientos | Sí, pero **solo visible para rol_id ≥ 2**; el resto ve siempre su sucursal (cookie `sucursal`) |

## Arquitectura

### 1. Conexión con la API de Mercado Pago: Guzzle directo, sin SDK nuevo

`guzzlehttp/guzzle` (`^7.3`) ya es una dependencia del proyecto. Para los dos únicos
endpoints que necesitamos (`GET /users/me` para probar conexión, `GET
/v1/payments/search` para sincronizar) **no se agrega el SDK oficial
`mercadopago/dx-php`**: son dos llamadas REST simples y agregar un SDK nuevo suma
riesgo de compatibilidad innecesario en un stack fijado a PHP `^7.2.5`, sin aportar
nada que Guzzle no resuelva ya. Si en el futuro se necesita más superficie de la API
(checkout, webhooks, etc.) se puede reconsiderar.

### 2. Base de datos

**Migración `mercadopago_config`** — una fila por sucursal:

| campo | tipo | nota |
|---|---|---|
| `id` | bigint PK | |
| `sucursal_id` | unsignedBigInteger, único | FK lógica a `sucursales.id` |
| `access_token` | text nullable | cifrado en reposo (ver Seguridad) |
| `public_key` | string nullable | no es secreto |
| `activo` | boolean default true | permite pausar la integración sin borrar el token |
| `timestamps` | | |

**Migración `mercadopago_pagos`** — cache local de cobros sincronizados:

| campo | tipo | nota |
|---|---|---|
| `id` | bigint PK | |
| `sucursal_id` | unsignedBigInteger | |
| `mp_payment_id` | string, único | id de pago de MP; evita duplicar en re-syncs |
| `fecha` | datetime | `date_approved` si existe, si no `date_created` |
| `monto` | decimal(12,2) | `transaction_amount` |
| `monto_neto` | decimal(12,2) nullable | `transaction_amount` menos comisión MP, si MP la informa |
| `estado` | string | `status` de MP (`approved`, `rejected`, `pending`, ...) |
| `medio_pago` | string nullable | `payment_type_id` / `payment_method_id` |
| `comprador` | string nullable | `payer.email`, si MP lo informa |
| `payload_raw` | json | respuesta cruda del pago, por si una futura columna hace falta |
| `estado_facturacion` | string default `'pendiente'` | gancho para la próxima etapa; esta entrega no la modifica desde ningún flujo |
| `timestamps` | | |

Índice único compuesto `(sucursal_id, mp_payment_id)`.

### 3. Componentes Laravel

**Modelos**

- `App\Models\MercadoPagoConfig` — tabla `mercadopago_config`. `$fillable =
  ['sucursal_id', 'public_key', 'activo']` (sin `access_token`: se setea solo vía
  mutator dedicado, nunca por asignación masiva). Accessor/mutator para
  `access_token`:
  ```php
  public function setAccessTokenAttribute($value): void
  {
      $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
  }

  public function getAccessTokenAttribute($value): ?string
  {
      return $value ? Crypt::decryptString($value) : null;
  }
  ```
  (Laravel 7.30, instalado en este repo, **no tiene** el cast nativo `'encrypted'`
  — se confirmó revisando `HasAttributes::castAttribute` en el vendor — por eso el
  accessor/mutator manual con `Crypt`.)
  Método `tokenEnmascarado(): ?string` para la vista (ej. `"····tu43"`, últimos 4
  caracteres del token plano, nunca lo completo).
- `App\Models\MercadoPagoPago` — tabla `mercadopago_pagos`. `$fillable` con todos los
  campos salvo `id`/`estado_facturacion` (ese se maneja aparte, en la próxima etapa).

**Service — `App\Services\MercadoPago\MercadoPagoService`**

- Constructor recibe (o crea) un `GuzzleHttp\Client` con `base_uri =
  https://api.mercadopago.com/`.
- `probarConexion(Sucursales $sucursal): array` — busca `MercadoPagoConfig` de la
  sucursal; si no hay token, devuelve `{ok: false, mensaje: 'No hay token cargado.'}`
  sin pegarle a la red. Si hay, `GET users/me` con `Authorization: Bearer
  {token}`. Devuelve `{ok, mensaje}`; nunca expone el cuerpo crudo de un error 401/4xx
  de MP al usuario (mensaje genérico "Token inválido o sin permisos"), loguea el
  detalle con `Log::error` server-side.
- `sincronizarPagos(Sucursales $sucursal, Carbon $desde, Carbon $hasta): array{nuevos:
  int, total: int}` — pagina `GET v1/payments/search` (`range=date_created`,
  `begin_date`, `end_date`, `sort=date_created`, `criteria=desc`, `limit=50`,
  `offset=...`) hasta agotar resultados **o** llegar a un tope de 10 páginas (500
  pagos) por corrida — si se llega al tope, corta y lo informa en el mensaje de
  vuelta (no es un límite silencioso) para que el usuario achique el rango. Por cada
  pago hace `updateOrCreate(['sucursal_id' => ..., 'mp_payment_id' => ...], [...])`.
  Igual que `probarConexion`, no filtra errores crudos de MP; loguea y devuelve un
  mensaje genérico.

**Controller — `App\Http\Controllers\MercadoPagoConfigController`**

- `index()` — gate `rol_id ≥ 2`. Trae todas las `Sucursales`, con su
  `MercadoPagoConfig` (o `firstOrCreate` por sucursal). Vista acordeón, una sección
  por sucursal.
- `guardar(Request, Sucursales $sucursal)` — gate, valida `access_token` (`required`
  la primera vez; en sucesivas, `nullable` para permitir guardar solo `public_key`/
  `activo` sin re-pegar el token), `public_key` nullable, `activo` boolean. Si
  `access_token` viene vacío y ya existe uno guardado, no lo pisa. Como
  `access_token` queda fuera de `$fillable` a propósito (ver modelo), el controller
  lo asigna explícito (`$config->access_token = $valor`) en vez de usar `fill()`/
  asignación masiva.
- `probar(Sucursales $sucursal, MercadoPagoService $mp)` — gate, llama al service,
  flashea el resultado.

**Controller — `App\Http\Controllers\MercadoPagoMovimientosController`**

- `index(Request)` — determina la sucursal objetivo: si el usuario tiene rol_id ≥ 2 y
  llega `?sucursal_id=`, usa esa (validando que exista); si no, usa
  `Sucursales::getSucursal()` (la de la cookie, como el resto del sistema). Si es
  admin, también pasa a la vista la lista de sucursales para el selector. Filtros de
  fecha por query string (`desde`/`hasta`, default: últimos 30 días). Pagina con
  `MercadoPagoPago::where(...)->orderBy('fecha', 'desc')->paginate(25)` — **paginación
  estándar de Laravel (`links()` en Blade), no la grilla AJAX server-side que usa
  `/carga`**: el volumen de pagos por sucursal no lo justifica todavía y mantiene esta
  entrega más chica; si el volumen crece, migrar al patrón AJAX es un cambio
  localizado a este controller/vista.
- `sincronizar(Request, Sucursales $sucursal, MercadoPagoService $mp)` — permitido si
  el usuario es rol_id ≥ 2, o si `$sucursal` es la propia (cookie). Valida rango de
  fechas (`desde`/`hasta`, default últimos 30 días, **máximo 90 días** por corrida —
  evita syncs sin límite). Llama al service, flashea cuántos pagos nuevos trajo.

**Gate de rol compartido**

`AfipConfigController::autorizar()` ya implementa el chequeo `rol_id ≥ 2` vía cookie
`kiosco`. Esta entrega lo necesita en dos controllers más, así que se extrae a un
trait `App\Http\Controllers\Concerns\AutorizaRolAdmin` (método `autorizar(int $rolMinimo
= 2)`) y `AfipConfigController` pasa a usarlo también, en lugar de triplicar el mismo
bloque de 8 líneas.

**Rutas — `routes/web.php`**

```php
// Mercado Pago - configuración de cuenta por sucursal y vista de movimientos
Route::middleware('throttle:20,1')->group(function () {
    Route::get('mercadopago/configuracion', 'MercadoPagoConfigController@index');
    Route::post('mercadopago/configuracion/{sucursal}', 'MercadoPagoConfigController@guardar');
    Route::post('mercadopago/probar/{sucursal}', 'MercadoPagoConfigController@probar');
    Route::post('mercadopago/movimientos/sincronizar/{sucursal}', 'MercadoPagoMovimientosController@sincronizar');
});
Route::get('mercadopago/movimientos', 'MercadoPagoMovimientosController@index');
```

`{sucursal}` usa route model binding contra `App\Models\Sucursales`. Acciones de
estado siempre `POST`, CSRF de Laravel activo (igual que AFIP).

### 4. Vistas

- `resources/views/mercadopago/configuracion.blade.php` — mismo layout admin que
  `afip/configuracion.blade.php`. Acordeón con una sección por sucursal: estado
  ("token configurado ····tu43" / "sin configurar"), campo Access Token (input
  password, se deja vacío si ya hay uno guardado — solo se pisa si se escribe algo
  nuevo), Public Key, switch `activo`, botón "Guardar" y botón "Probar conexión" con
  el resultado de la última prueba.
- `resources/views/mercadopago/movimientos.blade.php` — selector de sucursal (solo
  si rol_id ≥ 2), filtro de fechas desde/hasta, botón "Sincronizar ahora" (con
  feedback de cuántos pagos nuevos trajo), tabla paginada (fecha, monto, monto neto,
  estado con badge de color, medio de pago, comprador). Si la sucursal no tiene
  `MercadoPagoConfig` con token cargado, estado vacío con link directo a
  `/mercadopago/configuracion`.

### 5. Navegación

- Panel "Configuraciones" de `public/header.php` (el mismo donde está el link a
  AFIP, líneas ~157 en adelante): se agrega una entrada "Mercado Pago" →
  `/mercadopago/configuracion`, mismo formato que la de AFIP.
- Entrada de menú principal para "Movimientos MP" (`/mercadopago/movimientos`): el
  menú principal operativo no se localizó en esta exploración (el agente solo
  encontró el panel de "Configuraciones"); ubicarlo y confirmar el archivo/línea
  exacta es parte del trabajo de implementación, agregándolo junto a las demás
  secciones operativas del menú de `public/header.php`.

### 6. Seguridad

- `access_token` cifrado en reposo (`Crypt::encryptString`/`decryptString` vía
  accessor/mutator del modelo — ver sección 3).
- El token nunca se loguea ni se renderiza completo en HTML; solo enmascarado.
- Llamadas a la API de MP siempre por HTTPS (`https://api.mercadopago.com`).
- CSRF de Laravel en todas las rutas `POST`.
- `throttle:20,1` en las rutas de escritura/prueba/sync (mismo criterio que AFIP),
  para no machacar la API de MP ni habilitar fuerza bruta sobre el endpoint de
  prueba.
- Errores de la API de MP (4xx/5xx) nunca se filtran crudos al usuario; se loguean
  server-side (`Log::error`) y se devuelve un mensaje genérico.
- Sync acotado: rango máximo 90 días, tope de 500 pagos por corrida (10 páginas),
  informado al usuario si se corta — no es un límite silencioso.

## Testing

Ya existe infraestructura de tests en el repo (`tests/Unit`, `tests/Feature`,
`TestCase` base — agregada en una sesión reciente; la nota de `CLAUDE.md` sobre "no
hay `tests/`" está desactualizada). Se sigue el mismo patrón que
`tests/Unit/AfipServiceTest.php` y `tests/Unit/AfipConfigTest.php`:

1. **`MercadoPagoConfigTest`** (Unit, `RefreshDatabase`) — el accessor/mutator de
   `access_token` cifra al guardar y devuelve el valor plano al leer; `activo`
   default true.
2. **`MercadoPagoServiceTest`** (Unit) — mockear `GuzzleHttp\Client` (Mockery, mismo
   estilo que el resto de `tests/Unit`) para `probarConexion` (caso ok, caso 401,
   caso sin token configurado) y `sincronizarPagos` (upsert sin duplicar
   `mp_payment_id`, corte al tope de páginas).
3. **Manual** — forjar cookies legacy (técnica documentada en memoria
   `carga-grid-serverside.md`, DB real `laravel` en el contenedor `sigav_db`),
   cargar un Access Token real de una cuenta de Mercado Pago, probar conexión,
   sincronizar un rango con pagos reales y verificar que aparecen en la grilla.

## Fuera de alcance (siguiente etapa)

- Acción de "facturar" un movimiento (generar la factura asociada y actualizar
  `estado_facturacion`).
- Webhooks de Mercado Pago (notificaciones en tiempo real) — esta entrega es 100%
  pull manual.
- Sincronización automática en background (cron/queue).
- Modo prueba/sandbox de Mercado Pago.

## Archivos afectados (resumen)

**Nuevos:**
- `database/migrations/XXXX_create_mercadopago_config_table.php`
- `database/migrations/XXXX_create_mercadopago_pagos_table.php`
- `app/Models/MercadoPagoConfig.php`
- `app/Models/MercadoPagoPago.php`
- `app/Services/MercadoPago/MercadoPagoService.php`
- `app/Http/Controllers/Concerns/AutorizaRolAdmin.php`
- `app/Http/Controllers/MercadoPagoConfigController.php`
- `app/Http/Controllers/MercadoPagoMovimientosController.php`
- `resources/views/mercadopago/configuracion.blade.php`
- `resources/views/mercadopago/movimientos.blade.php`
- `tests/Unit/MercadoPagoConfigTest.php`
- `tests/Unit/MercadoPagoServiceTest.php`

**Modificados:**
- `routes/web.php`
- `app/Http/Controllers/AfipConfigController.php` (usa el trait nuevo en vez de su
  `autorizar()` propio)
- `public/header.php` (entrada en panel "Configuraciones" + entrada de menú
  principal, ubicación exacta a confirmar en implementación)
