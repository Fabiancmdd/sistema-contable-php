<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
$u = requireLogin();

$pdo = db();
$totalCuentas    = (int)$pdo->query('SELECT COUNT(*) FROM cuentas WHERE activo = 1')->fetchColumn();
$totalCompr      = (int)$pdo->query('SELECT COUNT(*) FROM comprobantes WHERE anulado = 0')->fetchColumn();
$totalUsuarios   = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn();
$ultimoNumero    = (int)$pdo->query('SELECT COALESCE(MAX(numero), 0) FROM comprobantes')->fetchColumn();

layoutHead('Panel');
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary"><div class="card-body">
            <div class="text-uppercase small">Cuentas activas</div>
            <div class="display-6"><?= $totalCuentas ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success"><div class="card-body">
            <div class="text-uppercase small">Comprobantes</div>
            <div class="display-6"><?= $totalCompr ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-secondary"><div class="card-body">
            <div class="text-uppercase small">Usuarios</div>
            <div class="display-6"><?= $totalUsuarios ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-dark"><div class="card-body">
            <div class="text-uppercase small">Último Nº comprobante</div>
            <div class="display-6"><?= $ultimoNumero ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <?php if (hasRole('admin','operador')): ?>
    <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="<?= e(url('/comprobantes/nuevo.php')) ?>">+ Nuevo comprobante</a></div>
    <div class="col-md-4"><a class="btn btn-outline-secondary w-100 py-3" href="<?= e(url('/cuentas/index.php')) ?>">Plan de cuentas</a></div>
    <?php endif; ?>
    <div class="col-md-4"><a class="btn btn-outline-dark w-100 py-3" href="<?= e(url('/reportes/libro_diario.php')) ?>">Libro Diario</a></div>
</div>
<?php layoutFoot();
