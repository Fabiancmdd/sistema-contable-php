<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin','operador');

header('Content-Type: application/json; charset=utf-8');

$codigo   = trim((string)($_GET['codigo'] ?? ''));
$ignoreId = (int)($_GET['ignore_id'] ?? 0);

if ($codigo === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$sql = 'SELECT id, nombre FROM cuentas WHERE codigo = ?';
$args = [$codigo];
if ($ignoreId > 0) {
    $sql  .= ' AND id <> ?';
    $args[] = $ignoreId;
}
$stmt = db()->prepare($sql);
$stmt->execute($args);
$row = $stmt->fetch();

echo json_encode([
    'exists' => (bool)$row,
    'nombre' => $row['nombre'] ?? null,
]);
