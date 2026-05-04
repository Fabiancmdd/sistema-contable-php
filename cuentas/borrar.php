<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

checkCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flashSet('danger', 'ID inválido.');
    redirect(url('/cuentas/index.php'));
}

$stmt = db()->prepare('SELECT codigo, nombre FROM cuentas WHERE id = ?');
$stmt->execute([$id]);
$cuenta = $stmt->fetch();
if (!$cuenta) {
    flashSet('danger', 'Cuenta no encontrada.');
    redirect(url('/cuentas/index.php'));
}

// Guarda 1: ¿usada en algún comprobante?
$stmt = db()->prepare('SELECT COUNT(*) FROM comprobante_detalles WHERE cuenta_id = ?');
$stmt->execute([$id]);
$movs = (int)$stmt->fetchColumn();
if ($movs > 0) {
    flashSet('danger', 'No se puede borrar: la cuenta '
        . $cuenta['codigo'] . ' tiene ' . $movs . ' movimiento(s) en comprobantes.');
    redirect(url('/cuentas/index.php'));
}

// Guarda 2: ¿tiene cuentas hijas?
$stmt = db()->prepare('SELECT COUNT(*) FROM cuentas WHERE padre_id = ?');
$stmt->execute([$id]);
$hijas = (int)$stmt->fetchColumn();
if ($hijas > 0) {
    flashSet('danger', 'No se puede borrar: la cuenta '
        . $cuenta['codigo'] . ' tiene ' . $hijas . ' cuenta(s) hija(s). Borrá las hijas primero.');
    redirect(url('/cuentas/index.php'));
}

try {
    $stmt = db()->prepare('DELETE FROM cuentas WHERE id = ?');
    $stmt->execute([$id]);
    flashSet('success', 'Cuenta ' . $cuenta['codigo'] . ' — ' . $cuenta['nombre'] . ' borrada.');
} catch (PDOException $e) {
    flashSet('danger', 'No se pudo borrar: ' . $e->getMessage());
}
redirect(url('/cuentas/index.php'));
