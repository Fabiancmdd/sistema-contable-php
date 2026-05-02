<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin','operador','consulta');

$desde     = $_GET['desde']     ?? date('Y-m-01');
$hasta     = $_GET['hasta']     ?? date('Y-m-t');
$cuentaId  = (int)($_GET['cuenta_id'] ?? 0);
$print     = isset($_GET['print']);

$cuentas = db()->query(
    'SELECT id, codigo, nombre, tipo FROM cuentas WHERE imputable = 1 ORDER BY codigo'
)->fetchAll();

$movs = [];
$saldoIni = 0.0;
$cuenta = null;
if ($cuentaId > 0) {
    foreach ($cuentas as $c) { if ((int)$c['id'] === $cuentaId) { $cuenta = $c; break; } }
    if ($cuenta) {
        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(d.debe - d.haber), 0)
             FROM comprobante_detalles d
             JOIN comprobantes c ON c.id = d.comprobante_id
             WHERE d.cuenta_id = ? AND c.fecha < ? AND c.anulado = 0'
        );
        $stmt->execute([$cuentaId, $desde]);
        $saldoIni = (float)$stmt->fetchColumn();

        $stmt = db()->prepare(
            'SELECT c.fecha, c.numero, c.descripcion AS comp_desc, c.anulado,
                    d.debe, d.haber, d.descripcion AS det_desc
             FROM comprobante_detalles d
             JOIN comprobantes c ON c.id = d.comprobante_id
             WHERE d.cuenta_id = ? AND c.fecha BETWEEN ? AND ?
             ORDER BY c.fecha, c.numero'
        );
        $stmt->execute([$cuentaId, $desde, $hasta]);
        $movs = $stmt->fetchAll();
    }
}

layoutHead('Libro Mayor', $print);
if ($print): ?>
    <script>window.addEventListener('load', () => window.print());</script>
<?php endif; ?>

<?php if (!$print): ?>
<form class="row g-2 mb-3 no-print" method="get">
    <div class="col-md-4"><label class="form-label">Cuenta</label>
        <select name="cuenta_id" class="form-select" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($cuentas as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $cuentaId===(int)$c['id']?'selected':'' ?>>
                    <?= e($c['codigo']) ?> — <?= e($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><label class="form-label">Desde</label>
        <input type="date" class="form-control" name="desde" value="<?= e($desde) ?>"></div>
    <div class="col-auto"><label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>"></div>
    <div class="col-auto align-self-end"><button class="btn btn-secondary">Ver</button></div>
    <?php if ($cuentaId): ?>
    <div class="col-auto align-self-end ms-auto">
        <a class="btn btn-outline-dark" target="_blank"
           href="?cuenta_id=<?= $cuentaId ?>&desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>&print=1">Imprimir</a>
    </div>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if ($cuenta): ?>
<h5>Cuenta: <code><?= e($cuenta['codigo']) ?></code> — <?= e($cuenta['nombre']) ?>
    <small class="text-muted">(<?= e(tipoCuentaLabel($cuenta['tipo'])) ?>)</small></h5>
<p class="text-muted">Período: <?= e($desde) ?> al <?= e($hasta) ?></p>

<table class="table table-sm table-bordered">
    <thead><tr>
        <th>Fecha</th><th>Comp. Nº</th><th>Detalle</th>
        <th class="text-end">Debe</th><th class="text-end">Haber</th><th class="text-end">Saldo</th>
    </tr></thead>
    <tbody>
        <tr class="table-light">
            <td colspan="5" class="text-end"><strong>Saldo inicial</strong></td>
            <td class="text-end"><strong><?= money($saldoIni) ?></strong></td>
        </tr>
        <?php $saldo = $saldoIni; $totD = 0; $totH = 0; foreach ($movs as $m):
            if ((int)$m['anulado']) continue;
            $saldo += (float)$m['debe'] - (float)$m['haber'];
            $totD  += (float)$m['debe'];
            $totH  += (float)$m['haber'];
        ?>
            <tr>
                <td><?= e($m['fecha']) ?></td>
                <td><?= (int)$m['numero'] ?></td>
                <td><?= e($m['det_desc'] ?: $m['comp_desc']) ?></td>
                <td class="text-end"><?= $m['debe']  > 0 ? money($m['debe'])  : '' ?></td>
                <td class="text-end"><?= $m['haber'] > 0 ? money($m['haber']) : '' ?></td>
                <td class="text-end"><?= money($saldo) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="totales-asiento">
            <td colspan="3" class="text-end">Totales del período</td>
            <td class="text-end"><?= money($totD) ?></td>
            <td class="text-end"><?= money($totH) ?></td>
            <td class="text-end"><?= money($saldo) ?></td>
        </tr>
    </tfoot>
</table>
<?php elseif (!$print): ?>
    <div class="text-muted">Seleccione una cuenta para ver el mayor.</div>
<?php endif; ?>
<?php layoutFoot();
