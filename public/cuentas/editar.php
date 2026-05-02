<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin','operador');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM cuentas WHERE id = ?');
$stmt->execute([$id]);
$cuenta = $stmt->fetch();
if (!$cuenta) {
    http_response_code(404);
    exit('Cuenta no encontrada.');
}

$padres = db()->prepare(
    'SELECT id, codigo, nombre FROM cuentas WHERE imputable = 0 AND id <> ? ORDER BY codigo'
);
$padres->execute([$id]);
$padres = $padres->fetchAll();

$tieneMovs = (int)db()->prepare('SELECT COUNT(*) FROM comprobante_detalles WHERE cuenta_id = ?')
    ->execute([$id]) ?: 0;
$stmt = db()->prepare('SELECT COUNT(*) FROM comprobante_detalles WHERE cuenta_id = ?');
$stmt->execute([$id]);
$tieneMovs = (int)$stmt->fetchColumn();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $cuenta['codigo']    = trim((string)($_POST['codigo'] ?? ''));
    $cuenta['nombre']    = trim((string)($_POST['nombre'] ?? ''));
    $cuenta['tipo']      = (string)($_POST['tipo'] ?? $cuenta['tipo']);
    $cuenta['padre_id']  = $_POST['padre_id'] !== '' ? (int)$_POST['padre_id'] : null;
    $imputableNuevo      = isset($_POST['imputable']) ? 1 : 0;
    $cuenta['activo']    = isset($_POST['activo']) ? 1 : 0;

    if ($cuenta['codigo'] === '') $errores[] = 'Código obligatorio.';
    if ($cuenta['nombre'] === '') $errores[] = 'Nombre obligatorio.';
    if ($tieneMovs > 0 && $imputableNuevo !== (int)$cuenta['imputable']) {
        $errores[] = 'No se puede cambiar "imputable" porque la cuenta ya tiene movimientos.';
        $imputableNuevo = (int)$cuenta['imputable'];
    }
    $cuenta['imputable'] = $imputableNuevo;

    if (!$errores) {
        try {
            $stmt = db()->prepare(
                'UPDATE cuentas SET codigo=?, nombre=?, tipo=?, padre_id=?, imputable=?, activo=? WHERE id=?'
            );
            $stmt->execute([
                $cuenta['codigo'], $cuenta['nombre'], $cuenta['tipo'],
                $cuenta['padre_id'], $cuenta['imputable'], $cuenta['activo'], $id,
            ]);
            flashSet('success', 'Cuenta actualizada.');
            redirect(url('/cuentas/index.php'));
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

layoutHead('Editar cuenta ' . $cuenta['codigo']);
?>
<form method="post" class="col-md-7">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if ($tieneMovs > 0): ?>
        <div class="alert alert-warning small">
            Esta cuenta tiene <?= $tieneMovs ?> movimiento(s) asociado(s); no se puede cambiar el flag imputable.
        </div>
    <?php endif; ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input class="form-control" name="codigo" value="<?= e($cuenta['codigo']) ?>" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" value="<?= e($cuenta['nombre']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
                <?php foreach (['activo','pasivo','patrimonio','ingreso','egreso'] as $t): ?>
                    <option value="<?= $t ?>" <?= $cuenta['tipo']===$t?'selected':'' ?>><?= tipoCuentaLabel($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Cuenta padre (opcional)</label>
            <select class="form-select" name="padre_id">
                <option value="">— Sin padre —</option>
                <?php foreach ($padres as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (string)$cuenta['padre_id']===(string)$p['id']?'selected':'' ?>>
                        <?= e($p['codigo']) ?> — <?= e($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 form-check ms-2 mt-3">
            <input class="form-check-input" type="checkbox" name="imputable" id="imp"
                   <?= (int)$cuenta['imputable']?'checked':'' ?>
                   <?= $tieneMovs > 0 ? 'disabled' : '' ?>>
            <label class="form-check-label" for="imp">Imputable</label>
        </div>
        <div class="col-md-5 form-check ms-2 mt-3">
            <input class="form-check-input" type="checkbox" name="activo" id="act" <?= (int)$cuenta['activo']?'checked':'' ?>>
            <label class="form-check-label" for="act">Activa</label>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/cuentas/index.php')) ?>">Cancelar</a>
    </div>
</form>
<?php layoutFoot();
