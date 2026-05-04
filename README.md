# Sistema Contable (PHP + MySQL)

Sistema contable simple en PHP + MySQL/MariaDB sin frameworks. Pensado para ser
fácil de instalar y entender, no para algo "tan pro". Incluye:

- Login con sesiones y 3 roles (`admin`, `operador`, `consulta`).
- Módulo de **Usuarios** (alta / edición / activar / cambiar contraseña).
- **Plan de Cuentas** jerárquico (activo / pasivo / patrimonio / ingreso / egreso),
  con cuentas imputables vs. no imputables. Códigos en formato fijo
  `X.X.XX.XX.XX` (8 dígitos, solo números y puntos), validados en cliente y
  servidor. CRUD completo: alta / edición / borrado (con guardas) / listado /
  vista de impresión. Sin tope de cantidad.
- **Comprobantes** con cabecera + detalles (debe / haber), validación de partida
  doble (`debe = haber`) y numeración automática.
- **Libro Diario**, **Libro Mayor** (con saldo inicial y saldo corriente) y
  **Estado de Resultados** por período.
- **Vista de impresión** del comprobante y de cada reporte.
- Restricciones por rol:
    - `admin`: todo.
    - `operador`: cuentas, comprobantes, reportes (no usuarios, no anular).
    - `consulta`: solo reportes y vista de comprobantes.

## Requisitos

- PHP 8.0+ con `pdo_mysql`.
- MySQL 5.7+ o MariaDB 10.3+.
- (Para desarrollo local) el servidor embebido de PHP.

## Instalación

### Opción A — Auto-instalación (recomendada, ideal para XAMPP / WAMP / MAMP)

1. Copiar la carpeta del proyecto en `htdocs/` (XAMPP) o equivalente.
2. Abrir en el navegador: `http://localhost/<carpeta>/`
3. **Listo.** Si tu MySQL usa los defaults de XAMPP
   (`127.0.0.1` / `root` / sin password), el sistema crea solo la BD, las
   tablas, el plan de cuentas y los 3 usuarios demo, y te lleva al login.
4. Si tu MySQL tiene otra configuración (por ejemplo password de root
   distinta), te muestra automáticamente el asistente web `setup.php`
   donde podés ingresar host / usuario / password / nombre de BD.

### Opción B — Por consola

```bash
# 1. Clonar
git clone https://github.com/Fabiancmdd/sistema-contable-php.git
cd sistema-contable-php

# 2. Configurar credenciales de BD
cp config.example.php config.php
# editar config.php (host, name, user, password)

# 3. Crear BD y datos iniciales
php install.php

# 4. Levantar el servidor de desarrollo
php -S 0.0.0.0:8000
# abrir http://localhost:8000/login.php
```

### Opción C — Pegar el SQL directo en MySQL

Si sólo querés cargar la base sin script de PHP, importá `sql/install_completo.sql`
desde phpMyAdmin / Workbench / DBeaver / mysql CLI. Crea la BD, las tablas,
el plan de cuentas y los 3 usuarios demo.

### Usuarios demo (cualquiera de las opciones)

| Email                        | Contraseña     | Rol      |
|------------------------------|----------------|----------|
| `admin@sistema.local`        | `admin123`     | admin    |
| `operador@sistema.local`     | `operador123`  | operador |
| `consulta@sistema.local`     | `consulta123`  | consulta |

> Cambiar las contraseñas de ejemplo apenas se entre por primera vez.
> En producción: eliminar `install.php` y `setup.php`. Las carpetas
> `includes/`, `sql/` y los archivos `config.php` / `config.example.php`
> ya están protegidos con `.htaccess` (Apache `Require all denied`).

## Estructura del proyecto

```
sistema-contable-php/
├── .htaccess               Bloquea config.php, install.php y listing
├── config.example.php      Plantilla de configuración (DB y empresa)
├── install.php             Instalador por CLI (schema + seed + usuarios)
├── setup.php               Asistente web de instalación
├── index.php               Panel principal
├── login.php / logout.php
├── usuarios/               CRUD de usuarios (solo admin)
├── cuentas/                CRUD del plan de cuentas
├── comprobantes/           Alta, listado, detalle, anulación, impresión
├── reportes/               libro_diario / libro_mayor / estado_resultados
├── assets/                 CSS
├── includes/               Lógica compartida + .htaccess (denied)
└── sql/                    Schema/seed/install_completo + .htaccess (denied)
```

## Notas de diseño

- **Partida doble**: cada renglón de un comprobante guarda `debe` y `haber`
  como `DECIMAL(14,2)`. Antes de persistir se valida que la suma de Debe sea
  igual a la suma de Haber, que cada renglón tenga sólo un lado, y que se
  tengan al menos 2 renglones.
- **Anulación**: los comprobantes no se borran, se marcan `anulado = 1` para
  preservar la trazabilidad. Los reportes los muestran tachados o los excluyen
  (Libro Mayor / Estado de Resultados).
- **CSRF**: todos los formularios incluyen un token de sesión validado en POST.
- **Hash de contraseñas**: `password_hash()` con BCRYPT y `password_verify()`.
- **Plan de cuentas jerárquico**: cuentas con `imputable = 0` actúan como
  agrupadores (ej. `1 ACTIVO`, `1.1 Activo Corriente`); sólo las imputables
  (`1.1.01 Caja`, etc.) pueden cargarse en un asiento.

## Apagado seguro en producción

- Borrar `install.php` después de instalar.
- Configurar el servidor web para apuntar el document root a `public/`.
- Asegurarse de que `config.php` no esté dentro de `public/`.
