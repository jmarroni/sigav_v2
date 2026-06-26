# Descuentos en ventas — Diseño

**Fecha:** 2026-06-26
**Estado:** Aprobado (pendiente de plan de implementación)
**Rama base:** `feat/afip-config-switch` (definir rama propia para el feature)

## Objetivo

Permitir aplicar **descuentos porcentuales** en la venta del sistema SIGAV v2, de dos formas
combinables y ambas **opcionales**:

1. **Descuento de producto:** cada producto puede tener un `%` de descuento fijo que se aplica
   automáticamente a esa línea cada vez que se vende.
2. **Descuento total de la venta:** el vendedor puede aplicar un `%` de descuento sobre el total
   de la venta al momento de facturar.

El descuento siempre es un **porcentaje** (nunca un monto fijo).

## Decisiones de alcance (acordadas)

| Tema | Decisión |
|------|----------|
| Descuento de producto | Campo fijo en el producto, aplicado automáticamente en cada venta. |
| Descuento total | `%` ingresado al momento de la venta por el vendedor. |
| Combinación | **Apilan**: primero el descuento de línea (producto), luego el descuento total sobre ese subtotal ya descontado. |
| AFIP | La factura electrónica se emite por el **total ya descontado** (base imponible e IVA sobre el total real cobrado). |
| Permisos / tope | **Sin límite**, cualquier vendedor. Solo se valida que el `%` sea válido (clamp a `[0, 100]`). |
| PDF | Muestra **solo el precio final** (líneas ya descontadas y total final), sin desglosar el descuento. |
| Registro | El descuento queda guardado en los datos de la venta (`ventas.descuento`, `factura.descuento_total`). |
| Auditoría | Tabla `descuentos_logs` registra qué usuario aplicó qué descuento, cuándo y por cuánto. |
| Arquitectura | La **lógica de cálculo** vive en una clase PHP pura testeable; el motor AFIP y las pantallas siguen en legacy. No se migra la venta a Laravel (queda como proyecto aparte). |

## Contexto técnico relevante

El flujo de venta es **legacy PHP**:

```
public/ventas.php        (pantalla de venta)
  → public/ventas_post.php   (agrega producto al carrito → tabla productos_en_carrito)
  → public/facturar.php      (descuenta stock, inserta en ventas, llama AFIP WSFE,
                              alta en factura, genera PDF)
```

- El total se calcula hoy como `precio × cantidad` en tres lugares duplicados:
  `public/ventas.php` (grilla), `public/assets/js/pages/ventas.js` (total en vivo),
  `public/facturar.php` (total final + IVA).
- El IVA se calcula inline en `facturar.php` como `neto = total / 1.21`, `iva = neto × 0.21`.
- El precio del producto llega a la pantalla vía `ProductoController@searchProducts`
  (`routes/web.php` → `etiqueta.buscarProductos`), que devuelve JSON con `precio` según
  `lista_precio`.
- El usuario actual en el legacy sale de `$_COOKIE["kiosco"]`; la sucursal de
  `getSucursal($_COOKIE["sucursal"])`.
- No existe directorio `tests/` todavía (ver caveat en `CLAUDE.md`).

## Modelo de datos

### Cambios de esquema (migraciones Laravel)

| Tabla | Columna nueva | Tipo | Default | Para qué |
|-------|---------------|------|---------|----------|
| `productos` | `descuento` | `DECIMAL(5,2)` | `0` | `%` de descuento fijo del producto. |
| `productos_en_carrito` | `descuento` | `DECIMAL(5,2)` | `0` | Snapshot del `%` del producto al agregarlo al carrito (no se altera si luego cambia el producto). |
| `ventas` | `descuento` | `DECIMAL(5,2)` | `0` | `%` de descuento de la línea en la venta concretada. |
| `factura` | `descuento_total` | `DECIMAL(5,2)` | `0` | `%` de descuento total aplicado a la venta. |

`precio` se mantiene como **precio original (sin descontar)** en todas las tablas. El descuento
se guarda en columna aparte para no romper reportes existentes y conservar trazabilidad.

### Tabla de auditoría `descuentos_logs`

Sigue el patrón de las tablas `*_logs` del sistema. A diferencia de `stock_logs`
(`$timestamps = false`), esta tabla **sí usa timestamps Eloquent** (`created_at` / `updated_at`
automáticos). Modelo Eloquent `App\Models\DescuentoLog`.

| Columna | Tipo | Nota |
|---------|------|------|
| `id` | PK | |
| `usuario` | `varchar(200)` | `$_COOKIE["kiosco"]` |
| `sucursal_id` | `int` null | `getSucursal(...)` |
| `tipo_operacion` | `varchar(50)` | `DESCUENTO_TOTAL` \| `DESCUENTO_PRODUCTO_CONFIG` |
| `factura_id` | `int` null | venta/factura asociada (descuento total) |
| `productos_id` | `int` null | producto (config del `%` del producto) |
| `descuento_anterior` | `DECIMAL(5,2)` null | valor previo (en cambios de config) |
| `descuento_nuevo` | `DECIMAL(5,2)` | `%` aplicado/seteado |
| `monto_descontado` | `DECIMAL(12,2)` null | `$` descontado real (en la venta) |
| `created_at` / `updated_at` | `datetime` | |

Casos auditados:
- **Descuento total en la venta:** `tipo_operacion = DESCUENTO_TOTAL`, con `factura_id`,
  `descuento_nuevo` (= `%`), `monto_descontado` (= `$`), `usuario`, `sucursal_id`.
- **Config del descuento de un producto:** `tipo_operacion = DESCUENTO_PRODUCTO_CONFIG`, con
  `productos_id`, `descuento_anterior`, `descuento_nuevo`, `usuario`. Se registra al editar el
  producto (lado Laravel).

## Módulo de cálculo (pieza testeable)

Clase PHP **pura, sin DB ni cookies**, autoloadeada por Composer (PSR-4). Punto único de la
matemática hoy duplicada.

```
App\Ventas\CalculadoraVenta

calcular(array $lineas, float $descuentoTotalPct): array
  // $lineas = [ ['precio' => float, 'cantidad' => int, 'descuento' => float], ... ]
```

### Lógica (descuentos apilados)

1. Por línea: `bruto = precio × cantidad`; `lineaConDesc = round(bruto × (1 − descuento/100), 2)`.
2. `subtotal = Σ lineaConDesc`.
3. `descuentoTotalMonto = round(subtotal × descuentoTotalPct / 100, 2)`.
4. `total = subtotal − descuentoTotalMonto`.
5. IVA sobre el total ya descontado: `neto = round(total / 1.21, 2)`, `iva = round(neto × 0.21, 2)`.

### Retorno

```
[
  'subtotalBruto'        => float,  // Σ precio×cantidad sin descuentos
  'subtotal'             => float,  // con descuento de línea aplicado
  'descuentoTotalMonto'  => float,  // $ del descuento total
  'total'                => float,  // total final cobrado
  'neto'                 => float,  // base imponible (total / 1.21)
  'iva'                  => float,  // IVA 21%
  'lineas'               => [ ['bruto'=>..., 'descuentoMonto'=>..., 'subtotal'=>...], ... ],
]
```

### Validación

- Los `%` se clampean a `[0, 100]`.
- Valores no numéricos (`%`, precio, cantidad) → `0`.
- Carrito vacío → todos los importes en `0`.

## Cambios en el flujo legacy

1. **`ProductoController@searchProducts`** — agrega `descuento` del producto al JSON de la API de
   búsqueda que alimenta la pantalla de venta.
2. **`public/assets/js/pages/ventas.js`** — al agregar un producto guarda el `descuento` de la
   línea; calcula el total en vivo usando los mismos pasos (línea con descuento). Agrega un input
   **"Descuento total %"** cerca del total que recalcula en vivo.
3. **`public/ventas_post.php`** — guarda el `descuento` (snapshot del producto) en
   `productos_en_carrito`.
4. **`public/facturar.php`** — lee las líneas del carrito con su `descuento`, recibe el
   `descuento_total` del form, usa `CalculadoraVenta` para `total`/`neto`/`iva`, persiste
   `ventas.descuento` por línea y `factura.descuento_total`, escribe el registro en
   `descuentos_logs`, y envía a **AFIP el total ya descontado**.
5. **`public/ventas.php`** — la grilla de venta en curso muestra el descuento por línea.
6. **Edición de producto (Laravel)** — el formulario de producto permite setear `descuento`; al
   guardarlo, si cambió, se registra en `descuentos_logs` (`DESCUENTO_PRODUCTO_CONFIG`).

## AFIP / IVA

- AFIP recibe `ImpTotal = total`, `ImpNeto = neto`, `ImpIVA = iva`, todos provenientes de
  `CalculadoraVenta` (ya descontados).
- **No** se cambia la estructura del request WSFE; solo los importes pasan a ser los descontados.
- El cálculo de IVA queda **centralizado** en `CalculadoraVenta` (hoy está inline en
  `facturar.php`).

## PDF

- El PDF muestra **solo el precio final**: líneas ya descontadas y total final, sin línea ni
  columna de descuento.
- Cambio mínimo: los importes que arma el PDF salen del resultado de `CalculadoraVenta`.

## Testing

Infra mínima nueva (no existe `tests/` aún): base `TestCase` + bootstrap PHPUnit. Corre en Docker
(`sigav_app`, `pdo_sqlite`).

### Unit tests — `CalculadoraVenta` (núcleo, sin DB)

- Sin descuentos → total = bruto.
- Solo descuento de línea.
- Solo descuento total.
- **Ambos apilados** (línea primero, total después).
- Redondeo a centavos correcto.
- Base de IVA = total ya descontado (`neto = total / 1.21`).
- Bordes: `0%`, `100%`, `%` inválido / no numérico → clamp / `0`.
- Carrito vacío → todo en `0`.
- Invariante: `subtotal − descuentoTotalMonto = total`.

### Tests de esquema / modelos

- Las columnas nuevas existen con default `0`.
- `DescuentoLog` persiste un registro de `DESCUENTO_TOTAL` con los campos esperados.

## Fuera de alcance

- Migración de la venta / facturación AFIP a Laravel (proyecto aparte con su propio spec).
- Descuentos por monto fijo (`$`).
- Topes de descuento o restricciones por rol.
- Desglose del descuento en el PDF.
- Recalcular reportes históricos de ventas previos al feature.
