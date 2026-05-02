<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n): string
{
    $cfg = appConfig();
    $simbolo = $cfg['app']['moneda'] ?? '$';
    return $simbolo . ' ' . number_format((float)$n, 2, ',', '.');
}

function flashSet(string $tipo, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function flashRender(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['tipo']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function tipoCuentaLabel(string $tipo): string
{
    return [
        'activo'     => 'Activo',
        'pasivo'     => 'Pasivo',
        'patrimonio' => 'Patrimonio',
        'ingreso'    => 'Ingreso',
        'egreso'     => 'Egreso',
    ][$tipo] ?? $tipo;
}

function rolLabel(string $rol): string
{
    return [
        'admin'    => 'Administrador',
        'operador' => 'Operador',
        'consulta' => 'Consulta',
    ][$rol] ?? $rol;
}
