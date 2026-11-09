# Instalación local de SYSMOVILWT

Esta guía permite crear una base nueva en MySQL 8.0 o superior. No reemplaza el respaldo de una base que ya contiene información.

## Antes de empezar

- Conserva aparte una copia de seguridad si ya tienes datos.
- No subas `config/database.local.php`, credenciales, respaldos ni datos reales a GitHub.
- `000_esquema_base.sql` crea las tablas originales vacías. No contiene usuarios, contraseñas, empleados reales ni transacciones.
- No ejecutes estos pasos sobre una base cuyos datos quieras conservar. Las tablas usan `CREATE TABLE IF NOT EXISTS`, así que no borran datos, pero tampoco actualizan una estructura que ya existe.

## Crear la base de práctica desde MySQL Workbench

1. Abre Workbench y conéctate al servidor local.
2. Usa **File → Open SQL Script…** para abrir cada archivo dentro de `database`.
3. Ejecuta los archivos en este orden y espera el mensaje de finalización antes del siguiente:
   1. `000_esquema_base.sql`
   2. `001_configuracion_sistema.sql`
   3. `002_actividad_sistema.sql`
   4. `003_autenticacion_permisos.sql`
   5. `004_estado_productos.sql`
   6. `005_actor_bitacora.sql`
4. Para cada archivo pulsa **Run** (el rayo). Los scripts seleccionan explícitamente `sysmovilwt_v2`.
5. Opcionalmente, ejecuta `datos_demo.sql` para crear un cliente, una categoría, una marca, un proveedor y un producto ficticios. Este script no crea cuentas de usuario.

## Preparar la conexión PHP

1. Copia `config/database.local.example.php` y nombra la copia `config/database.local.php`.
2. Completa los datos locales del host, usuario, contraseña y base. El archivo real está excluido de Git.
3. Inicia Apache y MySQL.
4. Abre `http://localhost/SYSMOVILWT_Proyecto/setup-admin.php` para crear la primera cuenta administradora.
5. Inicia sesión y crea los demás usuarios desde **Usuarios**.

La base limpia queda sin información de producción. Si necesitas recuperar los datos de tu equipo actual, importa el respaldo privado correspondiente en lugar de `datos_demo.sql`.
