<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador');

const CODIGO_PATTERN_RE   = '/^\d\.\d\.\d{2}\.\d{2}\.\d{2}$/';
const CODIGO_PATTERN_HTML = '\d\.\d\.\d{2}\.\d{2}\.\d{2}';
const CODIGO_EJEMPLO      = '1.0.00.00.00';
const MAX_CUENTAS         = 15;

$padres = db()->query('SELECT id, codigo, nombre FROM cuentas WHERE imputable = 0 ORDER BY codigo')->fetchAll();
$totalCuentas = (int)db()->query('SELECT COUNT(*) FROM cuentas')->fetchColumn();

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

    if ($totalCuentas >= MAX_CUENTAS) {
        $errores[] = 'Tope alcanzado: ya hay ' . MAX_CUENTAS . ' cuentas. Borrá o desactivá una para crear otra.';
    }
    if ($datos['codigo'] === '') {
        $errores[] = 'Código obligatorio.';
    } elseif (!preg_match(CODIGO_PATTERN_RE, $datos['codigo'])) {
        $errores[] = 'Formato de código inválido. Debe ser ' . CODIGO_EJEMPLO . ' (8 dígitos: X.X.XX.XX.XX).';
    }
    if ($datos['nombre'] === '') {
        $errores[] = 'Nombre obligatorio.';
    } elseif (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 150) {
        $errores[] = 'Nombre debe tener entre 2 y 150 caracteres.';
    }
    if (!in_array($datos['tipo'], ['activo','pasivo','patrimonio','ingreso','egreso'], true)) {
        $errores[] = 'Tipo inválido.';
    }
    if ($datos['padre_id'] !== '') {
        $stmt = db()->prepare('SELECT imputable FROM cuentas WHERE id = ?');
        $stmt->execute([(int)$datos['padre_id']]);
        $row = $stmt->fetch();
        if (!$row) {
            $errores[] = 'Cuenta padre inexistente.';
        } elseif ((int)$row['imputable'] === 1) {
            $errores[] = 'La cuenta padre no puede ser imputable.';
        }
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
            redirect(url('/cuentas/index.php'));
        } catch (PDOException $e) {
            $errores[] = 'No se pudo crear (¿código duplicado?). ' . $e->getMessage();
        }
    }
}

layoutHead('Nueva cuenta');
?>
<form method="post" class="col-md-7" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if ($totalCuentas >= MAX_CUENTAS): ?>
        <div class="alert alert-warning small">
            Tope alcanzado: ya tenés <?= $totalCuentas ?> de <?= MAX_CUENTAS ?> cuentas.
            No se puede crear una nueva hasta liberar un cupo.
        </div>
    <?php else: ?>
        <p class="text-muted small">
            Cuentas usadas: <strong><?= $totalCuentas ?>/<?= MAX_CUENTAS ?></strong>.
        </p>
    <?php endif; ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input class="form-control" name="codigo"
                   value="<?= e($datos['codigo']) ?>"
                   pattern="<?= CODIGO_PATTERN_HTML ?>"
                   placeholder="<?= CODIGO_EJEMPLO ?>"
                   title="Formato: X.X.XX.XX.XX (8 dígitos, ej: <?= CODIGO_EJEMPLO ?>)"
                   maxlength="12" required>
            <small class="text-muted">8 dígitos: <code><?= CODIGO_EJEMPLO ?></code></small>
        </div>
        <div class="col-md-8">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre"
                   value="<?= e($datos['nombre']) ?>"
                   minlength="2" maxlength="150" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="tipo" required>
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
        <button class="btn btn-primary" <?= $totalCuentas >= MAX_CUENTAS ? 'disabled' : '' ?>>Guardar</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/cuentas/index.php')) ?>">Cancelar</a>
    </div>
</form>
<?php layoutFoot();
