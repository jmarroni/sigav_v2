# Manual de Puesta en Marcha - SIGAV

## Introduccion

Este manual describe los pasos iniciales para poner en funcionamiento el sistema SIGAV (Sistema Integral de Gestion y Administracion de Ventas).

---

## Requisitos Previos

### Requisitos del Servidor

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- Apache o Nginx
- Extensiones PHP: mysqli, gd, curl, openssl, json
- Composer (para dependencias)

### Requisitos para Facturacion Electronica

- Certificado digital de AFIP
- Clave privada del certificado
- CUIT de la empresa
- Punto de venta habilitado en AFIP

---

## Paso 1: Configuracion del Perfil de Empresa

### Acceso

1. Ingrese al sistema con el usuario administrador
2. Menu: **"Perfil"** o acceda a `/perfil.php`

### Datos a Completar

1. **Nombre Fantasia**
   - Nombre comercial de su empresa
   - Aparecera en el encabezado del sistema

2. **Razon Social**
   - Nombre legal registrado en AFIP
   - Se imprime en facturas

3. **Direccion Fiscal**
   - Domicilio legal de la empresa
   - Aparece en comprobantes

4. **Mail**
   - Email de contacto principal
   - Se usa para envio de facturas

5. **Telefono**
   - Numero de contacto

6. **Provincia y Localidad**
   - Ubicacion de la empresa

7. **Logo**
   - Suba el logo de su empresa
   - Formatos: JPG, PNG
   - Aparece en login y comprobantes

### Guardar

Haga clic en **"Configurar"** para guardar los datos

---

## Paso 2: Crear la Primera Sucursal

### Acceso

1. Menu: **"Sucursales"** o `/sucursales.php`

### Datos Requeridos

1. **Nombre**: Identificador de la sucursal
2. **Direccion**: Ubicacion fisica
3. **Codigo Postal**: CP de la ubicacion
4. **Provincia**: Provincia
5. **Punto de Venta**: Numero asignado en AFIP (importante para facturacion)
6. **Fecha de Alta**: Fecha de apertura

### Guardar

Haga clic en **"Anadir"**

---

## Paso 3: Configurar Usuarios

### Crear Usuario Administrador

1. Acceda a **"Usuarios"** (`/usuario`)
2. Complete:
   - Nombre completo
   - Usuario de acceso
   - Clave segura
   - Rol: Administrador
   - Sucursal: La creada en paso anterior
3. Haga clic en **"Anadir"**

### Crear Usuarios Vendedores

Repita el proceso para cada vendedor:
- Asigne rol "Vendedor" o "Supervisor" segun corresponda
- Asigne la sucursal donde operaran

---

## Paso 4: Configuracion de AFIP (Facturacion Electronica)

### Requisitos Previos AFIP

1. Tener certificado digital (archivo .crt)
2. Tener clave privada (archivo .key)
3. Haber habilitado el punto de venta en AFIP

### Acceso

1. Menu: **"Configuracion AFIP"** o `/configuracion_afip.php`

### Datos a Configurar

1. **Clave Privada (key)**
   - Pegue el contenido del archivo .key
   - Incluye "-----BEGIN RSA PRIVATE KEY-----" y "-----END RSA PRIVATE KEY-----"

2. **Certificado (crt)**
   - Pegue el contenido del archivo .crt
   - Incluye "-----BEGIN CERTIFICATE-----" y "-----END CERTIFICATE-----"

3. **Punto de Venta**
   - Numero de punto de venta habilitado en AFIP
   - Debe coincidir con el de la sucursal

4. **Tipo de Comprobante**
   - Seleccione el tipo por defecto
   - Generalmente "Factura B" para consumidores finales

5. **CUIT**
   - CUIT de la empresa (11 digitos sin guiones)

6. **Emitir siempre Factura Electronica**
   - Active para emitir automaticamente a AFIP
   - Desactive para modo presupuesto

7. **Ingresos Brutos**
   - Numero de inscripcion en IIBB

8. **Inicio de Actividades**
   - Fecha de inicio segun AFIP

9. **Condicion frente al IVA**
   - "Responsable Inscripto", "Monotributista", etc.

10. **Solicitar Datos al comprador**
    - Active para pedir CUIT/CUIL del cliente
    - Necesario para Facturas A

### Probar Configuracion

1. Haga clic en **"Guardar y Probar"**
2. El sistema intentara conectar con AFIP
3. Si hay error, verifique certificados y CUIT

---

## Paso 5: Configurar Proveedores

### Acceso

1. Menu: **"Proveedores"** o `/proveedor`

### Crear Proveedores

1. Ingrese nombre del proveedor
2. Complete datos de contacto
3. Haga clic en **"Guardar"**

Repita para cada proveedor de productos

---

## Paso 6: Configurar Categorias

### Acceso

1. Menu: **"Categorias"** o `/categoria`

### Crear Categorias

1. Ingrese nombre de la categoria
2. Opcionalmente asigne categoria padre (subcategorias)
3. Haga clic en **"Guardar"**

Ejemplos de categorias:
- Ropa
- Electronica
- Alimentos
- Bebidas

---

## Paso 7: Cargar Productos Iniciales

### Acceso

1. Menu: **"Carga"** o `/carga`

### Para cada producto

1. Complete los datos:
   - Nombre
   - Proveedor
   - Categoria
   - Precio de venta
   - Costo
   - Codigo de barras (opcional)
   - Stock inicial
   - Stock minimo

2. Suba imagenes (opcional)
3. Haga clic en **"Guardar"**

---

## Paso 8: Asignar Stock por Sucursal

Si tiene multiples sucursales:

1. Acceda a **"Stock por Sucursal"**
2. Seleccione la sucursal
3. Para cada producto, ingrese:
   - Stock disponible
   - Stock minimo de alerta
4. Guarde los cambios

---

## Paso 9: Configurar Clientes (Opcional)

Si necesita facturar a clientes habituales:

1. Acceda a **"Clientes"** (`/cliente.php`)
2. Cargue los datos de cada cliente:
   - Razon Social
   - CUIT
   - Direccion
   - Condicion IVA
   - Datos de contacto

---

## Verificacion Final

### Lista de Verificacion

- [ ] Perfil de empresa completo con logo
- [ ] Al menos una sucursal creada
- [ ] Usuarios creados y con acceso
- [ ] Certificados AFIP configurados (si usa facturacion electronica)
- [ ] Proveedores cargados
- [ ] Categorias definidas
- [ ] Productos cargados con stock
- [ ] Prueba de facturacion exitosa

### Prueba de Venta

1. Ingrese como usuario vendedor
2. Vaya a **"Ventas"**
3. Agregue un producto al carrito
4. Complete los datos del cliente
5. Haga clic en **"Concretar Venta y Facturar"**
6. Verifique que se genere la factura correctamente

---

## Problemas Frecuentes en la Puesta en Marcha

### Error de conexion con AFIP
- Verifique que el certificado no este vencido
- Confirme que el CUIT sea correcto
- Verifique el punto de venta

### No aparecen productos en ventas
- Asegurese de que tengan stock asignado
- Verifique que la sucursal sea la correcta

### Usuario no puede acceder
- Confirme que tenga una sucursal asignada
- Verifique el rol y permisos

### Logo no aparece
- Verifique formato del archivo (JPG, PNG)
- Confirme que se haya guardado correctamente

---

## Soporte

Para consultas adicionales:
- Revise los manuales especificos de cada modulo
- Consulte el plan de testing para verificar funcionalidades
- Contacte al administrador del sistema
