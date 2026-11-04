-- =============================================================================
-- Mercado Artesanal: saneo de `clientes.condicion_iva` para RG 5616.
--
-- El puente AFIP (public/afip_bridge.php, afip_cond_iva_receptor) mapea los
-- valores internos '1' (Resp. Inscripto), '2' (Monotributo), '3' (Exento),
-- '4' (Consumidor Final). Cualquier otro valor cae a Consumidor Final, así que
-- la emisión no se rompe; pero en el dump de feb-2026 hay 6 clientes con un
-- CUIT cargado en esta columna y 1 vacío. Este script:
--   1) lista los anómalos (para revisar a mano si alguno es RI/Monotributo),
--   2) los normaliza a '4' (Consumidor Final), que es lo que AFIP recibiría
--      igual.
-- IDEMPOTENTE. Correr DESPUÉS de importar el dump fresco. Los valores previos
-- quedan en `clientes_condicion_iva_backup`.
-- =============================================================================

SELECT id, razon_social, cuit, condicion_iva
  FROM clientes
 WHERE condicion_iva IS NULL OR condicion_iva NOT IN ('1', '2', '3', '4');

-- Respaldo de los valores originales ANTES de tocarlos (campo fiscal): si un
-- cliente resulta ser Resp. Inscripto o Monotributo, se recupera de aca.
CREATE TABLE IF NOT EXISTS clientes_condicion_iva_backup (
  id int NOT NULL PRIMARY KEY,
  condicion_iva varchar(255) NULL,
  cuit varchar(255) NULL,
  guardado_at datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO clientes_condicion_iva_backup (id, condicion_iva, cuit, guardado_at)
SELECT c.id, c.condicion_iva, c.cuit, NOW() FROM clientes c
 WHERE (c.condicion_iva IS NULL OR c.condicion_iva NOT IN ('1', '2', '3', '4'))
   AND NOT EXISTS (SELECT 1 FROM clientes_condicion_iva_backup b WHERE b.id = c.id);

UPDATE clientes
   SET condicion_iva = '4'
 WHERE condicion_iva IS NULL OR condicion_iva NOT IN ('1', '2', '3', '4');

SELECT condicion_iva, COUNT(*) AS clientes FROM clientes GROUP BY condicion_iva;

-- -----------------------------------------------------------------------------
-- Precios guardados como texto con separador de miles (ej. '1.200.000'): no se
-- corrigen automaticamente porque el valor real es ambiguo; se listan para que
-- el operador los corrija desde /carga. Mientras tanto la app los castea a 0
-- en los reportes en vez de romper.
-- -----------------------------------------------------------------------------
SELECT id, codigo_barras, nombre, precio_unidad, precio_mayorista, costo
  FROM productos
 WHERE precio_unidad    NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
    OR precio_mayorista NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
    OR costo            NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$';
