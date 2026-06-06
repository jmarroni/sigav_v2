# Diseño: Configuración AFIP + switch Homologación/Producción (Laravel-native)

- **Fecha:** 2026-06-06
- **Estado:** Aprobado (diseño) — pendiente plan de implementación
- **Autor:** desarrollo@bairesai.com (con Claude Code)

## Problema

Hoy la facturación electrónica AFIP de SIGAV v2 es **100% legacy** (scripts `public/*.php`
sobre el SDK vendado `public/vendor/afipsdk/afip.php`). Tiene dos problemas que impiden
testear la facturación de forma segura:

1. **No existe modo homologación.** Los 11 archivos que instancian el SDK están
   hardcodeados a producción (`new Afip(array('CUIT' => ..., "production" => TRUE))`).
   AFIP separa homologación y producción en entornos distintos con certificados
   distintos. Hoy cualquier prueba **emite comprobantes reales en producción**.

2. **Credenciales en el webroot.** El certificado y la clave privada viven en
   `public/AFIP/{cert,key}` y `public/vendor/afipsdk/afip.php/src/Afip_res/{cert,key}`,
   accesibles por web salvo reglas del servidor. Riesgo de exposición de secretos.

### Objetivo

Permitir cargar **dos juegos de credenciales** (homologación y producción) y un
**switch global** que elige cuál se usa en ventas, construido **del lado de Laravel**,
sacando las credenciales del webroot, y tendiendo a eliminar los `.php` legacy de
configuración.

## Decisiones tomadas (brainstorming)

| Decisión | Resultado |
|---|---|
| Alcance del switch | **Global** (un estado para todo el sistema) |
| UI | Pantalla con **switch maestro + acordeón** de 2 secciones (homo / prod) |
| Indicador en ventas | Sí — badge visible del entorno activo |
| Dónde se construye | **Laravel** (DB + storage + Blade + Service) |
| Storage de credenciales | **Fuera del webroot** (`storage/app/afip/...`) |
| Alcance de esta entrega | Config + modo en Laravel **ahora**; la emisión real sigue legacy leyendo desde Laravel vía un puente delgado. La migración del billing a Laravel es un paso posterior. |
| Migración de creds actuales | **No migrar.** Se eliminan las credenciales del webroot. Se recargan homo y prod desde la pantalla nueva. |

## Hallazgos del SDK que habilitan el diseño

- `Afip.php:347-351` — los WSDL (`wsfe.wsdl` vs `wsfe-production.wsdl`) y la URL se
  resuelven desde el **directorio propio del SDK** (`__DIR__/Afip_res/`) según el flag
  `production`, **no** desde `res_folder`.
- `Afip.php:128-129, 118-122` — de `res_folder`/`ta_folder` el SDK solo toma `cert`,
  `key` y el cache de tokens `TA-*.xml`.
- Conclusión: apuntando `res_folder`/`ta_folder` a `storage/app/afip/{homo,prod}/`
  (solo `cert`, `key` + tokens) y pasando `production` según el modo, **no hay que
  parchear el vendor**.
- `app/Http/Middleware/LegacyCookieAuth.php` — puentea la cookie legacy (`kiosco`/`rol`/
  `sucursal`) a la sesión `web` de Laravel. Una pantalla Blade con `middleware('auth')`
  es alcanzable por un usuario logueado en el flujo legacy.

## Arquitectura

### Fuente de verdad

- **DB (Laravel):** modo activo + datos escalares por entorno.
- **Archivos fuera del webroot:** `cert`/`key` + cache de tokens.

### 1. Base de datos — migración `afip_config`

Una fila por entorno:

| campo | tipo | nota |
|---|---|---|
| `id` | bigint PK | |
| `entorno` | enum('homo','prod') | único |
| `cuit` | string nullable | |
| `ptovta` | string nullable | punto de venta por defecto |
| `comprobante` | string nullable | tipo de comprobante |
| `condicion_iva` | string nullable | |
| `inicio_actividades` | date/string nullable | |
| `ingresos_brutos` | string nullable | |
| `emitir` | boolean default 0 | "emitir siempre factura electrónica" |
| `solicitar_datos` | boolean default 0 | "solicitar datos al comprador" |
| `activo` | boolean default 0 | **exactamente uno en true = el switch** |
| `timestamps` | | |

**Invariante:** exactamente una fila con `activo = 1`. El cambio de switch debe ser
atómico (transacción: poner todas en 0, luego la elegida en 1).

**Seeder:** dos filas vacías (`homo`, `prod`); **`homo` con `activo = 1`** por defecto.

> Nota multi-sucursal: hoy `ventas.php`/`configuracion_afip.php` toman `ptovta` desde
> `sucursales.pto_vta`. El `ptovta` de `afip_config` es el valor por defecto de la
> pantalla de configuración; **la emisión sigue usando el `pto_vta` de la sucursal**
> como hoy. No se cambia ese comportamiento.

### 2. Credenciales fuera del webroot

```
storage/app/afip/
├── homo/
│   ├── cert            # PEM (0600)
│   ├── key             # PEM (0600)
│   └── TA-*.xml        # cache de tokens (lo escribe el SDK)
└── prod/
    └── (idéntico)
```

- Carpeta y archivos con permisos restrictivos; el web server necesita lectura/escritura
  (escribe el cache de TA).
- `.gitignore`: no commitear `storage/app/afip/**` salvo `.gitkeep`.

### 3. Componentes Laravel

**Model — `app/Models/AfipConfig.php`**
- Tabla `afip_config`, `$fillable` explícito (sin `id`/`activo` por mass-assignment;
  `activo` se maneja por método dedicado).
- `scopeActivo()`, `static activa()`, `tieneCredenciales(): bool` (existencia de
  `cert` y `key` en su carpeta), `rutaStorage(): string`.

**Service — `app/Services/Afip/AfipService.php`**
- Carga el SDK vendado: `require_once base_path('public/vendor/afipsdk/afip.php/src/Afip.php')`
  (transicional; mover el SDK a composer es cleanup posterior).
- `instancia(?string $entorno = null): \Afip` — usa el entorno dado o el activo; arma:
  ```php
  new \Afip([
      'CUIT'       => floatval($cfg->cuit),
      'production'  => $entorno === 'prod',
      'res_folder' => $rutaStorage,   // con trailing slash
      'ta_folder'  => $rutaStorage,
  ]);
  ```
- `probar(string $entorno): array` — `GetLastVoucher($ptovta,$comprobante)`; devuelve
  `['ok'=>bool, 'mensaje'=>string, 'detalle'=>?mixed]`. Captura excepciones (no filtra
  stack traces al usuario; loguea el detalle server-side).
- `tiposComprobante(string $entorno): array` — `GetVoucherTypes()` para poblar el select;
  devuelve `[]` si falla (sin romper la pantalla).
- `guardarCredenciales(string $entorno, string $cert, string $key): void` — escribe a
  storage con permisos restrictivos. Valida que parezcan PEM antes de escribir.

**Controller — `app/Http/Controllers/AfipConfigController.php`**
- `__construct`: `middleware('auth')` + verificación de rol ≥ 2 (mismo criterio que
  `configuracion_afip.php`; reusar el patrón de rol de los controllers Blade existentes).
- `index()` — muestra el acordeón con ambos entornos + estado del switch + tipos de
  comprobante por entorno.
- `guardar(Request, $entorno)` — valida y persiste datos escalares del entorno.
- `subirCredenciales(Request, $entorno)` — recibe cert/key como texto PEM pegado en
  textarea (mismo método que la pantalla legacy actual), valida PEM, delega en el service.
- `activar(Request)` — setea el entorno activo (transacción atómica).
- `probar(Request, $entorno)` — corre `AfipService::probar` y devuelve JSON/redirect con
  el resultado.

**Validación de entrada (todas las acciones):**
- `entorno` ∈ {`homo`,`prod`}.
- `cuit`: numérico, 11 dígitos.
- `ptovta`, `comprobante`: enteros.
- `cert`/`key`: no vacíos y con cabecera PEM (`-----BEGIN`).

**Rutas — `routes/web.php`**
```
GET   afip/configuracion            -> index
POST  afip/configuracion/{entorno}  -> guardar
POST  afip/credenciales/{entorno}   -> subirCredenciales
POST  afip/activar                  -> activar
POST  afip/probar/{entorno}         -> probar
```
(Acciones de estado siempre POST; CSRF de Laravel activo.)

**Vista — `resources/views/afip/configuracion.blade.php`**
- Layout admin existente (`resources/views/layout/layout.blade.php`).
- Switch maestro arriba: "Entorno activo: Homologación / Producción".
- Acordeón con 2 secciones; cada una con: key, cert, CUIT, punto de venta, tipo de
  comprobante (select poblado por `tiposComprobante`), ingresos brutos, inicio de
  actividades, condición IVA, toggles `emitir` y `solicitar_datos`, y botón
  "Guardar y Probar".
- Cada sección indica si tiene credenciales cargadas y el último resultado de prueba.
- Sin diseño "template genérico": jerarquía clara, estados de los toggles/acordeón
  intencionales.

### 4. Puente legacy (transicional) — `public/afip_bridge.php`

Mientras la emisión real siga en `.php`, un único puente delgado, **sin credenciales
embebidas**:

- `require_once __DIR__.'/vendor/afipsdk/afip.php/src/Afip.php'`.
- Lee el entorno **activo** y los escalares desde la **misma tabla `afip_config`**
  usando el `mysqli` de `conection.php` (consulta parametrizada con `mysqli_prepare`).
- `cert`/`key` desde `storage/app/afip/{entorno}/` (ruta `dirname(__DIR__).'/storage/app/afip/...'`).
- Expone:
  - `afip_instance(): \Afip` — instancia configurada para el entorno activo.
  - `afip_valor(string $clave): ?string` — escalar del entorno activo (cuit, ptovta, ...).
  - `afip_modo(): string` — `'homo'` | `'prod'`.

**Refactor de los archivos de billing** para usar el puente en lugar de
`new Afip(array(...))` y `file_get_contents(.../Afip_res/...)`:
`facturar.php`, `ventas.php`, `nota_de_credito.php`, `nota_de_debito.php`,
`devoluciones.php`, `obtener_tiposDocumentos.php`, `obtener_alicuotas.php`,
`taxt_types.php`. (`facturarbk26-07-2022.php` es un backup; ver sección Limpieza.)

> El puente lee la tabla que Laravel administra: **fuente única**, no duplica lógica.
> Cuando el billing migre a Laravel, usará la misma tabla vía Eloquent y el puente se
> elimina.

### 5. Indicador en ventas

Badge visible del entorno activo, leyendo `afip_modo()`:
- `HOMOLOGACIÓN` → color de advertencia (warning).
- `PRODUCCIÓN` → color neutro/normal.

Ubicación: `public/ventas.php` (y opcionalmente `public/header.php` para que aparezca en
todo el área legacy de facturación).

### 6. Limpieza legacy

**Eliminar credenciales del webroot:**
- `public/AFIP/cert`, `public/AFIP/key`.
- `public/vendor/afipsdk/afip.php/src/Afip_res/{cert,key,cuit,ptovta,comprobante,condicion_iva,inicio_actividades,ingresos_brutos,emitir,solicitar_datos,TA-*.xml,TRA-*.xml}`.
- **Conservar** en `Afip_res/`: los `*.wsdl` y `.htaccess` (el SDK los sigue usando).

**Eliminar `.php` reemplazados por Laravel:**
- `public/configuracion_afip.php`
- `public/guardar_certificados.php`
- `public/emitir_online.php`
- `public/solicitar_datos.php`
- `public/assets/js/pages/afip.js`

Antes de borrar: auditar referencias (`header('Location:')`, `action=`, links de menú en
`header.php`/vistas) y reapuntar al nuevo `afip/configuracion`.

`public/facturarbk26-07-2022.php`: es un backup viejo; no se refactoriza. Evaluar
eliminarlo en la limpieza (confirmar que no está referenciado).

### 7. Seguridad

- Credenciales fuera del webroot (no descargables por web).
- Pantalla bajo `middleware('auth')` + rol ≥ 2.
- CSRF de Laravel en todas las acciones de estado.
- Consultas del puente con `mysqli_prepare` (no interpolación).
- `storage/app/afip/` escribible por el web server (cache TA) → documentar en `deploy/`.
- No commitear credenciales (`.gitignore`).
- No filtrar stack traces de AFIP al usuario; loguear server-side.

## Punto operacional crítico

Al eliminar las credenciales de `public/`, **la facturación de producción deja de
funcionar hasta recargar las credenciales de producción en la pantalla nueva**.

Orden de cutover seguro:
1. Deploy del código nuevo (DB + pantalla + puente).
2. Cargar credenciales de **homologación** y dejar el switch en `homo`.
3. Probar facturación en homologación.
4. Cargar credenciales de **producción**.
5. Recién entonces, eliminar las credenciales legacy del webroot.
6. Cambiar el switch a `prod` cuando se quiera volver a emitir real.

## Testing

No hay infraestructura de tests en el repo (ver CLAUDE.md). Plan de verificación:

1. **Unit (sin pegar a AFIP):** crear base `TestCase` + `phpunit` mínimo; testear
   `AfipService::instancia` (paths, flag `production` por entorno) y la invariante de
   `activo` único al activar.
2. **Manual:** forjar cookies legacy (técnica documentada en memoria
   `carga-grid-serverside.md`, DB real `laravel`); cargar homo, "Guardar y Probar",
   verificar badge en ventas, y un alta de comprobante en homologación con credenciales
   reales (el usuario las tiene).

## Fuera de alcance (siguiente etapa)

- Migración del billing real (emisión de CAE, notas, devoluciones) a controladores/
  servicios Laravel.
- Mover el SDK AFIP a composer (raíz) y eliminar `public/vendor/afipsdk`.
- Eliminar el puente `afip_bridge.php` una vez migrado el billing.

## Archivos afectados (resumen)

**Nuevos:**
- `database/migrations/XXXX_create_afip_config_table.php`
- `database/seeds/AfipConfigSeeder.php`
- `app/Models/AfipConfig.php`
- `app/Services/Afip/AfipService.php`
- `app/Http/Controllers/AfipConfigController.php`
- `resources/views/afip/configuracion.blade.php`
- `public/afip_bridge.php`
- `.gitignore` (entrada para `storage/app/afip`)

**Modificados:**
- `routes/web.php`
- Billing legacy: `facturar.php`, `ventas.php`, `nota_de_credito.php`,
  `nota_de_debito.php`, `devoluciones.php`, `obtener_tiposDocumentos.php`,
  `obtener_alicuotas.php`, `taxt_types.php`
- `public/header.php` (menú → nueva pantalla; badge opcional)
- `deploy/` (doc de permisos `storage/app/afip`)

**Eliminados:**
- `public/configuracion_afip.php`, `public/guardar_certificados.php`,
  `public/emitir_online.php`, `public/solicitar_datos.php`,
  `public/assets/js/pages/afip.js`
- Credenciales en `public/AFIP/*` y `public/vendor/.../Afip_res/*` (salvo `.wsdl`/`.htaccess`)
- (a confirmar) `public/facturarbk26-07-2022.php`
