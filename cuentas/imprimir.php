<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin','operador','consulta');

$cuentas = db()->query(
    "SELECT codigo, nombre, tipo, imputable, activo
     FROM cuentas
     ORDER BY codigo"
)->fetchAll();

$cfg     = appConfig();
$empresa = $cfg['app']['empresa'] ?? '';

layoutHead('Plan de cuentas (impresión)', true);
?>
<script>window.addEventListener('load', () => window.print());</script>

<div class="encabezado-impresion mb-3">
    <h3 class="mb-1"><?= e($empresa) ?: 'Sistema Contable' ?></h3>
    <h5 class="text-muted mb-0">Plan de Cuentas</h5>
    <small>Generado: <?= date('d/m/Y H:i') ?> · Total cuentas: <?= count($cuentas) ?></small>
</div>

<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th style="width:160px">Código</th>
            <th>Nombre</th>
            <th style="width:120px">Tipo</th>
            <th style="width:80px">Imputable</th>
            <th style="width:60px">Activa</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cuentas as $c): ?>
            <tr>
                <td><code><?= e($c['codigo']) ?></code></td>
                <td><?= e($c['nombre']) ?></td>
                <td><?= e(tipoCuentaLabel($c['tipo'])) ?></td>
                <td class="text-center"><?= $c['imputable'] ? 'Sí' : '—' ?></td>
                <td class="text-center"><?= $c['activo']    ? 'Sí' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="mt-4 no-print">
    <button class="btn btn-outline-dark" onclick="window.print()">Imprimir</button>
    <a class="btn btn-link" href="<?= e(url('/cuentas/index.php')) ?>">Volver</a>
</div>
<?php layoutFoot();
