# Cobro con QR de Mercado Pago desde ventas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A "Cobrar con MP" button on `/ventas.php` that generates a Mercado Pago payment link (Checkout Pro) for the exact sale total, renders it as an on-screen QR, and polls until the payment is approved, recording it in `mercadopago_pagos`.

**Architecture:** Extends the existing Mercado Pago feature (spec `2026-07-01-mercadopago-qr-cobro-design.md`): two new methods on `MercadoPagoService` (Guzzle against `checkout/preferences` and `v1/payments/search`), one new thin controller with two JSON endpoints, and frontend wiring in the legacy `ventas.php`/`ventas.js` using the already-vendored `public/assets/js/qr/qrcode.js` QR library. CSRF stays fully enabled via Laravel's XSRF-TOKEN cookie/header mechanism.

**Tech Stack:** Laravel 7.30 (PHP ^7.2.5, Docker runtime PHP 7.4), GuzzleHttp 7.3 (already installed), jQuery (legacy pages), davidshimjs qrcode.js (already vendored), PHPUnit 8.5 + sqlite in-memory.

## Global Constraints

- PHP `^7.2.5` / Laravel `^7.0` syntax only (typed class properties OK — runtime is PHP 7.4 and precedent exists; no PHP 8+ features).
- **Zero new dependencies** (composer or JS). The QR library is `public/assets/js/qr/qrcode.js`, already in the repo (API: `new QRCode(domElement, {text, width, height, correctLevel})`, global `QRCode.CorrectLevel`).
- CSRF stays active — **no additions to `VerifyCsrfToken::$except`**. The legacy page authenticates the POST via the `XSRF-TOKEN` cookie → `X-XSRF-TOKEN` header mechanism (Laravel 7 decrypts the header value itself; proven in this repo during the movimientos feature's manual verification).
- The Access Token never reaches the browser; the browser only sees `init_point` (public checkout URL) and the `ref`. `external_reference` is generated **server-side** (`'QR-'.$sucursalId.'-'.uniqid()`).
- Sucursal is ALWAYS the session's own (`Sucursales::getSucursal()`, cookie-derived) — no route param, no selector, no role gate beyond `auth` (any logged-in user may charge for their own branch; same criterion as syncing one's own branch).
- The polling endpoint must NOT live inside the existing `throttle:20,1` group — polling every 4 s is ~15 req/min and would exhaust it. It gets its own `throttle:30,1`.
- Accepted limitation (documented in the spec): the `monto` comes from the client (the in-progress sale's total exists only in the browser). Validated `required|numeric|min:0.01|max:99999999`; the amount shown as paid always comes from MP's response, never echoed from the request.
- Errors from the MP API are never leaked raw to the caller: generic message, real detail to `Log::error`, catch `\GuzzleHttp\Exception\GuzzleException` (established pattern in `MercadoPagoService`).
- `mercadopago_pagos.estado_facturacion` remains untouched (not in the upsert payload — it's excluded from `$fillable` and reserved for the future invoicing stage).
- **Test command:** the host PHP CLI fatals on Laravel 7 — ALWAYS run PHPUnit via Docker: `docker compose exec -T app vendor/bin/phpunit [path]`. Containers: `sigav_app` / `sigav_db` (`docker compose up -d` if down).
- **Manual verification:** forge legacy cookies. SEMILLA `'$%Reset20122017AnnaLuca#^'`; `rol`/`sucursal` = `sha1(SEMILLA . <id> . SEMILLA)`; `kiosco` = username. Live-query the user first (`docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT usuario, rol_id, sucursal_id FROM usuarios WHERE rol_id >= 2 LIMIT 5"`) — hardcoded ids have gone stale twice in this project. Gotcha: `php -r "echo sha1('$SEMILLA'.$X.'$SEMILLA');"` fails to parse on the host CLI (dot-concat lexing); put spaces around the `.` operators.
- The dev DB's sucursal has a **real** MP Access Token configured. Read-only calls (`payments/search`) and creating preferences are harmless (no money moves unless someone pays the QR) — but never pay a generated QR during verification.
- `App\Services\MercadoPago\MercadoPagoService` resolves through a contextual binding in `AppServiceProvider` (gives it a Guzzle client with `base_uri`/timeouts). The new methods reuse `$this->client` — do not construct clients inside methods, do not touch the binding.

---

## File Structure

**New files:**
- `app/Http/Controllers/MercadoPagoQrController.php`

**Modified files:**
- `app/Services/MercadoPago/MercadoPagoService.php` (private `guardarPago()` + `tituloVenta()`, public `crearPreferencia()` + `buscarPagoPorReferencia()`)
- `tests/Unit/MercadoPagoServiceTest.php` (new test methods)
- `routes/web.php` (two routes)
- `public/ventas.php` (button, hidden QR panel, script include)
- `public/assets/js/pages/ventas.js` (handlers, QR render, polling, CSRF handshake)

---

### Task 1: Service — extract `guardarPago()` + add `crearPreferencia()`

**Files:**
- Modify: `app/Services/MercadoPago/MercadoPagoService.php`
- Test: `tests/Unit/MercadoPagoServiceTest.php`

**Interfaces:**
- Consumes: existing `MercadoPagoConfig` (access_token accessor), existing `MercadoPagoPago`, existing test helpers `servicioConRespuestas(array $responses)` and `configConToken(int $sucursalId, string $token)` in `MercadoPagoServiceTest`.
- Produces: `public function crearPreferencia(int $sucursalId, float $monto): array` returning `['ok'=>true,'ref'=>string,'init_point'=>string]` or `['ok'=>false,'mensaje'=>string]`; `private function guardarPago(int $sucursalId, array $pago): MercadoPagoPago` (used by Task 2). `ref` format: `QR-{sucursalId}-{uniqid}`.

- [ ] **Step 1: Write the failing tests**

Append inside the `MercadoPagoServiceTest` class in `tests/Unit/MercadoPagoServiceTest.php`, after the last existing test, before the closing `}`. All imports used below (`Response`, `ClientException`, `Psr7Request`, `MercadoPagoConfig`) already exist at the top of the file.

```php
    /** @test */
    public function crear_preferencia_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->crearPreferencia(1, 1500.50);

        $this->assertFalse($r['ok']);
        $this->assertSame('No hay token cargado.', $r['mensaje']);
    }

    /** @test */
    public function crear_preferencia_devuelve_init_point_y_referencia()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(201, [], json_encode([
                'id' => '123-abc',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123-abc',
            ])),
        ]);

        $r = $servicio->crearPreferencia(1, 1500.50);

        $this->assertTrue($r['ok']);
        $this->assertSame('https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=123-abc', $r['init_point']);
        $this->assertStringStartsWith('QR-1-', $r['ref']);
    }

    /** @test */
    public function crear_preferencia_sin_init_point_en_la_respuesta_devuelve_error_generico()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(201, [], json_encode(['id' => '123-abc'])),
        ]);

        $r = $servicio->crearPreferencia(1, 100);

        $this->assertFalse($r['ok']);
        $this->assertSame('No se pudo generar el QR. Probá de nuevo.', $r['mensaje']);
    }

    /** @test */
    public function crear_preferencia_error_de_api_no_filtra_detalle()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Bad Request',
                new Psr7Request('POST', 'checkout/preferences'),
                new Response(400, [], json_encode(['message' => 'detalle interno secreto']))
            ),
        ]);

        $r = $servicio->crearPreferencia(1, 100);

        $this->assertFalse($r['ok']);
        $this->assertStringNotContainsString('secreto', $r['mensaje']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -T app vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: 4 new tests FAIL with `Call to undefined method App\Services\MercadoPago\MercadoPagoService::crearPreferencia()`; the 10 pre-existing tests still pass.

- [ ] **Step 3: Implement — extract `guardarPago()` and add `crearPreferencia()`**

In `app/Services/MercadoPago/MercadoPagoService.php`:

3a. Add one import (the file already imports `MercadoPagoConfig`, `MercadoPagoPago`, `Carbon`, `Client`, `GuzzleException`, `Log`):

```php
use App\Models\Sucursales;
```

3b. Inside `sincronizarPagos()`, replace the inline upsert block:

```php
                foreach ($resultados as $pago) {
                    $registro = MercadoPagoPago::updateOrCreate(
                        ['sucursal_id' => $sucursalId, 'mp_payment_id' => (string) $pago['id']],
                        [
                            'fecha' => $pago['date_approved'] ?? $pago['date_created'],
                            'monto' => $pago['transaction_amount'] ?? 0,
                            'monto_neto' => $pago['transaction_details']['net_received_amount'] ?? null,
                            'estado' => $pago['status'] ?? 'unknown',
                            'medio_pago' => $pago['payment_type_id'] ?? null,
                            'comprador' => $pago['payer']['email'] ?? null,
                            'payload_raw' => $pago,
                        ]
                    );
                    if ($registro->wasRecentlyCreated) {
                        $nuevos++;
                    }
                }
```

with:

```php
                foreach ($resultados as $pago) {
                    $registro = $this->guardarPago($sucursalId, $pago);
                    if ($registro->wasRecentlyCreated) {
                        $nuevos++;
                    }
                }
```

3c. Add at the end of the class (after `sincronizarPagos`):

```php
    /** Genera un link de pago (Checkout Pro) por el monto exacto y devuelve la URL para el QR. */
    public function crearPreferencia(int $sucursalId, float $monto): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'mensaje' => 'No hay token cargado.'];
        }

        $ref = 'QR-'.$sucursalId.'-'.uniqid();

        try {
            $res = $this->client->request('POST', 'checkout/preferences', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                'json' => [
                    'items' => [[
                        'title' => $this->tituloVenta($sucursalId),
                        'quantity' => 1,
                        'unit_price' => round($monto, 2),
                        'currency_id' => 'ARS',
                    ]],
                    'external_reference' => $ref,
                    'date_of_expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d\TH:i:s.000P'),
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);

            if (empty($data['init_point'])) {
                Log::error('MercadoPago crearPreferencia sin init_point', ['sucursal_id' => $sucursalId]);

                return ['ok' => false, 'mensaje' => 'No se pudo generar el QR. Probá de nuevo.'];
            }

            return ['ok' => true, 'ref' => $ref, 'init_point' => $data['init_point']];
        } catch (GuzzleException $e) {
            Log::error('MercadoPago crearPreferencia falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'No se pudo generar el QR. Probá de nuevo.'];
        }
    }

    /** Upsert de un pago de MP en la cache local (clave compuesta sucursal + mp_payment_id). */
    private function guardarPago(int $sucursalId, array $pago): MercadoPagoPago
    {
        return MercadoPagoPago::updateOrCreate(
            ['sucursal_id' => $sucursalId, 'mp_payment_id' => (string) $pago['id']],
            [
                'fecha' => $pago['date_approved'] ?? $pago['date_created'],
                'monto' => $pago['transaction_amount'] ?? 0,
                'monto_neto' => $pago['transaction_details']['net_received_amount'] ?? null,
                'estado' => $pago['status'] ?? 'unknown',
                'medio_pago' => $pago['payment_type_id'] ?? null,
                'comprador' => $pago['payer']['email'] ?? null,
                'payload_raw' => $pago,
            ]
        );
    }

    /** Título del ítem del checkout. `sucursales` es tabla legacy sin migración: en tests no existe -> fallback. */
    private function tituloVenta(int $sucursalId): string
    {
        try {
            $nombre = Sucursales::where('id', $sucursalId)->value('nombre');

            return $nombre ? "Venta {$nombre}" : 'Venta';
        } catch (\Throwable $e) {
            return 'Venta';
        }
    }
```

- [ ] **Step 4: Run tests to verify they pass (refactor included)**

Run: `docker compose exec -T app vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: OK, 14 tests. The pre-existing `sincronizarPagos` tests double as the regression net for the `guardarPago` extraction — they must stay green untouched.

Then full suite: `docker compose exec -T app vendor/bin/phpunit`
Expected: OK (59 tests) — 55 previous + 4 new.

- [ ] **Step 5: Commit**

```bash
git add app/Services/MercadoPago/MercadoPagoService.php tests/Unit/MercadoPagoServiceTest.php
git commit -m "feat(mercadopago): crearPreferencia genera link de pago Checkout Pro con expiración de 30 min"
```

---

### Task 2: Service — `buscarPagoPorReferencia()`

**Files:**
- Modify: `app/Services/MercadoPago/MercadoPagoService.php`
- Test: `tests/Unit/MercadoPagoServiceTest.php`

**Interfaces:**
- Consumes: `guardarPago()` from Task 1 (exact signature: `private function guardarPago(int $sucursalId, array $pago): MercadoPagoPago`).
- Produces: `public function buscarPagoPorReferencia(int $sucursalId, string $ref): array` returning `['ok'=>true,'pagado'=>true,'monto'=>float,'mp_payment_id'=>string]` | `['ok'=>true,'pagado'=>false]` | `['ok'=>false,'pagado'=>false,'mensaje'=>string]`. Used by Task 3's `estado` endpoint.

- [ ] **Step 1: Write the failing tests**

Append inside the `MercadoPagoServiceTest` class, after Task 1's tests:

```php
    /** @test */
    public function buscar_pago_por_referencia_sin_token_no_pega_a_la_red()
    {
        MercadoPagoConfig::create(['sucursal_id' => 1, 'activo' => true]);
        $servicio = $this->servicioConRespuestas([]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertFalse($r['ok']);
        $this->assertFalse($r['pagado']);
    }

    /** @test */
    public function buscar_pago_aprobado_lo_guarda_y_devuelve_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [[
                'id' => 999888,
                'status' => 'approved',
                'date_approved' => '2026-07-01T12:00:00.000-03:00',
                'transaction_amount' => 1500.50,
                'payment_type_id' => 'account_money',
                'payer' => ['email' => 'cliente@test.com'],
            ]]])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertTrue($r['ok']);
        $this->assertTrue($r['pagado']);
        $this->assertSame(1500.50, $r['monto']);
        $this->assertSame('999888', $r['mp_payment_id']);
        $this->assertSame(1, \App\Models\MercadoPagoPago::count());
        $this->assertSame('approved', \App\Models\MercadoPagoPago::first()->estado);
    }

    /** @test */
    public function buscar_pago_pendiente_no_lo_guarda_y_devuelve_no_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => [[
                'id' => 999888,
                'status' => 'pending',
                'date_created' => '2026-07-01T12:00:00.000-03:00',
                'transaction_amount' => 1500.50,
            ]]])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['pagado']);
        $this->assertSame(0, \App\Models\MercadoPagoPago::count());
    }

    /** @test */
    public function buscar_pago_sin_resultados_devuelve_no_pagado()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new Response(200, [], json_encode(['results' => []])),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-inexistente');

        $this->assertTrue($r['ok']);
        $this->assertFalse($r['pagado']);
    }

    /** @test */
    public function buscar_pago_error_de_api_no_filtra_detalle()
    {
        $this->configConToken(1, 'TEST-TOKEN');
        $servicio = $this->servicioConRespuestas([
            new ClientException(
                'Unauthorized',
                new Psr7Request('GET', 'v1/payments/search'),
                new Response(401, [], json_encode(['message' => 'detalle interno secreto']))
            ),
        ]);

        $r = $servicio->buscarPagoPorReferencia(1, 'QR-1-abc');

        $this->assertFalse($r['ok']);
        $this->assertFalse($r['pagado']);
        $this->assertStringNotContainsString('secreto', $r['mensaje']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec -T app vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: 5 new tests FAIL with `Call to undefined method ...::buscarPagoPorReferencia()`; the other 14 pass.

- [ ] **Step 3: Implement `buscarPagoPorReferencia()`**

Add to `MercadoPagoService`, after `crearPreferencia()` (before the private methods):

```php
    /** Consulta si ya hay un pago aprobado con esa referencia; si lo hay, lo cachea localmente. */
    public function buscarPagoPorReferencia(int $sucursalId, string $ref): array
    {
        $config = MercadoPagoConfig::where('sucursal_id', $sucursalId)->first();

        if (! $config || ! $config->access_token) {
            return ['ok' => false, 'pagado' => false, 'mensaje' => 'No hay token cargado.'];
        }

        try {
            $res = $this->client->request('GET', 'v1/payments/search', [
                'headers' => ['Authorization' => 'Bearer '.$config->access_token],
                'query' => [
                    'external_reference' => $ref,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);

            foreach ($data['results'] ?? [] as $pago) {
                if (($pago['status'] ?? '') === 'approved') {
                    $registro = $this->guardarPago($sucursalId, $pago);

                    return [
                        'ok' => true,
                        'pagado' => true,
                        'monto' => (float) $registro->monto,
                        'mp_payment_id' => $registro->mp_payment_id,
                    ];
                }
            }

            return ['ok' => true, 'pagado' => false];
        } catch (GuzzleException $e) {
            Log::error('MercadoPago buscarPagoPorReferencia falló', ['sucursal_id' => $sucursalId, 'error' => $e->getMessage()]);

            return ['ok' => false, 'pagado' => false, 'mensaje' => 'No se pudo consultar el estado del pago.'];
        }
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec -T app vendor/bin/phpunit tests/Unit/MercadoPagoServiceTest.php`
Expected: OK, 19 tests.

Full suite: `docker compose exec -T app vendor/bin/phpunit` → OK (64 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/MercadoPago/MercadoPagoService.php tests/Unit/MercadoPagoServiceTest.php
git commit -m "feat(mercadopago): buscarPagoPorReferencia detecta el pago aprobado y lo cachea"
```

---

### Task 3: Routes + `MercadoPagoQrController`

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/MercadoPagoQrController.php`

**Interfaces:**
- Consumes: `MercadoPagoService::crearPreferencia` / `buscarPagoPorReferencia` (Tasks 1-2), `Sucursales::getSucursal()` (existing, cookie-derived), `auth` middleware.
- Produces: `POST /mercadopago/qr` (body `monto`) → JSON `{ok, ref, init_point}` | `{ok:false, mensaje}`; `GET /mercadopago/qr/estado?ref=...` → JSON `{ok, pagado, ...}`. Consumed by Task 4's JS.

No automated test (controller depends on `Usuario`/`Sucursales` DB rows that don't exist in the sqlite test DB — documented precedent for all three MP controllers). Manual verification in Step 3.

- [ ] **Step 1: Add the routes**

In `routes/web.php`, the current MP block is:

```php
Route::middleware('throttle:20,1')->group(function () {
    Route::get('mercadopago/configuracion', 'MercadoPagoConfigController@index');
    Route::post('mercadopago/configuracion/{sucursal_id}', 'MercadoPagoConfigController@guardar')->where('sucursal_id', '[0-9]+');
    Route::post('mercadopago/probar/{sucursal_id}', 'MercadoPagoConfigController@probar')->where('sucursal_id', '[0-9]+');
    Route::post('mercadopago/movimientos/sincronizar/{sucursal_id}', 'MercadoPagoMovimientosController@sincronizar')->where('sucursal_id', '[0-9]+');
});
Route::get('mercadopago/movimientos', 'MercadoPagoMovimientosController@index');
```

Add `Route::post('mercadopago/qr', 'MercadoPagoQrController@crear');` as the last line INSIDE the throttle group, and after the `movimientos` line (outside the group) add:

```php
// Polling del estado del QR: fuera del grupo 20,1 (a ~4s son ~15 req/min y lo agotaría).
Route::get('mercadopago/qr/estado', 'MercadoPagoQrController@estado')->middleware('throttle:30,1');
```

- [ ] **Step 2: Write the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Sucursales;
use App\Services\MercadoPago\MercadoPagoService;
use Illuminate\Http\Request;

class MercadoPagoQrController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Crea un link de pago por el total de la venta en curso, para la sucursal de la sesión. */
    public function crear(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:99999999',
        ]);

        $sucursalId = Sucursales::getSucursal();

        return response()->json($mp->crearPreferencia($sucursalId, (float) $data['monto']));
    }

    /** Estado del cobro: ¿ya hay un pago aprobado con esta referencia? */
    public function estado(Request $request, MercadoPagoService $mp)
    {
        $data = $request->validate([
            'ref' => 'required|string|max:100',
        ]);

        $sucursalId = Sucursales::getSucursal();

        return response()->json($mp->buscarPagoPorReferencia($sucursalId, $data['ref']));
    }
}
```

Save as `app/Http/Controllers/MercadoPagoQrController.php`.

- [ ] **Step 3: Regression + manual verification against the real DB**

Full suite (nothing should change): `docker compose exec -T app vendor/bin/phpunit` → OK (64 tests).

Manual (live DB; note the real token means `estado` does a real read-only MP search — fine):

```bash
docker compose up -d
ROW=$(docker exec sigav_db mysql -uroot -psecret laravel -N -e "SELECT usuario, rol_id, sucursal_id FROM usuarios WHERE rol_id >= 2 LIMIT 1")
USER=$(echo "$ROW" | awk '{print $1}'); ROL_ID=$(echo "$ROW" | awk '{print $2}'); SUC_ID=$(echo "$ROW" | awk '{print $3}')
SEMILLA='$%Reset20122017AnnaLuca#^'
ROL_HASH=$(php -r "echo sha1('$SEMILLA' . $ROL_ID . '$SEMILLA');")
SUC_HASH=$(php -r "echo sha1('$SEMILLA' . $SUC_ID . '$SEMILLA');")
COOKIES="kiosco=$USER; rol=$ROL_HASH; sucursal=$SUC_HASH"

# 1) estado con ref inexistente -> 200 {"ok":true,"pagado":false} (y setea XSRF-TOKEN)
curl -s -c /tmp/mpqr_jar -b "$COOKIES" "http://localhost:8080/mercadopago/qr/estado?ref=handshake"; echo

# 2) POST sin CSRF -> 419
curl -s -o /dev/null -w "%{http_code}\n" -b "$COOKIES" -X POST -d "monto=100" http://localhost:8080/mercadopago/qr

# 3) POST con XSRF -> 200 {"ok":true,"ref":"QR-...","init_point":"https://..."}
XSRF=$(grep XSRF-TOKEN /tmp/mpqr_jar | awk '{print $NF}' | php -r 'echo urldecode(stream_get_contents(STDIN));')
curl -s -b "$COOKIES" -b /tmp/mpqr_jar -H "X-XSRF-TOKEN: $XSRF" -X POST -d "monto=100" http://localhost:8080/mercadopago/qr; echo
```

Expected: (1) `{"ok":true,"pagado":false}`, (2) `419`, (3) JSON with `"ok":true` and a real `init_point` URL. **Do not pay the created preference.** If (3) returns `{"ok":false,"mensaje":"No hay token cargado."}`, the session's sucursal has no token — check which sucursal the forged cookie maps to vs. where the token was loaded (`SELECT sucursal_id FROM mercadopago_config WHERE access_token IS NOT NULL`), and forge the cookie for that sucursal's user.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php app/Http/Controllers/MercadoPagoQrController.php
git commit -m "feat(mercadopago): endpoints para crear el QR de cobro y polear su estado"
```

---

### Task 4: Frontend — botón, panel QR y polling en ventas

**Files:**
- Modify: `public/ventas.php`
- Modify: `public/assets/js/pages/ventas.js`

**Interfaces:**
- Consumes: `POST /mercadopago/qr` + `GET /mercadopago/qr/estado` (Task 3), global `QRCode` from `/assets/js/qr/qrcode.js`, existing DOM (`#total_ventas`) and file-level vars in `ventas.js` (`subtotal_con_descuento`, `recalcularTotal()`).
- Produces: the user-facing flow. Nothing downstream.

No automated test (legacy jQuery page — precedent). Manual verification with headless Chrome in Step 4.

- [ ] **Step 1: Edit `public/ventas.php` — button in the "Venta en curso" header**

Find (around line 158-166):

```html
                <span class="sg-total-badge">Total&nbsp; $ <span id="total_ventas">0.00</span></span>
            </header>
```

Replace with:

```html
                <span class="sg-total-badge">Total&nbsp; $ <span id="total_ventas">0.00</span></span>
                <button type="button" class="btn btn-sm btn-success" id="cobrar_mp" style="margin-left:8px;">Cobrar con MP</button>
            </header>
```

- [ ] **Step 2: Edit `public/ventas.php` — hidden QR panel + script include**

2a. Find the `<!-- Factura -->` section (`<section class="sg-card" id="factura_iframe" ...>`, around line 272) and insert immediately BEFORE it:

```html
        <!-- Cobro con QR de Mercado Pago -->
        <section class="sg-card" id="panel_qr_mp" style="display:none;">
            <header class="sg-card__head">
                <div class="sg-card__title"><span class="sg-dot"></span><h3>Cobro con Mercado Pago</h3></div>
                <span class="sg-total-badge">$ <span id="monto_qr_mp">0.00</span></span>
            </header>
            <div class="sg-card__body" style="text-align:center;">
                <div id="qr_mp" style="display:inline-block; padding:16px; background:#fff;"></div>
                <p id="estado_qr_mp" style="font-size:16px; font-weight:600; margin-top:12px;">Esperando pago&hellip;</p>
                <button type="button" class="btn btn-default" id="cancelar_qr_mp">Cancelar</button>
            </div>
        </section>
```

2b. Find the ventas.js include (around line 360):

```html
    <script src="/assets/js/pages/ventas.js?v=<?php echo rand(); ?>"></script>
```

and add the QR library on the line BEFORE it:

```html
    <script src="/assets/js/qr/qrcode.js"></script>
    <script src="/assets/js/pages/ventas.js?v=<?php echo rand(); ?>"></script>
```

- [ ] **Step 3: Edit `public/assets/js/pages/ventas.js`**

3a. At the END of the file (after `recalcularTotal()`), add the file-level helpers:

```js
// --- Cobro con QR de Mercado Pago ---
var qrMpPoll = null;
var qrMpTimeout = null;

function leerCookie(nombre) {
    var match = document.cookie.match(new RegExp('(^|;\\s*)' + nombre + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : null;
}

function detenerQrMp() {
    if (qrMpPoll) { clearInterval(qrMpPoll); qrMpPoll = null; }
    if (qrMpTimeout) { clearTimeout(qrMpTimeout); qrMpTimeout = null; }
    $("#panel_qr_mp").hide();
    $("#qr_mp").empty();
}
```

3b. Inside the `jQuery("document").ready(function(){ ... })` block (e.g. right after the `$("#descuento_total_input").on(...)` handler around line 214), add:

```js
        // Cobrar con MP: crea el link de pago por el total actual y lo muestra como QR.
        jQuery("#cobrar_mp").click(function(){
            var monto = parseFloat($("#total_ventas").html()) || 0;
            if (monto <= 0) { alert("No hay monto para cobrar."); return; }

            // Handshake CSRF: cualquier GET a Laravel setea la sesión y la cookie XSRF-TOKEN
            // (esta página legacy no tiene el token embebido).
            $.get("/mercadopago/qr/estado", { ref: "handshake" }).always(function(){
                $.ajax({
                    url: "/mercadopago/qr",
                    method: "POST",
                    headers: { "X-XSRF-TOKEN": leerCookie("XSRF-TOKEN") },
                    data: { monto: monto },
                    dataType: "json"
                }).done(function(r){
                    if (!r.ok) { alert(r.mensaje || "No se pudo generar el QR."); return; }

                    detenerQrMp();
                    $("#monto_qr_mp").html(monto.toFixed(2));
                    $("#estado_qr_mp").html("Esperando pago&hellip;").css("color", "");
                    new QRCode(document.getElementById("qr_mp"), {
                        text: r.init_point,
                        width: 220,
                        height: 220,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                    $("#panel_qr_mp").show();

                    qrMpPoll = setInterval(function(){
                        $.get("/mercadopago/qr/estado", { ref: r.ref }).done(function(e){
                            if (e.pagado) {
                                clearInterval(qrMpPoll); qrMpPoll = null;
                                if (qrMpTimeout) { clearTimeout(qrMpTimeout); qrMpTimeout = null; }
                                $("#estado_qr_mp").html("&#10003; Pago acreditado").css("color", "#1e9e64");
                            }
                            // Errores transitorios (e.ok false) no cortan el polling.
                        });
                    }, 4000);
                    // La preferencia expira a los 30 min: cortamos el polling ahí.
                    qrMpTimeout = setTimeout(detenerQrMp, 30 * 60 * 1000);
                }).fail(function(){
                    alert("No se pudo generar el QR. Probá de nuevo.");
                });
            });
        });

        jQuery("#cancelar_qr_mp").click(detenerQrMp);
```

- [ ] **Step 4: Manual verification (headless Chrome, real DB)**

Forge cookies for the sucursal that has the token loaded (see Task 3 Step 3 for the recipe; check `mercadopago_config`). Drive the page with headless Chrome via CDP (pattern already used in this repo — set cookies via `Network.setCookie`, navigate to `http://localhost:8080/ventas.php`):

1. Evaluate JS to give the sale a total without needing products/stock in the dev DB (`subtotal_con_descuento` and `recalcularTotal` are file-level in ventas.js):
   `subtotal_con_descuento = 150; recalcularTotal(); document.getElementById('cobrar_mp').click();`
2. Wait ~4 s, screenshot. Expected: the "Cobro con Mercado Pago" panel visible, a rendered QR image, "$ 150.00", "Esperando pago…". **Do not pay the QR.**
3. Evaluate `document.getElementById('cancelar_qr_mp').click();`, screenshot: panel hidden again.
4. Check the browser console (via CDP `Runtime` events or `Log.entryAdded`) for JS errors — must be none.
5. Regression: full PHPUnit suite still OK (64 tests) — no PHP files changed in this task, this is just the standing gate.

If the dev DB's token turns out to be invalid/expired, the click will alert "No se pudo generar el QR." — that still proves the wiring (button → handshake → POST → response handling), but note it in the report as partially verified and flag it.

- [ ] **Step 5: Commit**

```bash
git add public/ventas.php public/assets/js/pages/ventas.js
git commit -m "feat(ventas): botón Cobrar con MP con QR en pantalla y aviso de pago acreditado"
```

---

## Spec Coverage Check

- Botón junto al total, panel con QR, monto, estado y Cancelar → Task 4.
- `crearPreferencia` (ítem ARS, external_reference server-side, expiración 30 min, init_point) → Task 1.
- Polling + "✓ Pago acreditado" + corte a los 30 min + errores transitorios no cortan → Tasks 2 y 4.
- Pago aprobado se cachea en `mercadopago_pagos` (aparece en Movimientos sin sync) → Task 2 (`guardarPago` compartido, extraído sin duplicar).
- Endpoints auth + sucursal de la sesión + throttle separado para polling → Task 3.
- CSRF activo vía handshake XSRF (sin excepciones) → Tasks 3 (verificación 419) y 4 (JS).
- Sin dependencias nuevas (qrcode.js vendorizada) → Task 4.
- Errores MP genéricos + Log::error → Tasks 1-2.
