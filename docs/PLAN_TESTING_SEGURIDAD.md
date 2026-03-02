# Plan de Testing - Correcciones de Seguridad

## Resumen de Cambios Realizados
- Conversión de queries SQL directas a prepared statements
- Agregado de `exit()` después de redirects con `header()`
- Reemplazo de API keys hardcodeadas por variables de entorno
- Agregado de `htmlspecialchars()` para prevenir XSS
- Validación de inputs con `intval()`, `floatval()`, regex

---

## 1. Autenticación y Login

### 1.1 Login (`public/login.php`)
- [ ] Iniciar sesión con usuario válido
- [ ] Verificar que las cookies se crean correctamente
- [ ] Intentar login con credenciales incorrectas
- [ ] Verificar que no hay errores SQL visibles

### 1.2 Logout
- [ ] Cerrar sesión correctamente
- [ ] Verificar que las cookies se eliminan

---

## 2. Gestión de Productos

### 2.1 Carga de Productos (`public/carga_post.php`)
- [ ] Eliminar un producto existente
- [ ] Verificar que se registra en stock_logs
- [ ] Verificar redirect correcto después de eliminar

### 2.2 Búsqueda de Productos (`public/search.php`, `public/search_codigo.php`)
- [ ] Buscar producto por nombre
- [ ] Buscar producto por código de barras
- [ ] Verificar autocompletado funciona

### 2.3 Obtener Productos (`public/get_productos.php`)
- [ ] Obtener producto por ID
- [ ] Obtener producto por código
- [ ] Verificar respuesta JSON correcta

---

## 3. Gestión de Stock

### 3.1 Actualizar Stock (`public/actualizar_stock.php`)
- [ ] Actualizar stock de producto existente
- [ ] Crear nuevo registro de stock para sucursal
- [ ] Verificar redirect correcto

### 3.2 Stock por Sucursal (`public/actualizar_stock_por_sucursal.php`)
- [ ] Modificar stock mínimo
- [ ] Verificar actualización correcta

---

## 4. Ventas

### 4.1 Agregar al Carrito (`public/ventas_post.php`)
- [ ] Agregar producto al carrito
- [ ] Verificar respuesta JSON con ventas_id
- [ ] Producto libre (sin ID) se crea correctamente

### 4.2 Eliminar del Carrito (`public/eliminar_venta.php`)
- [ ] Eliminar producto del carrito
- [ ] Verificar respuesta JSON OK

### 4.3 Facturación (`public/facturar.php`)
- [ ] Crear presupuesto (no genera factura AFIP)
- [ ] Verificar PDF se genera correctamente
- [ ] Verificar datos del cliente en factura
- [ ] Stock se descuenta correctamente
- [ ] Logs de stock se registran

**IMPORTANTE:** No probar facturación real AFIP en producción

---

## 5. Gestión de Clientes

### 5.1 CRUD Clientes (`public/cliente_post.php`)
- [ ] Crear nuevo cliente
- [ ] Actualizar cliente existente
- [ ] Habilitar/deshabilitar cliente
- [ ] Verificar PDF se genera en alta

### 5.2 Buscar Clientes (`public/get_cliente.php`)
- [ ] Buscar por ID
- [ ] Buscar por término (autocompletado)
- [ ] Verificar respuesta JSON

---

## 6. Gestión de Pedidos

### 6.1 CRUD Pedidos (`public/pedidos_post.php`)
- [ ] Crear nuevo pedido
- [ ] Actualizar estado de pedido
- [ ] Verificar número de pedido incremental

### 6.2 Obtener Pedidos (`public/get_pedidos.php`)
- [ ] Obtener pedido por ID
- [ ] Verificar respuesta JSON

---

## 7. Proveedores y Pagos

### 7.1 Pagos a Proveedores (`public/proveedores_post.php`)
- [ ] Registrar nuevo pago
- [ ] Verificar redirect correcto
- [ ] Verificar datos guardados

---

## 8. Sucursales

### 8.1 CRUD Sucursales (`public/sucursales_post.php`)
- [ ] Crear nueva sucursal
- [ ] Actualizar sucursal existente
- [ ] Eliminar sucursal
- [ ] Subir imagen de sucursal

---

## 9. Configuración

### 9.1 Perfil (`public/perfil_post.php`)
- [ ] Actualizar perfil empresa
- [ ] Crear nuevo perfil
- [ ] Subir logo

### 9.2 Servicios (`public/servicios_post.php`)
- [ ] Crear nuevo servicio
- [ ] Actualizar servicio existente

### 9.3 Configuración AFIP (`public/configuracion_afip.php`)
- [ ] Verificar que carga datos desde storage/afip_credentials/
- [ ] Guardar cambios de configuración
- [ ] Verificar emitir_online funciona
- [ ] Verificar solicitar_datos funciona

---

## 10. Reportes

### 10.1 Reportes de Ventas (`public/reportes.php`)
- [ ] Filtrar por fecha desde/hasta
- [ ] Filtrar por proveedor
- [ ] Verificar gráficos cargan
- [ ] Exportar a Excel/PDF

---

## 11. Cuenta Corriente

### 11.1 Obsequios (`public/cta_corriente_post.php`)
- [ ] Registrar obsequio
- [ ] Verificar descuento de stock
- [ ] Verificar redirect correcto

---

## 12. Devoluciones y Notas de Crédito

### 12.1 Devoluciones (`public/devoluciones.php`)
- [ ] Seleccionar factura para devolver
- [ ] Verificar carga de datos de factura

### 12.2 Notas de Crédito/Débito
- [ ] Verificar que usan getAfipConfig()
- [ ] Verificar generación de PDF

---

## 13. Imágenes

### 13.1 Upload de Imágenes (`public/upload_articles/image.php`)
- [ ] Subir imagen de producto
- [ ] Verificar validación de formato (solo jpg/png/gif/webp)
- [ ] Verificar autenticación requerida

---

## 14. APIs

### 14.1 Acceso con API Key
- [ ] Verificar get_productos.php con apiKey
- [ ] Verificar carga_post.php con apiKey
- [ ] Verificar que API key inválida es rechazada

---

## Checklist de Seguridad Post-Testing

### Verificar que NO ocurren estos errores:
- [ ] Errores SQL visibles en pantalla
- [ ] Acceso sin autenticación a páginas protegidas
- [ ] Modificación de datos sin permisos adecuados

### Verificar en logs del servidor:
- [ ] No hay errores PHP fatales
- [ ] No hay warnings de mysqli
- [ ] No hay errores de prepared statements

---

## Notas para el Testing

1. **Ambiente:** Realizar pruebas en ambiente de staging si es posible
2. **Backup:** Hacer backup de BD antes de testear
3. **AFIP:** NO probar facturación real, usar presupuestos
4. **Datos:** Usar datos de prueba, no datos reales de clientes

---

## Registro de Testing

| Fecha | Tester | Módulo | Estado | Observaciones |
|-------|--------|--------|--------|---------------|
|       |        |        |        |               |

