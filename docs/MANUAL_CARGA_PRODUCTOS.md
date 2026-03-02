# Manual de Carga de Productos - SIGAV

## Acceso al Modulo de Carga

1. Ingrese al sistema con su usuario y clave
2. En el menu lateral, haga clic en **"Carga"** o acceda a `/carga`
3. Necesita rol de carga o superior para acceder

---

## Pantalla Principal de Carga

La pantalla muestra:
- Formulario para alta/modificacion de productos
- Lista de productos existentes con opciones de edicion

---

## Alta de un Nuevo Producto

### Datos Obligatorios

1. **Nombre del Producto**
   - Ingrese un nombre descriptivo
   - Ejemplo: "Remera Algodon Talle M"

2. **Proveedor**
   - Seleccione el proveedor de la lista desplegable
   - Si no existe, debe darlo de alta primero en el modulo Proveedores

3. **Categoria**
   - Seleccione la categoria correspondiente
   - Si no existe, debe crearla en el modulo Categorias

4. **Precio de Venta**
   - Ingrese el precio unitario de venta
   - Use punto (.) como separador decimal
   - Ejemplo: 1500.00

5. **Costo**
   - Ingrese el costo del producto
   - Importante para reportes de rentabilidad

### Datos Opcionales

6. **Codigo de Barras**
   - Si el producto tiene codigo, ingreselo
   - Si lo deja vacio, el sistema genera uno automaticamente

7. **Stock Inicial**
   - Cantidad inicial en inventario
   - Puede dejarlo en 0 y ajustarlo despues

8. **Stock Minimo**
   - Cantidad minima antes de alertar reposicion
   - Util para control de inventario

9. **Precio Mayorista**
   - Si maneja precios diferenciados
   - Se usa segun la lista de precios activa

10. **Descripcion**
    - Descripcion detallada del producto
    - Visible en reportes y etiquetas

11. **Material**
    - Especificar el material (ej: algodon, cuero, etc.)

12. **Precio de Reposicion**
    - Costo estimado de reposicion futura

### Imagenes del Producto

- Puede subir hasta **7 imagenes** por producto
- Formatos aceptados: JPG, PNG, GIF, WEBP
- El sistema genera automaticamente miniaturas de 300x300 px
- La primera imagen sera la principal

### Guardar el Producto

1. Complete todos los campos requeridos
2. Haga clic en el boton **"Guardar"** o **"Anadir"**
3. Aparecera un mensaje de confirmacion
4. El producto quedara disponible para ventas

---

## Modificar un Producto Existente

### Desde la Lista de Productos

1. Ubique el producto en la tabla
2. Haga clic en **"Modificar"** o el icono de edicion
3. Los datos se cargaran en el formulario superior
4. Realice los cambios necesarios
5. Haga clic en **"Guardar"**

### Modificacion Rapida (Actualizacion de Articulos)

En la pantalla de **Actualizar Articulos** (`/actualizar_articulos.php`):

1. Todos los productos aparecen en una tabla editable
2. Puede modificar directamente:
   - Proveedor
   - Codigo de barras
   - Precio de venta
   - Costo
   - Stock
   - Stock minimo
3. Haga clic en **"Actualizar"** junto al producto modificado
4. El boton cambiara a "OK" confirmando el guardado

---

## Eliminar un Producto

1. Ubique el producto en la lista
2. Haga clic en **"Eliminar"**
3. Confirme la eliminacion
4. El producto se eliminara junto con su stock en todas las sucursales

**Importante**: La eliminacion queda registrada en los logs de auditoria

---

## Gestion de Stock por Sucursal

### Asignar Stock a una Sucursal

1. Acceda a **Stock por Sucursal**
2. Seleccione la sucursal destino
3. Busque el producto
4. Ingrese la cantidad de stock
5. Ingrese el stock minimo de alerta
6. Haga clic en **"Actualizar"**

### Consultar Stock

En la pantalla de consulta de stock puede ver:
- Stock actual por sucursal
- Stock minimo configurado
- Productos que requieren reposicion

---

## Transferencias entre Sucursales

Si maneja multiples sucursales:

1. Acceda a **Transferencias**
2. Seleccione sucursal origen
3. Seleccione sucursal destino
4. Agregue los productos a transferir
5. Indique cantidades
6. Confirme la transferencia

Las transferencias quedan registradas y pueden verse en reportes

---

## Impresion de Etiquetas

1. Acceda al modulo **Etiquetas**
2. Busque los productos a etiquetar
3. Seleccione la cantidad de etiquetas por producto
4. Haga clic en **"Imprimir Etiquetas"**
5. El sistema generara un PDF con las etiquetas

Las etiquetas incluyen:
- Nombre del producto
- Codigo de barras
- Precio
- Codigo QR (opcional)

---

## Auditoria y Logs

El sistema registra automaticamente:
- Altas de productos (fecha, usuario)
- Modificaciones de stock
- Cambios de precios y costos
- Eliminaciones

Puede consultar los logs en:
- **Logs de Productos**: `/logsProductos`
- **Logs de Costos/Precios**: `/logsProductosCostosPrecios`

---

## Consejos y Buenas Practicas

1. **Codigos de barras**: Use codigos unicos, verifique que no existan duplicados
2. **Categorias**: Organice bien las categorias para facilitar busquedas
3. **Stock minimo**: Configure alertas realistas segun rotacion del producto
4. **Imagenes**: Suba fotos de buena calidad, mejoran la identificacion
5. **Descripciones**: Sea detallado, facilita busquedas y reportes

---

## Problemas Frecuentes

### El producto no aparece en ventas
- Verifique que tenga stock asignado a su sucursal
- Confirme que el producto este activo

### Error al subir imagenes
- Verifique el formato (JPG, PNG, GIF, WEBP)
- El archivo no debe superar el limite de tamano

### Codigo de barras duplicado
- Cada producto debe tener un codigo unico
- Si aparece error, verifique que no exista otro producto con ese codigo
