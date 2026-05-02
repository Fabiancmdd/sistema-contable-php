-- Esquema del sistema contable
-- Compatible con MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS sistema_contable
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_contable;

DROP TABLE IF EXISTS comprobante_detalles;
DROP TABLE IF EXISTS comprobantes;
DROP TABLE IF EXISTS cuentas;
DROP TABLE IF EXISTS usuarios;

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
