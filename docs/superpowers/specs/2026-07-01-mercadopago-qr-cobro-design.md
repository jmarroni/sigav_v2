# Diseño: Cobrar con QR de Mercado Pago desde la pantalla de ventas

- **Fecha:** 2026-07-01
- **Estado:** Aprobado (diseño) — pendiente plan de implementación
- **Autor:** desarrollo@bairesai.com (con Claude Code)

## Problema

SIGAV ya puede configurar la cuenta de Mercado Pago por sucursal y ver los cobros
sincronizados (spec `2026-06-30-mercadopago-movimientos-design.md`), pero el cobro
en sí ocurre por fuera: el cliente tipea el monto en su app frente al QR estático
del mostrador, con riesgo de error de tipeo, o el cajero usa un dispositivo Point.
No hay forma de generar un cobro por el **monto exacto de la venta en curso** desde
la pantalla de ventas.

### Objetivo de esta entrega

Un botón **"Cobrar con MP"** en `/ventas.php` que:
1. Genere un link de pago de Mercado Pago (Checkout Pro) por el total exacto de la
   venta en curso y lo muestre como **QR en pantalla**.
2. Consulte a Mercado Pago cada pocos segundos y muestre **"✓ Pago acreditado"**
   cuando el cliente paga, sin que el cajero mire el celular.
3. Registre el pago acreditado en `mercadopago_pagos` al detectarlo (aparece en
   "Movimientos MP" sin sincronización manual).

**Explícitamente fuera de alcance:** vincular el pago a la venta/factura (no existe
ID de venta al momento del cobro), facturar automáticamente al acreditarse,
webhooks de MP, y el QR de mostrador "in-store" (stores/POS API).

## Decisiones tomadas (brainstorming)

| Decisión | Resultado |
|---|---|
| Tipo de QR | **Link de pago (Checkout Pro)**: el cliente escanea con la cámara, paga en el checkout web de MP. No requiere configuración extra en MP ni la app instalada. Descartado el QR in-store (alta de stores/POS por API, mucho más complejo). |
| Confirmación de pago | **Polling desde el navegador**: `ventas.js` consulta el estado cada ~4 s mientras el QR está visible. Descartados webhooks (requieren URL pública; no funcionan en dev local). |
| Ubicación en el flujo | **Botón junto al total de "Venta en curso"**. La facturación no se toca: el cajero cobra, ve el ✓ y concreta/factura como siempre. |
| Sucursal | Siempre la de la sesión (cookie), sin selector. Cualquier usuario logueado puede cobrar por su propia sucursal (mismo criterio que sincronizar la propia en movimientos). |
| Librería QR | Se reutiliza `public/assets/js/qr/qrcode.js` (ya vendorizada, la usa etiquetas). **Cero dependencias nuevas.** |
| Puente legacy | Descartado: el Access Token está cifrado con `Crypt` de Laravel; no es legible desde PHP plano. Todo pasa por endpoints Laravel. |

## Arquitectura

### 1. Servicio — `MercadoPagoService` (extender, mismo archivo)

Dos métodos nuevos, mismo estilo que los existentes (cliente Guzzle inyectable,
errores nunca crudos al usuario, detalle a `Log::error`, catch `GuzzleException`):

- `crearPreferencia(int $sucursalId, float $monto): array`
  - Sin token configurado → `{ok: false, mensaje: 'No hay token cargado.'}` sin red.
  - `POST checkout/preferences` con:
    - `items`: un ítem, `title` = `"Venta {nombre sucursal}"` (o "Venta" si no se
      resuelve el nombre), `quantity` = 1, `unit_price` = monto (2 decimales),
      `currency_id` = `ARS`.
    - `external_reference` = `'QR-' . $sucursalId . '-' . uniqid()` (generada
      server-side; es la clave del polling posterior).
    - `date_of_expiration` = ahora + 30 minutos (ISO 8601 con offset) — un QR
      viejo no puede pagarse después.
  - Devuelve `{ok: true, ref, init_point}` (la URL `init_point` es lo que se
    renderiza como QR).
- `buscarPagoPorReferencia(int $sucursalId, string $ref): array`
  - `GET v1/payments/search?external_reference={ref}&sort=date_created&criteria=desc`.
  - Si hay un pago con `status = approved`: lo **upsertea en `mercadopago_pagos`**
    (misma lógica de mapeo que `sincronizarPagos`; extraer ese mapeo a un método
    privado compartido `guardarPago(int $sucursalId, array $pago)` para no
    duplicarlo) y devuelve `{ok: true, pagado: true, monto, mp_payment_id}`.
  - Si no hay pago aún: `{ok: true, pagado: false}`.
  - Error de API: `{ok: false, pagado: false, mensaje}` genérico (el JS sigue
    poleando; un error transitorio no corta el cobro).

### 2. Controller — `App\Http\Controllers\MercadoPagoQrController`

- Constructor: `middleware('auth')` (como los otros dos controllers MP).
- Sucursal SIEMPRE de la cookie (`Sucursales::getSucursal()`), sin parámetro de
  ruta ni selector — no hay caso de uso de cobrar por otra sucursal.
- `crear(Request)`: valida `monto` (`required|numeric|min:0.01|max:99999999`),
  llama a `crearPreferencia`, devuelve JSON `{ok, ref, init_point}` o
  `{ok: false, mensaje}`.
  - **Limitación aceptada:** el monto viene del cliente (el total de la venta en
    curso vive solo en el navegador hasta que se concreta; el server no puede
    recalcularlo). Riesgo bajo: herramienta interna de cajero, y el pago real
    siempre se verifica contra lo que MP informa.
- `estado(Request)`: valida `ref` (`required|string|max:100`), llama a
  `buscarPagoPorReferencia`, devuelve el JSON del servicio. Una `ref`
  desconocida devuelve `{ok: true, pagado: false}` (esto además permite usar el
  endpoint como handshake CSRF, ver §4).

**Rutas** (`routes/web.php`, junto a las MP existentes):

```php
// dentro del grupo throttle:20,1 existente:
Route::post('mercadopago/qr', 'MercadoPagoQrController@crear');
// FUERA del grupo 20,1 — el polling a ~4s son ~15 req/min y agotaría ese límite:
Route::get('mercadopago/qr/estado', 'MercadoPagoQrController@estado')->middleware('throttle:30,1');
```

### 3. Frontend — `ventas.php` + `ventas.js`

- `ventas.php`: botón `id="cobrar_mp"` ("Cobrar con MP") junto al badge
  `#total_ventas` en el header de "Venta en curso"; un panel oculto
  `id="panel_qr_mp"` (misma estética `sg-card`/`sg-` que el resto de la pantalla)
  con: contenedor del QR (`id="qr_mp"`), el monto grande, el estado
  (`id="estado_qr_mp"`, arranca "Esperando pago…"), y botón "Cancelar".
  `<script src="/assets/js/qr/qrcode.js">` se incluye en la página.
- `ventas.js`:
  - Click en "Cobrar con MP": si total ≤ 0, no hace nada (botón deshabilitado).
    Ejecuta el handshake CSRF (§4), luego `POST /mercadopago/qr` con el total
    actual. Con `{ok: true}`: renderiza `init_point` como QR
    (`new QRCode('qr_mp', {...})`), muestra el panel y arranca el polling.
    Con `{ok: false}`: muestra el `mensaje` (ej. "No hay token cargado.").
  - Polling: `GET /mercadopago/qr/estado?ref=...` cada 4 s. `pagado: true` →
    detiene el polling, estado pasa a "✓ Pago acreditado" (verde). Errores
    transitorios no detienen el polling.
  - "Cancelar" o iniciar un nuevo cobro: detiene el polling y oculta el panel.
    El polling también se corta solo a los 30 minutos (expiración del QR).

### 4. CSRF desde la página legacy

`ventas.php` no tiene el token CSRF de Laravel embebido (la sirve PHP plano). Se
usa el mecanismo estándar de Laravel, ya probado en este proyecto (verificación
manual del feature de movimientos):

1. `ventas.js` hace primero `GET /mercadopago/qr/estado?ref=handshake` — cualquier
   respuesta de Laravel bajo el grupo `web` setea las cookies de sesión y
   `XSRF-TOKEN` (y `LegacyCookieAuth` puentea la cookie legacy a la sesión).
2. Lee la cookie `XSRF-TOKEN` (no es httpOnly) y manda su valor en el header
   `X-XSRF-TOKEN` del POST. Laravel 7 lo descifra y valida solo.

CSRF queda **activo, sin excepciones** en `VerifyCsrfToken::$except`.

### 5. Seguridad

- Access Token: nunca sale del server; el browser solo ve `init_point` (URL
  pública de checkout) y la `ref`.
- Endpoints bajo `auth` + operación limitada a la sucursal de la sesión.
- CSRF activo (§4). Throttle en ambos endpoints (§2).
- Errores de MP nunca crudos al usuario; `Log::error` server-side.
- `external_reference` se genera server-side (el cliente no puede elegirla) y el
  monto mostrado como pagado sale de la respuesta de MP, no del request.
- Expiración de la preferencia a 30 min.

## Testing

Mismo criterio que el feature de movimientos:

1. **`MercadoPagoServiceTest`** (extender): `crearPreferencia` (ok con
   init_point/ref, sin token sin red, error de API genérico) y
   `buscarPagoPorReferencia` (aprobado → upsertea en `mercadopago_pagos` y
   devuelve pagado, sin resultados → pagado false, error de API → ok false sin
   filtrar detalle). Todo con `MockHandler`, sin red.
2. **Controllers sin test automatizado** (dependen de `Usuario`/`Sucursales`, sin
   migración — precedente documentado). Verificación manual con cookies forjadas:
   crear QR (JSON con init_point), estado con ref inexistente (pagado false),
   POST sin header CSRF (419).
3. **Manual E2E** (usuario, con token real): generar QR desde una venta, pagarlo
   con un celular real, ver el ✓ en pantalla y el movimiento en Movimientos MP.

## Archivos afectados

**Nuevos:**
- `app/Http/Controllers/MercadoPagoQrController.php`

**Modificados:**
- `app/Services/MercadoPago/MercadoPagoService.php` (dos métodos + mapeo extraído)
- `routes/web.php` (dos rutas)
- `public/ventas.php` (botón + panel QR + include de qrcode.js)
- `public/assets/js/pages/ventas.js` (handler, render QR, polling, handshake CSRF)
- `tests/Unit/MercadoPagoServiceTest.php` (casos nuevos)
