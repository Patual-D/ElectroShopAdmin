# ElectroShopAdmin

Panel de administración para **ElectroShop**, plataforma de comercio electrónico especializada en productos electrónicos y servicios técnicos. Permite gestionar clientes, proveedores, empleados, productos, servicios, compras, devoluciones y facturas mediante una interfaz con operaciones CRUD, búsqueda y paginación.

## Tecnologías

- **Backend:** PHP
- **Base de datos:** MySQL (`tienda_electronica`)
- **Frontend:** Bootstrap 5, CSS3, Font Awesome

## Estructura del proyecto

### Raíz

| Archivo              | Descripción                                   |
| -------------------- | --------------------------------------------- |
| `db.php`             | Conexión con la base de datos MySQL           |
| `index.php`          | Página principal (redirige a `cliente.php`)   |
| `cliente.php`        | Listado y gestión de clientes                 |
| `proveedor.php`      | Listado y gestión de proveedores              |
| `empleado.php`       | Listado y gestión de empleados                |
| `producto.php`       | Listado y gestión de productos                |
| `categoria.php`      | Listado y gestión de categorías               |
| `puesto.php`         | Listado y gestión de puestos                  |
| `servicio.php`       | Listado y gestión de servicios técnicos       |
| `tipo_servicio.php`  | Listado y gestión de tipos de servicio        |
| `compra.php`         | Listado de compras realizadas                 |
| `devolucion.php`     | Listado de devoluciones de productos          |
| `factura_compra.php` | Listado de facturas de compra (a proveedores) |
| `factura_venta.php`  | Listado de facturas de venta (a clientes)     |
| `delete_admin.php`   | Eliminación unificada de registros            |
| `toggle_status.php`  | Cambio de estado (activo/inactivo) unificado  |

### Subdirectorios

| Archivo                    | Descripción                                 |
| -------------------------- | ------------------------------------------- |
| `create/cliente.php`       | Crear nuevo cliente                         |
| `create/proveedor.php`     | Crear nuevo proveedor                       |
| `create/empleado.php`      | Crear nuevo empleado                        |
| `create/producto.php`      | Crear nuevo producto                        |
| `create/categoria.php`     | Crear nueva categoría                       |
| `create/puesto.php`        | Crear nuevo puesto                          |
| `create/servicio.php`      | Crear nuevo servicio                        |
| `create/tipo_servicio.php` | Crear nuevo tipo de servicio                |
| `update/cliente.php`       | Editar cliente                              |
| `update/proveedor.php`     | Editar proveedor                            |
| `update/empleado.php`      | Editar empleado                             |
| `update/producto.php`      | Editar producto                             |
| `update/categoria.php`     | Editar categoría                            |
| `update/puesto.php`        | Editar puesto                               |
| `update/servicio.php`      | Editar servicio                             |
| `update/tipo_servicio.php` | Editar tipo de servicio                     |
| `detail/compra.php`        | Detalle de una compra (productos incluidos) |
| `detail/factura.php`       | Detalle de una factura (compras asociadas)  |

## Funcionalidades

- CRUD completo de clientes, proveedores, empleados, productos, categorías, puestos, servicios y tipos de servicio
- Paginación (10 registros por página) en todos los listados
- Búsqueda dinámica en cada módulo
- Cambio de estado (activo / inactivo) para clientes, proveedores, empleados, productos, servicios y tipos de servicio
- Eliminación segura de registros con prepared statements
- Detalle de compra con productos y subtotales
- Detalle de factura con compras asociadas
- Conteo de empleados por puesto y compras por tipo de servicio
- Facturación separada por compras (proveedores) y ventas (clientes)
- Interfaz responsiva con Bootstrap 5
