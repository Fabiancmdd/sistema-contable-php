<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin','operador','consulta');

$cuentas = db()->query(
    'SELECT id, codigo, nombre, tipo, padre_id, imputable, activo
     FROM cuentas ORDER BY codigo'
)->fetchAll();

layoutHead('Plan de cuentas');
?>
<div class="d-flex justify-content-between mb-3">
    <p class="text-muted mb-0">Las cuentas marcadas como <strong>imputables</strong> son las únicas
        que se pueden usar en los comprobantes.</p>
    <?php if (hasRole('admin','operador')): ?>
        <a class="btn btn-primary" href="/cuentas/nueva.php">+ Nueva cuenta</a>
    <?php endif; ?>
</div>

<table class="table table-striped table-sm">
    <thead><tr>
        <th>Código</th><th>Nombre</th><th>Tipo</th><th>Imputable</th><th>Activo</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($cuentas as $c): ?>
        <tr>
            <td><code><?= e($c['codigo']) ?></code></td>
            <td><?= e($c['nombre']) ?></td>
            <td><span class="badge bg-secondary"><?= e(tipoCuentaLabel($c['tipo'])) ?></span></td>
            <td><?= $c['imputable'] ? 'Sí' : '—' ?></td>
            <td><?= $c['activo']    ? 'Sí' : 'No' ?></td>
            <td>
                <?php if (hasRole('admin','operador')): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="/cuentas/editar.php?id=<?= (int)$c['id'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php layoutFoot();
