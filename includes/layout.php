<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function layoutHead(string $titulo, bool $print = false): void
{
    $cfg = appConfig();
    $u   = currentUser();
    $nombreApp = $cfg['app']['nombre'] ?? 'Sistema Contable';
    $empresa   = $cfg['app']['empresa'] ?? '';
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e($nombreApp) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="<?= $print ? 'modo-impresion' : '' ?>">
<?php if (!$print): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3 no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php"><?= e($nombreApp) ?></a>
        <?php if ($u): ?>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/index.php">Inicio</a></li>
                <?php if (hasRole('admin')): ?>
                    <li class="nav-item"><a class="nav-link" href="/usuarios/index.php">Usuarios</a></li>
                <?php endif; ?>
                <?php if (hasRole('admin','operador')): ?>
                    <li class="nav-item"><a class="nav-link" href="/cuentas/index.php">Plan de cuentas</a></li>
                    <li class="nav-item"><a class="nav-link" href="/comprobantes/index.php">Comprobantes</a></li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Reportes</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/reportes/libro_diario.php">Libro Diario</a></li>
                        <li><a class="dropdown-item" href="/reportes/libro_mayor.php">Libro Mayor</a></li>
                        <li><a class="dropdown-item" href="/reportes/estado_resultados.php">Estado de Resultados</a></li>
                    </ul>
                </li>
            </ul>
            <span class="navbar-text text-light me-3">
                <?= e($u['nombre']) ?> <small class="opacity-75">(<?= e(rolLabel($u['rol'])) ?>)</small>
            </span>
            <a class="btn btn-outline-light btn-sm" href="/logout.php">Salir</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>
<main class="container">
<?php if (!$print): ?>
    <h2 class="mb-3"><?= e($titulo) ?></h2>
    <?= flashRender() ?>
<?php endif; ?>
<?php
}

function layoutFoot(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
