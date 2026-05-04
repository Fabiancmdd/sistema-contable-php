<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador','consulta');

$cuentas = db()->query(
    'SELECT id, codigo, nombre, tipo, padre_id, imputable, activo
     FROM cuentas ORDER BY codigo'
)->fetchAll();

// Cuentas usadas en algún comprobante (no se pueden borrar).
$enUso = [];
foreach (db()->query(
    'SELECT DISTINCT cuenta_id FROM comprobante_detalles'
) as $r) {
    $enUso[(int)$r['cuenta_id']] = true;
}

// Cuentas con hijas (no se pueden borrar).
$conHijas = [];
foreach (db()->query(
    'SELECT DISTINCT padre_id FROM cuentas WHERE padre_id IS NOT NULL'
) as $r) {
    $conHijas[(int)$r['padre_id']] = true;
}

layoutHead('Plan de cuentas');
?>
<div class="d-flex justify-content-between mb-3">
    <p class="text-muted mb-0">
        Las cuentas marcadas como <strong>imputables</strong> son las únicas
        que se pueden usar en los comprobantes.<br>
        <small>Total: <strong><?= count($cuentas) ?></strong> cuentas.</small>
    </p>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" target="_blank"
           href="<?= e(url('/cuentas/imprimir.php')) ?>">Imprimir</a>
        <?php if (hasRole('admin','operador')): ?>
            <a class="btn btn-primary" href="<?= e(url('/cuentas/nueva.php')) ?>">+ Nueva cuenta</a>
        <?php endif; ?>
    </div>
</div>

<table class="table table-striped table-sm align-middle">
    <thead><tr>
        <th>Código</th><th>Nombre</th><th>Tipo</th><th>Imputable</th><th>Activo</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($cuentas as $c): ?>
        <?php
            $id        = (int)$c['id'];
            $usada     = !empty($enUso[$id]);
            $tieneH    = !empty($conHijas[$id]);
            $puedeBorr = !$usada && !$tieneH;
            $tooltip   = $usada ? 'Tiene movimientos en comprobantes' : ($tieneH ? 'Tiene cuentas hijas' : '');
        ?>
        <tr>
            <td><code><?= e($c['codigo']) ?></code></td>
            <td><?= e($c['nombre']) ?></td>
            <td><span class="badge bg-secondary"><?= e(tipoCuentaLabel($c['tipo'])) ?></span></td>
            <td><?= $c['imputable'] ? 'Sí' : '—' ?></td>
            <td><?= $c['activo']    ? 'Sí' : 'No' ?></td>
            <td class="text-nowrap">
                <?php if (hasRole('admin','operador')): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/cuentas/editar.php')) ?>?id=<?= $id ?>">Editar</a>
                <?php endif; ?>
                <?php if (hasRole('admin')): ?>
                    <?php if ($puedeBorr): ?>
                        <form method="post" action="<?= e(url('/cuentas/borrar.php')) ?>" class="d-inline"
                              onsubmit="return confirm('¿Borrar la cuenta <?= e($c['codigo']) ?> — <?= e($c['nombre']) ?>? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-sm btn-outline-danger">Borrar</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger" disabled
                                title="<?= e($tooltip) ?>">Borrar</button>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php layoutFoot();
