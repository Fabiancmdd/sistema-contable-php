# Sistema Contable (PHP + MySQL)

Sistema contable simple en PHP + MySQL/MariaDB sin frameworks. Pensado para ser
fácil de instalar y entender, no para algo "tan pro". Incluye:

- Login con sesiones y 3 roles (`admin`, `operador`, `consulta`).
- Módulo de **Usuarios** (alta / edición / activar / cambiar contraseña).
- **Plan de Cuentas** jerárquico (activo / pasivo / patrimonio / ingreso / egreso),
  con cuentas imputables vs. no imputables.
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

```bash
# 1. Clonar
git clone https://github.com/Fabiancmdd/sistema-contable-php.git
cd sistema-contable-php

# 2. Configurar credenciales de BD
cp config.example.php config.php
# editar config.php (host, name, user, password)

# 3. Crear BD y datos iniciales
php install.php
# Esto crea la base, importa el schema, carga el plan de cuentas
# y crea los usuarios de ejemplo:
#    admin@sistema.local      / admin123      (rol admin)
#    operador@sistema.local   / operador123   (rol operador)
#    consulta@sistema.local   / consulta123   (rol consulta)

# 4. Levantar el servidor de desarrollo
php -S 0.0.0.0:8000 -t public
# abrir http://localhost:8000/login.php
```

> Cambiar las contraseñas de ejemplo apenas se entre por primera vez.
> En producción, eliminar `install.php` y servir solo el directorio `public/`.

## Estructura del proyecto

```
sistema-contable-php/
├── config.example.php     Plantilla de configuración (DB y empresa)
├── install.php            Instalador (schema + seed + usuarios demo)
├── sql/
│   ├── schema.sql         Definición de tablas
│   └── seed.sql           Plan de cuentas inicial
├── includes/              Lógica compartida (DB, auth, layout, helpers)
└── public/                Document root del servidor web
    ├── index.php          Panel
    ├── login.php / logout.php
    ├── usuarios/          CRUD de usuarios (solo admin)
    ├── cuentas/           CRUD del plan de cuentas
    ├── comprobantes/      Alta, listado, detalle, anulación, impresión
    └── reportes/          libro_diario / libro_mayor / estado_resultados
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
