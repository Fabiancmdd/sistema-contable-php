<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador','consulta');

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$print = isset($_GET['print']);

$stmt = db()->prepare(
    "SELECT c.id, c.numero, c.fecha, c.tipo, c.descripcion, c.anulado,
            d.debe, d.haber, d.descripcion AS det_desc,
            ct.codigo, ct.nombre
     FROM comprobantes c
     JOIN comprobante_detalles d ON d.comprobante_id = c.id
     JOIN cuentas ct ON ct.id = d.cuenta_id
     WHERE c.fecha BETWEEN ? AND ?
     ORDER BY c.fecha, c.numero, d.id"
);
$stmt->execute([$desde, $hasta]);
$rows = $stmt->fetchAll();

$totDebe = array_sum(array_column($rows, 'debe'));
$totHaber = array_sum(array_column($rows, 'haber'));

layoutHead('Libro Diario', $print);
if ($print): ?>
    <script>window.addEventListener('load', () => window.print());</script>
<?php endif; ?>

<?php if (!$print): ?>
<form class="row g-2 mb-3 no-print" method="get">
    <div class="col-auto"><label class="form-label">Desde</label>
        <input type="date" class="form-control" name="desde" value="<?= e($desde) ?>"></div>
    <div class="col-auto"><label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>"></div>
    <div class="col-auto align-self-end"><button class="btn btn-secondary">Filtrar</button></div>
    <div class="col-auto align-self-end ms-auto">
        <a class="btn btn-outline-dark" target="_blank" href="?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>&print=1">Imprimir</a>
    </div>
</form>
<?php else: ?>
<h5>Libro Diario · <?= e($desde) ?> al <?= e($hasta) ?></h5>
<?php endif; ?>

<table class="table table-sm table-bordered">
    <thead><tr>
        <th>Fecha</th><th>Nº</th><th>Cuenta</th><th>Detalle</th>
        <th class="text-end">Debe</th><th class="text-end">Haber</th>
    </tr></thead>
    <tbody>
        <?php $compAnt = null; foreach ($rows as $r): ?>
            <?php if ($compAnt !== $r['id']):
                $compAnt = $r['id']; ?>
                <tr class="table-light">
                    <td><?= e($r['fecha']) ?></td>
                    <td><?= (int)$r['numero'] ?></td>
                    <td colspan="2"><strong><?= e(ucfirst($r['tipo'])) ?>:</strong> <?= e($r['descripcion']) ?>
                        <?= (int)$r['anulado'] ? '<span class="badge bg-danger">ANULADO</span>' : '' ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td></td><td></td>
                <td><code><?= e($r['codigo']) ?></code> <?= e($r['nombre']) ?></td>
                <td class="small text-muted"><?= e($r['det_desc']) ?></td>
                <td class="text-end"><?= $r['debe']  > 0 ? money($r['debe'])  : '' ?></td>
                <td class="text-end"><?= $r['haber'] > 0 ? money($r['haber']) : '' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin movimientos en el período.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="totales-asiento">
            <td colspan="4" class="text-end">Totales</td>
            <td class="text-end"><?= money($totDebe) ?></td>
            <td class="text-end"><?= money($totHaber) ?></td>
        </tr>
    </tfoot>
</table>
<?php layoutFoot();
