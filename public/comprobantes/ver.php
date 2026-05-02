<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
$me = requireRole('admin','operador','consulta');

$id = (int)($_GET['id'] ?? 0);
$print = isset($_GET['print']);

$stmt = db()->prepare(
    'SELECT c.*, u.nombre AS usuario
     FROM comprobantes c LEFT JOIN usuarios u ON u.id = c.usuario_id
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$comp = $stmt->fetch();
if (!$comp) {
    http_response_code(404);
    exit('Comprobante no encontrado.');
}

$stmt = db()->prepare(
    'SELECT d.*, ct.codigo, ct.nombre
     FROM comprobante_detalles d
     JOIN cuentas ct ON ct.id = d.cuenta_id
     WHERE d.comprobante_id = ?
     ORDER BY d.id'
);
$stmt->execute([$id]);
$dets = $stmt->fetchAll();
$totDebe  = array_sum(array_column($dets, 'debe'));
$totHaber = array_sum(array_column($dets, 'haber'));

$cfg = appConfig();
layoutHead('Comprobante Nº ' . $comp['numero'], $print);
?>

<?php if ($print): ?>
    <script>window.addEventListener('load', () => window.print());</script>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4 class="mb-0"><?= e($cfg['app']['empresa']) ?></h4>
                <small class="text-muted"><?= e($cfg['app']['nombre']) ?></small>
            </div>
            <div class="text-end">
                <h5>Comprobante <?= e(ucfirst($comp['tipo'])) ?> Nº <?= (int)$comp['numero'] ?></h5>
                <div>Fecha: <strong><?= e($comp['fecha']) ?></strong></div>
                <?php if ((int)$comp['anulado']): ?>
                    <span class="badge bg-danger">ANULADO</span>
                <?php endif; ?>
            </div>
        </div>
        <hr>
        <div><strong>Descripción:</strong> <?= e($comp['descripcion'] ?: '—') ?></div>
        <div class="text-muted small">Registró: <?= e($comp['usuario']) ?> · <?= e($comp['creado_en']) ?></div>
    </div>
</div>

<table class="table table-bordered">
    <thead><tr><th>Código</th><th>Cuenta</th><th>Detalle</th>
        <th class="text-end">Debe</th><th class="text-end">Haber</th></tr></thead>
    <tbody>
        <?php foreach ($dets as $d): ?>
            <tr>
                <td><code><?= e($d['codigo']) ?></code></td>
                <td><?= e($d['nombre']) ?></td>
                <td><?= e($d['descripcion']) ?></td>
                <td class="text-end"><?= $d['debe']  > 0 ? money($d['debe'])  : '' ?></td>
                <td class="text-end"><?= $d['haber'] > 0 ? money($d['haber']) : '' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="totales-asiento">
            <td colspan="3" class="text-end">Totales</td>
            <td class="text-end"><?= money($totDebe) ?></td>
            <td class="text-end"><?= money($totHaber) ?></td>
        </tr>
    </tfoot>
</table>

<?php if (!$print): ?>
<div class="no-print">
    <a class="btn btn-outline-dark" href="?id=<?= $id ?>&print=1" target="_blank">Imprimir</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('/comprobantes/index.php')) ?>">Volver</a>
    <?php if (hasRole('admin') && !$comp['anulado']): ?>
        <form method="post" action="<?= e(url('/comprobantes/anular.php')) ?>" class="d-inline" onsubmit="return confirm('¿Anular este comprobante?');">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn btn-outline-danger">Anular</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php layoutFoot();
