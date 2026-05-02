-- =====================================================================
-- Reset del plan de cuentas al formato nuevo (8 dígitos, 15 cuentas)
-- =====================================================================
-- Pegá este archivo entero en phpMyAdmin si ya tenías el plan viejo
-- y querés migrar al formato X.X.XX.XX.XX. NO toca usuarios.
--
-- ATENCIÓN: borra TODOS los comprobantes existentes porque referencian
-- cuentas viejas. Si todavía no cargaste comprobantes, no perdés nada.
-- =====================================================================

USE sistema_contable;

-- 1) Limpiar movimientos y cuentas (los comprobantes se borran en cascada).
DELETE FROM comprobante_detalles;
DELETE FROM comprobantes;
ALTER TABLE comprobantes AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM cuentas;
ALTER TABLE cuentas AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- 2) Cargar las 15 cuentas con el formato nuevo.
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

-- 3) Verificación.
SELECT COUNT(*) AS total_cuentas FROM cuentas;
