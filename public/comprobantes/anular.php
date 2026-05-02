<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
checkCsrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('UPDATE comprobantes SET anulado = 1 WHERE id = ?');
$stmt->execute([$id]);
flashSet('warning', 'Comprobante anulado.');
redirect(url('/comprobantes/ver.php') . '?id=' . $id);
