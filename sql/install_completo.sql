-- =====================================================================
-- Sistema Contable · Script SQL completo
-- =====================================================================
-- Pegar este archivo entero en MySQL/MariaDB (phpMyAdmin, HeidiSQL,
-- DBeaver, mysql CLI, Workbench, etc.). Crea la base, todas las tablas,
-- el plan de cuentas inicial y 3 usuarios demo:
--
--   admin@sistema.local     / admin123     (rol: admin)
--   operador@sistema.local  / operador123  (rol: operador)
--   consulta@sistema.local  / consulta123  (rol: consulta)
--
-- Las contraseñas están guardadas como hash BCRYPT (lo que genera
-- password_hash() de PHP). Cambialas apenas entres por primera vez.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sistema_contable
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_contable;

-- ---------- LIMPIEZA (por si ya existían) -----------------------------
DROP TABLE IF EXISTS comprobante_detalles;
DROP TABLE IF EXISTS comprobantes;
DROP TABLE IF EXISTS cuentas;
DROP TABLE IF EXISTS usuarios;

-- ---------- TABLAS ----------------------------------------------------
CREATE TABLE usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('admin','operador','consulta') NOT NULL DEFAULT 'operador',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cuentas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    codigo     VARCHAR(20) NOT NULL UNIQUE,
    nombre     VARCHAR(150) NOT NULL,
    tipo       ENUM('activo','pasivo','patrimonio','ingreso','egreso') NOT NULL,
    padre_id   INT NULL,
    imputable  TINYINT(1) NOT NULL DEFAULT 1,
    activo     TINYINT(1) NOT NULL DEFAULT 1,
    creada_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cuentas_padre FOREIGN KEY (padre_id) REFERENCES cuentas(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_cuentas_tipo  ON cuentas(tipo);
CREATE INDEX idx_cuentas_padre ON cuentas(padre_id);

CREATE TABLE comprobantes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    numero      INT NOT NULL UNIQUE,
    fecha       DATE NOT NULL,
    tipo        ENUM('diario','ingreso','egreso','traspaso') NOT NULL DEFAULT 'diario',
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    usuario_id  INT NOT NULL,
    anulado     TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE INDEX idx_comprobantes_fecha ON comprobantes(fecha);

CREATE TABLE comprobante_detalles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    comprobante_id  INT NOT NULL,
    cuenta_id       INT NOT NULL,
    debe            DECIMAL(14,2) NOT NULL DEFAULT 0,
    haber           DECIMAL(14,2) NOT NULL DEFAULT 0,
    descripcion     VARCHAR(255) NOT NULL DEFAULT '',
    CONSTRAINT fk_det_comp   FOREIGN KEY (comprobante_id) REFERENCES comprobantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_det_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas(id),
    CONSTRAINT chk_no_neg    CHECK (debe >= 0 AND haber >= 0)
) ENGINE=InnoDB;
CREATE INDEX idx_det_cuenta ON comprobante_detalles(cuenta_id);
CREATE INDEX idx_det_comp   ON comprobante_detalles(comprobante_id);

-- ---------- USUARIOS DEMO ---------------------------------------------
-- Hashes BCRYPT generados con password_hash() de PHP.
INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES
('Administrador', 'admin@sistema.local',    '$2y$10$3z/Xx8xoiaqK4YwhfV2/6uSsoGmojYBa8wnDNEITxOTsOBgzFTr6y', 'admin',    1),
('Operador',      'operador@sistema.local', '$2y$10$navlqaQSk6jyVfU6LQSCCO0USNGRdK1j52kpCA5c7i4vwOGsxgv5e', 'operador', 1),
('Consulta',      'consulta@sistema.local', '$2y$10$c8HwuUzjMuSaw7JYXay7Y.FGuXrr8MIZgx6PwGinL6JXexXn2KCD.', 'consulta', 1);

-- ---------- PLAN DE CUENTAS INICIAL (15 cuentas, formato X.X.XX.XX.XX) ---
-- ACTIVO
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('1.0.00.00.00', 'ACTIVO', 'activo', NULL, 0);
SET @act = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('1.1.00.00.00', 'Caja',        'activo', @act, 1),
('1.2.00.00.00', 'Banco',       'activo', @act, 1),
('1.3.00.00.00', 'Clientes',    'activo', @act, 1),
('1.4.00.00.00', 'Mercaderías', 'activo', @act, 1);

-- PASIVO
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('2.0.00.00.00', 'PASIVO', 'pasivo', NULL, 0);
SET @pas = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('2.1.00.00.00', 'Proveedores',       'pasivo', @pas, 1),
('2.2.00.00.00', 'IVA Débito Fiscal', 'pasivo', @pas, 1);

-- PATRIMONIO
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('3.0.00.00.00', 'PATRIMONIO', 'patrimonio', NULL, 0);
SET @pat = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('3.1.00.00.00', 'Capital', 'patrimonio', @pat, 1);

-- INGRESOS
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('4.0.00.00.00', 'INGRESOS', 'ingreso', NULL, 0);
SET @ing = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('4.1.00.00.00', 'Ventas', 'ingreso', @ing, 1);

-- EGRESOS
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('5.0.00.00.00', 'EGRESOS', 'egreso', NULL, 0);
SET @egr = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('5.1.00.00.00', 'Sueldos y Jornales', 'egreso', @egr, 1),
('5.2.00.00.00', 'Servicios',          'egreso', @egr, 1);

-- ---------- VERIFICACIÓN ---------------------------------------------
-- (opcional) descomentá las siguientes líneas para chequear la carga:
-- SELECT COUNT(*) AS usuarios   FROM usuarios;
-- SELECT COUNT(*) AS cuentas    FROM cuentas;
