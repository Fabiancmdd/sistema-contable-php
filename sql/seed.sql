-- Plan de cuentas básico (los usuarios se crean desde install.php
-- para usar password_hash() de PHP en vez de hashes embebidos).
USE sistema_contable;

INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('1',     'ACTIVO',                 'activo',     NULL, 0);
SET @act = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('1.1',   'Activo Corriente',       'activo',     @act, 0);
SET @ac = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('1.1.01','Caja',                   'activo',     @ac,  1),
('1.1.02','Banco',                  'activo',     @ac,  1),
('1.1.03','Clientes',               'activo',     @ac,  1),
('1.1.04','Mercaderías',            'activo',     @ac,  1);

INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('2',     'PASIVO',                 'pasivo',     NULL, 0);
SET @pas = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('2.1',   'Pasivo Corriente',       'pasivo',     @pas, 0);
SET @pc = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('2.1.01','Proveedores',            'pasivo',     @pc, 1),
('2.1.02','Préstamos',              'pasivo',     @pc, 1),
('2.1.03','IVA Débito Fiscal',      'pasivo',     @pc, 1);

INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('3',     'PATRIMONIO',             'patrimonio', NULL, 0);
SET @pat = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('3.1.01','Capital',                'patrimonio', @pat, 1),
('3.1.02','Resultados Acumulados',  'patrimonio', @pat, 1);

INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('4',     'INGRESOS',               'ingreso',    NULL, 0);
SET @ing = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('4.1.01','Ventas',                 'ingreso',    @ing, 1),
('4.1.02','Ingresos Varios',        'ingreso',    @ing, 1);

INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('5',     'EGRESOS',                'egreso',     NULL, 0);
SET @egr = LAST_INSERT_ID();
INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable) VALUES
('5.1.01','Costo de Ventas',        'egreso',     @egr, 1),
('5.1.02','Sueldos y Jornales',     'egreso',     @egr, 1),
('5.1.03','Servicios',              'egreso',     @egr, 1),
('5.1.04','Gastos Varios',          'egreso',     @egr, 1);
