<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador','consulta');

$desde = $_GET['desde'] ?? date('Y-01-01');
$hasta = $_GET['hasta'] ?? date('Y-12-31');
$print = isset($_GET['print']);

// En contabilidad: ingresos por su naturaleza son acreedoras (haber - debe);
// los egresos son deudoras (debe - haber).
$stmt = db()->prepare(
    "SELECT ct.id, ct.codigo, ct.nombre, ct.tipo,
            COALESCE(SUM(CASE WHEN ct.tipo = 'ingreso' THEN d.haber - d.debe
                              WHEN ct.tipo = 'egreso'  THEN d.debe  - d.haber
                              ELSE 0 END), 0) AS importe
     FROM cuentas ct
     LEFT JOIN comprobante_detalles d ON d.cuenta_id = ct.id
     LEFT JOIN comprobantes c        ON c.id = d.comprobante_id
                                     AND c.anulado = 0
                                     AND c.fecha BETWEEN ? AND ?
     WHERE ct.tipo IN ('ingreso','egreso')
       AND ct.imputable = 1
     GROUP BY ct.id
     HAVING importe <> 0
     ORDER BY ct.tipo DESC, ct.codigo"
);
$stmt->execute([$desde, $hasta]);
$rows = $stmt->fetchAll();

$totIng = 0.0; $totEgr = 0.0;
foreach ($rows as $r) {
    if ($r['tipo'] === 'ingreso') $totIng += (float)$r['importe'];
    else                          $totEgr += (float)$r['importe'];
}
$resultado = $totIng - $totEgr;

$cfg = appConfig();
layoutHead('Estado de Resultados', $print);
if ($print): ?>
    <script>window.addEventListener('load', () => window.print());</script>
<?php endif; ?>

<?php if (!$print): ?>
<form class="row g-2 mb-3 no-print" method="get">
    <div class="col-auto"><label class="form-label">Desde</label>
        <input type="date" class="form-control" name="desde" value="<?= e($desde) ?>"></div>
    <div class="col-auto"><label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>"></div>
    <div class="col-auto align-self-end"><button class="btn btn-secondary">Ver</button></div>
    <div class="col-auto align-self-end ms-auto">
        <a class="btn btn-outline-dark" target="_blank"
           href="?desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>&print=1">Imprimir</a>
    </div>
</form>
<?php endif; ?>

<h5><?= e($cfg['app']['empresa']) ?></h5>
<h6 class="text-muted">Estado de Resultados — <?= e($desde) ?> al <?= e($hasta) ?></h6>

<table class="table table-bordered" style="max-width:700px">
    <thead><tr><th>Código</th><th>Cuenta</th><th class="text-end">Importe</th></tr></thead>
    <tbody>
        <tr class="table-success"><th colspan="3">Ingresos</th></tr>
        <?php foreach ($rows as $r): if ($r['tipo'] !== 'ingreso') continue; ?>
            <tr>
                <td><code><?= e($r['codigo']) ?></code></td>
                <td><?= e($r['nombre']) ?></td>
                <td class="text-end"><?= money($r['importe']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="totales-asiento"><td colspan="2" class="text-end">Total Ingresos</td>
            <td class="text-end"><?= money($totIng) ?></td></tr>

        <tr class="table-warning"><th colspan="3">Egresos</th></tr>
        <?php foreach ($rows as $r): if ($r['tipo'] !== 'egreso') continue; ?>
            <tr>
                <td><code><?= e($r['codigo']) ?></code></td>
                <td><?= e($r['nombre']) ?></td>
                <td class="text-end"><?= money($r['importe']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="totales-asiento"><td colspan="2" class="text-end">Total Egresos</td>
            <td class="text-end"><?= money($totEgr) ?></td></tr>

        <tr class="<?= $resultado >= 0 ? 'table-primary' : 'table-danger' ?>">
            <th colspan="2" class="text-end"><?= $resultado >= 0 ? 'Resultado del ejercicio (Ganancia)' : 'Resultado del ejercicio (Pérdida)' ?></th>
            <th class="text-end"><?= money($resultado) ?></th>
        </tr>
    </tbody>
</table>
<?php layoutFoot();
