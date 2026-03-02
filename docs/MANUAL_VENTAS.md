# Manual de Ventas - SIGAV

## Acceso al Modulo de Ventas

1. Ingrese al sistema con su usuario y clave
2. El sistema lo redirigira automaticamente al area de ventas si su rol lo permite
3. Tambien puede acceder desde el menu lateral haciendo clic en **Ventas**

---

## Pantalla Principal de Ventas

Al ingresar al modulo de ventas vera:

### Indicadores Superiores
- **Productos Vendidos**: Cantidad de productos vendidos por usted en el dia
- **Caja**: Monto actual en caja
- **Facturado**: Total facturado en el dia
- **Total**: Suma de caja + facturado

---

## Realizar una Venta

### Paso 1: Buscar el Producto

Tiene dos formas de buscar productos:

#### Opcion A: Por Codigo de Barras
1. Posicione el cursor en el campo **"Codigo de Barras"**
2. Escanee el codigo con el lector o ingreselo manualmente
3. El sistema buscara automaticamente el producto

#### Opcion B: Por Nombre
1. Escriba parte del nombre del producto en el campo **"Nombre"**
2. Aparecera una lista desplegable con coincidencias
3. Seleccione el producto deseado

### Paso 2: Verificar Stock
- El campo **"Stock"** mostrara la cantidad disponible en su sucursal
- Si no hay stock, el valor sera 0

### Paso 3: Indicar Cantidad
1. En el campo **"Cant"** ingrese la cantidad a vender
2. Por defecto viene con valor 1

### Paso 4: Verificar/Modificar Precio
1. El campo **"Monto"** mostrara el precio del producto
2. Puede modificarlo si es necesario (ej: descuentos)

### Paso 5: Agregar al Carrito
1. Haga clic en el boton **"Anadir"**
2. El producto aparecera en la tabla de venta actual
3. El total se actualizara automaticamente

### Paso 6: Repetir para mas productos
- Repita los pasos 1-5 para cada producto a vender

---

## Eliminar Producto del Carrito

Si agrego un producto por error:
1. Ubique el producto en la tabla de venta actual
2. Haga clic en el boton **"Eliminar"** (icono de papelera) junto al producto

---

## Configurar la Venta

### Forma de Pago
Seleccione una opcion:
- **Efectivo**: Pago en efectivo
- **Debito**: Tarjeta de debito
- **Credito**: Tarjeta de credito
- **Transferencia**: Transferencia bancaria

### Condicion IVA del Cliente
Seleccione la condicion fiscal:
- **Resp. Inscripto**: Responsable Inscripto en IVA
- **Monotributista**: Contribuyente monotributo
- **Excento**: Excento de IVA
- **Cons. Final**: Consumidor Final (opcion por defecto)

---

## Datos del Cliente (Opcional)

Si el sistema tiene habilitada la opcion "Solicitar Datos del Cliente":

1. **Nombre y apellido**: Ingrese el nombre del cliente
   - Puede buscar clientes existentes escribiendo parte del nombre
2. **Direccion**: Domicilio del cliente
3. **Tipo de Documento**: CUIT, CUIL o CDI
4. **Documento**: Numero sin guiones (11 digitos)
5. **Fecha de Facturacion**: Por defecto es la fecha actual

---

## Concretar la Venta

### Con Factura Electronica AFIP
1. Verifique que todos los datos esten correctos
2. Haga clic en **"Concretar Venta y facturar"**
3. Espere mientras el sistema:
   - Genera la factura electronica en AFIP
   - Descuenta el stock
   - Genera el PDF de la factura
4. La factura aparecera en el visor inferior

### Sin Factura (Presupuesto)
1. Si el boton "Concretar Venta" esta visible, uselo para generar solo un presupuesto
2. No se emite factura electronica a AFIP

---

## Enviar Factura por Email

Una vez concretada la venta:
1. En el campo **"Mail donde enviar factura"** ingrese el email del cliente
2. Haga clic en **"Enviar Factura"**
3. Aparecera el mensaje "El mail fue enviado correctamente"

---

## Ver Ventas del Dia

En la parte inferior de la pantalla vera:
- Lista de todas las ventas realizadas hoy
- Imagen del producto
- Hora de venta
- Usuario que realizo la venta
- Precio y stock restante

---

## Consejos y Recomendaciones

1. **Verifique siempre el stock** antes de ofrecer un producto
2. **Confirme el precio** antes de concretar la venta
3. **Solicite el CUIT/CUIL** si el cliente necesita factura A
4. **Espere la confirmacion** de AFIP antes de entregar el producto
5. **Imprima o envie** la factura al cliente

---

## Problemas Frecuentes

### El producto no aparece en la busqueda
- Verifique que el producto tenga stock en su sucursal
- Revise que el codigo de barras sea correcto
- Intente buscar por nombre

### Error al facturar
- Verifique la conexion a internet
- Confirme que los certificados AFIP esten vigentes
- Contacte al administrador del sistema

### El precio no es correcto
- Puede modificar el precio manualmente en el campo "Monto"
- Consulte con su supervisor para descuentos especiales
