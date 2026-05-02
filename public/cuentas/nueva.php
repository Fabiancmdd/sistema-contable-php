<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin','operador');

$padres = db()->query('SELECT id, codigo, nombre FROM cuentas WHERE imputable = 0 ORDER BY codigo')->fetchAll();

$errores = [];
$datos = [
    'codigo' => '', 'nombre' => '', 'tipo' => 'activo',
    'padre_id' => '', 'imputable' => 1, 'activo' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    foreach (['codigo','nombre','tipo','padre_id'] as $k) {
        $datos[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $datos['imputable'] = isset($_POST['imputable']) ? 1 : 0;
    $datos['activo']    = isset($_POST['activo'])    ? 1 : 0;

    if ($datos['codigo'] === '') $errores[] = 'Código obligatorio.';
    if ($datos['nombre'] === '') $errores[] = 'Nombre obligatorio.';
    if (!in_array($datos['tipo'], ['activo','pasivo','patrimonio','ingreso','egreso'], true)) {
        $errores[] = 'Tipo inválido.';
    }

    if (!$errores) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO cuentas (codigo, nombre, tipo, padre_id, imputable, activo)
                 VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([
                $datos['codigo'], $datos['nombre'], $datos['tipo'],
                $datos['padre_id'] === '' ? null : (int)$datos['padre_id'],
                $datos['imputable'], $datos['activo'],
            ]);
            flashSet('success', 'Cuenta creada.');
            redirect('/cuentas/index.php');
        } catch (PDOException $e) {
            $errores[] = 'No se pudo crear (¿código duplicado?). ' . $e->getMessage();
        }
    }
}

layoutHead('Nueva cuenta');
?>
<form method="post" class="col-md-7">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input class="form-control" name="codigo" value="<?= e($datos['codigo']) ?>" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" value="<?= e($datos['nombre']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
                <?php foreach (['activo','pasivo','patrimonio','ingreso','egreso'] as $t): ?>
                    <option value="<?= $t ?>" <?= $datos['tipo']===$t?'selected':'' ?>><?= tipoCuentaLabel($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Cuenta padre (opcional)</label>
            <select class="form-select" name="padre_id">
                <option value="">— Sin padre —</option>
                <?php foreach ($padres as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (string)$datos['padre_id']===(string)$p['id']?'selected':'' ?>>
                        <?= e($p['codigo']) ?> — <?= e($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 form-check ms-2 mt-3">
            <input class="form-check-input" type="checkbox" name="imputable" id="imp" <?= $datos['imputable']?'checked':'' ?>>
            <label class="form-check-label" for="imp">Imputable (usable en comprobantes)</label>
        </div>
        <div class="col-md-5 form-check ms-2 mt-3">
            <input class="form-check-input" type="checkbox" name="activo" id="act" <?= $datos['activo']?'checked':'' ?>>
            <label class="form-check-label" for="act">Activa</label>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-secondary" href="/cuentas/index.php">Cancelar</a>
    </div>
</form>
<?php layoutFoot();
