<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador','consulta');

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');

$stmt = db()->prepare(
    "SELECT c.id, c.numero, c.fecha, c.tipo, c.descripcion, c.anulado,
            u.nombre AS usuario,
            COALESCE(SUM(d.debe),  0) AS total_debe,
            COALESCE(SUM(d.haber), 0) AS total_haber
     FROM comprobantes c
     LEFT JOIN comprobante_detalles d ON d.comprobante_id = c.id
     LEFT JOIN usuarios u ON u.id = c.usuario_id
     WHERE c.fecha BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY c.fecha DESC, c.numero DESC"
);
$stmt->execute([$desde, $hasta]);
$rows = $stmt->fetchAll();

layoutHead('Comprobantes');
?>
<form class="row g-2 mb-3" method="get">
    <div class="col-auto">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control" name="desde" value="<?= e($desde) ?>">
    </div>
    <div class="col-auto">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>">
    </div>
    <div class="col-auto align-self-end">
        <button class="btn btn-secondary">Filtrar</button>
    </div>
    <?php if (hasRole('admin','operador')): ?>
    <div class="col-auto align-self-end ms-auto">
        <a class="btn btn-primary" href="<?= e(url('/comprobantes/nuevo.php')) ?>">+ Nuevo</a>
    </div>
    <?php endif; ?>
</form>

<table class="table table-striped">
    <thead><tr>
        <th>Nº</th><th>Fecha</th><th>Tipo</th><th>Descripción</th>
        <th class="text-end">Debe</th><th class="text-end">Haber</th>
        <th>Usuario</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="<?= (int)$r['anulado'] ? 'text-decoration-line-through text-muted' : '' ?>">
            <td><?= (int)$r['numero'] ?></td>
            <td><?= e($r['fecha']) ?></td>
            <td><?= e(ucfirst($r['tipo'])) ?></td>
            <td><?= e($r['descripcion']) ?></td>
            <td class="text-end"><?= money($r['total_debe']) ?></td>
            <td class="text-end"><?= money($r['total_haber']) ?></td>
            <td><?= e($r['usuario']) ?></td>
            <td><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/comprobantes/ver.php')) ?>?id=<?= (int)$r['id'] ?>">Ver</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="8" class="text-center text-muted">Sin comprobantes en el período.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php layoutFoot();
