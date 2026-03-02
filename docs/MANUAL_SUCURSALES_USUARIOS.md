# Manual de Alta de Sucursales y Usuarios - SIGAV

## Parte 1: Gestion de Sucursales

### Acceso al Modulo de Sucursales

1. Ingrese al sistema con un usuario administrador
2. En el menu lateral, haga clic en **"Sucursales"**
3. O acceda directamente a `/sucursales.php`

---

### Alta de Nueva Sucursal

#### Paso 1: Completar el Formulario

1. **Nombre**
   - Nombre identificador de la sucursal
   - Ejemplo: "Sucursal Centro", "Local Shopping"

2. **Direccion**
   - Direccion completa del local
   - Ejemplo: "Av. Rivadavia 1234, Viedma, Rio Negro"

3. **Codigo Postal**
   - Codigo postal de la ubicacion
   - Ejemplo: "8500"

4. **Provincia**
   - Provincia donde se ubica
   - Ejemplo: "Rio Negro"

5. **Fecha de Alta**
   - Fecha de apertura de la sucursal
   - Formato: dd/mm/yyyy

6. **Fecha de Baja** (Opcional)
   - Solo completar si la sucursal cierra
   - Dejar vacio para sucursales activas

7. **Imagen** (Opcional)
   - Subir foto o logo de la sucursal
   - Formatos: JPG, PNG

8. **Punto de Venta**
   - Numero de punto de venta para AFIP
   - Importante para facturacion electronica
   - Ejemplo: "1", "2", "3"

#### Paso 2: Guardar

1. Haga clic en **"Anadir"**
2. Aparecera mensaje de confirmacion
3. La sucursal aparecera en la lista inferior

---

### Modificar Sucursal

1. En la lista de sucursales, ubique la deseada
2. Haga clic en **"Modificar"**
3. Los datos se cargan en el formulario
4. Realice los cambios necesarios
5. Haga clic en **"Anadir"** para guardar

---

### Eliminar Sucursal

1. Ubique la sucursal en la lista
2. Haga clic en **"Eliminar"**
3. Confirme la eliminacion

**Advertencia**: Al eliminar una sucursal:
- Se pierden los registros de stock asociados
- Las ventas historicas mantienen referencia

---

## Parte 2: Gestion de Usuarios

### Tipos de Usuarios

El sistema maneja dos tipos de usuarios:

1. **Usuarios del Sistema (Internos)**
   - Acceden al panel de administracion
   - Realizan ventas, cargas, reportes
   - Gestionados en `/usuario`

2. **Usuarios API (Externos)**
   - Acceden via API desde aplicaciones externas
   - Gestionados en `/usuarios_api.php`

---

### Alta de Usuario del Sistema

#### Acceso

1. Menu lateral: **"Usuarios"** o acceda a `/usuario`

#### Completar Datos

1. **Nombre**
   - Nombre completo del usuario
   - Ejemplo: "Juan Perez"

2. **Usuario**
   - Nombre de acceso al sistema
   - Sin espacios, minusculas recomendado
   - Ejemplo: "jperez"

3. **Clave**
   - Contrasena de acceso
   - Minimo 6 caracteres recomendado

4. **Rol**
   - Nivel de permisos del usuario
   - Ver seccion "Roles" mas abajo

5. **Sucursal**
   - Sucursal asignada por defecto
   - El usuario operara principalmente en esta sucursal

#### Guardar

1. Haga clic en **"Anadir"**
2. El usuario queda activo inmediatamente

---

### Roles de Usuario

| Nivel | Rol | Permisos |
|-------|-----|----------|
| 1 | Vendedor | Solo ventas |
| 2 | Vendedor+ | Ventas + ver clientes |
| 3 | Cargador | Carga de productos |
| 4 | Supervisor | Ventas, cargas, reportes basicos |
| 5 | Administrador | Acceso completo |

---

### Modificar Usuario

1. En la lista de usuarios, haga clic en **"Modificar"**
2. Edite los campos necesarios
3. Para cambiar clave, ingrese la nueva
4. Haga clic en **"Guardar"**

---

### Eliminar Usuario

1. Haga clic en **"Eliminar"** junto al usuario
2. Confirme la eliminacion
3. El usuario ya no podra acceder

**Nota**: Las acciones realizadas por el usuario permanecen en los logs

---

### Alta de Usuario API

#### Acceso

1. Menu lateral: **"Usuarios API"** o `/usuarios_api.php`

#### Completar Datos

1. **Nombre**
   - Nombre identificador

2. **Email**
   - Email unico del usuario

3. **Password**
   - Clave para autenticacion API

#### Asignar Sucursales

Cada usuario API puede tener acceso a multiples sucursales:

1. Ubique el usuario en la lista
2. En la columna "Sucursales permitidas" vera las asignadas
3. Para agregar:
   - Seleccione la sucursal en el dropdown
   - Haga clic en **"Agregar sucursal"**
4. Para quitar:
   - Seleccione la sucursal en "Sucursales permitidas"
   - Haga clic en **"Sacar sucursal"**

---

## Parte 3: Gestion de Roles

### Acceso

1. Menu lateral: **"Roles"** o `/rol`

### Crear Nuevo Rol

1. Ingrese el nombre del rol
2. Configure los permisos
3. Haga clic en **"Guardar"**

### Modificar Rol

1. Haga clic en **"Modificar"** junto al rol
2. Ajuste los permisos
3. Guarde los cambios

---

## Buenas Practicas

### Para Sucursales

1. **Nombres claros**: Use nombres que identifiquen facilmente la ubicacion
2. **Punto de venta**: Cada sucursal debe tener un numero unico para AFIP
3. **Actualizar datos**: Mantenga direcciones y datos actualizados

### Para Usuarios

1. **Usuarios unicos**: Cada persona debe tener su propio usuario
2. **Claves seguras**: Minimo 8 caracteres, combinar letras y numeros
3. **Rol adecuado**: Asigne solo los permisos necesarios
4. **Baja oportuna**: Elimine usuarios cuando dejen la empresa

---

## Problemas Frecuentes

### No puedo acceder a Sucursales/Usuarios
- Verifique que su usuario tenga rol de administrador
- Contacte al administrador del sistema

### El punto de venta ya existe
- Cada sucursal debe tener un numero de punto de venta unico
- Verifique los numeros asignados a otras sucursales

### Usuario no puede ingresar
- Verifique usuario y clave
- Confirme que el usuario este activo
- Verifique que tenga una sucursal asignada
