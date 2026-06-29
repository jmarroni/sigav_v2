# Importar catálogo de productos desde CSV

**Fecha:** 2026-06-28
**Rama:** feat/descuentos-ventas
**Estado:** aprobado

## Objetivo

Reemplazar el catálogo de prueba actual (101 productos seed + datos
transaccionales de prueba) por el catálogo real provisto en
`CODIGOS PRODUCTOS.csv` (497 filas válidas, 4 proveedores).

## Decisiones (acordadas con el usuario)

1. **Limpieza total** antes de cargar: se borran productos y todos los datos
   transaccionales de prueba que los referencian.
2. **Una categoría por proveedor** (el CSV no trae categoría y `categorias_id`
   es `NOT NULL`).
3. **Generar código de barras único** para filas con código vacío o duplicado.
4. **Precios con centavos** (valor exacto del CSV).

## Formato del CSV

Delimitador `;`, UTF-8, header en la primera línea:

```
CODIGO;PRODUCTO;STOCK;PRECIO ULTIMA COMPRA;VALOR MINORISTA;VALOR MAYORISTA;PROVEEDOR;VENDIDOS;INGRESOS
```

- Plata: `$ 74.802,20` → `.` miles, `,` decimal, prefijo `$` y espacios.
- 3 filas totalmente vacías (sin PRODUCTO) → se omiten.
- Códigos vacíos (con datos) y 3 códigos duplicados → se genera uno único.
- `VENDIDOS` e `INGRESOS` se ignoran.

## Arquitectura

Dos piezas; la lógica delicada se aísla en una clase pura testeable.

### 1. `app/Catalogo/CatalogoCsvParser.php` (puro, sin DB)

`parse(string $csv): array` devuelve:

```php
[
  'productos' => [
    [
      'codigo_barras'    => string,  // real o generado "SC-0001"
      'nombre'           => string,  // talle/color embebido
      'stock'            => int,
      'costo'            => float,    // PRECIO ULTIMA COMPRA
      'precio_unidad'    => float,    // VALOR MINORISTA (precio de venta)
      'precio_mayorista' => float,    // VALOR MAYORISTA
      'proveedor'        => string,   // nombre crudo; el comando mapea a id
    ], ...
  ],
  'omitidas'         => int,  // filas vacías salteadas
  'codigosGenerados' => int,  // códigos sintéticos creados
]
```

Reglas:
- Salta filas sin PRODUCTO.
- `parseMoney`: quita todo menos dígitos/`,`/`.`/`-`, borra `.` (miles),
  convierte `,`→`.`, `floatval`.
- Dedupe de códigos: la primera aparición conserva el código real; vacíos y
  duplicados posteriores reciben `SC-NNNN` (verificado único contra el set).

### 2. `app/Console/Commands/ImportarCatalogo.php`

`php artisan catalogo:importar [--force] [--path=...]`

- Lee el CSV (default `base_path('CODIGOS PRODUCTOS.csv')`), llama al parser,
  muestra el resumen.
- **Sin `--force`: dry-run** — solo muestra qué haría, no escribe.
- **Con `--force`**, todo en una transacción (`DB::transaction`):
  1. Borra (hijos primero): `ventas`, `productos_en_carrito`,
     `descuentos_logs`, `factura`, `stock_logs`, `stock`, `productos`.
     (No toca `clientes`, `usuarios`, `sucursales`, ni las categorías
     existentes; solo agrega las nuevas.)
  2. Crea un proveedor por cada proveedor distinto en los datos.
  3. Crea una categoría por proveedor; `abreviatura` = primera palabra
     (uppercase, única). `categorias_id` = `"{cat_id}_{abrev}"`.
  4. Inserta cada producto + su fila de `stock` en la sucursal 2.
  5. Imprime resumen final.

### Mapeo de campos del producto

| Columna producto    | Origen                              |
|---------------------|-------------------------------------|
| `codigo_barras`     | CODIGO (o generado)                 |
| `nombre`            | PRODUCTO                            |
| `precio_unidad`     | VALOR MINORISTA (`%.2f`)            |
| `precio_mayorista`  | VALOR MAYORISTA (`%.2f`)           |
| `costo`             | PRECIO ULTIMA COMPRA (`%.2f`)      |
| `precio_reposicion` | PRECIO ULTIMA COMPRA (`%.2f`)      |
| `stock`             | STOCK (+ fila en tabla `stock` suc 2) |
| `stock_minimo`      | 0                                   |
| `descuento`         | 0                                   |
| `es_comodato`       | 0                                   |
| `descripcion`       | = nombre                            |
| `proveedores_id`    | id del proveedor                    |
| `categorias_id`     | `"{cat_id}_{abrev}"`               |
| `usuario`           | `import`                            |
| `fecha`/timestamps  | ahora                               |

Los precios se guardan como string con punto decimal (`"74802.20"`),
compatible con `floatval`/`parseFloat` del flujo de venta.

## Testing

- Unit puro de `CatalogoCsvParser` (extiende `PHPUnit\Framework\TestCase`):
  parseo de plata en varios formatos, skip de filas vacías, generación de
  código por vacío y por duplicado, mapeo de columnas, conteos.
- El wipe/insert no es testeable con el harness actual (sqlite en memoria sin
  las tablas legacy), así que se valida corriendo el comando en **dry-run**
  contra la DB Docker antes del `--force`.

## Seguridad / reversibilidad

- Operación destructiva: requiere `--force` explícito; dry-run por defecto.
- Todo el wipe + carga va en una transacción: un error de parseo no deja la
  DB a medio borrar.
