<?php
ob_start();
require_once '../config.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

$producto_id = $_GET['producto_id'] ?? null;
$almacen_id = $_GET['almacen_id'] ?? null;
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$producto_id || !$almacen_id || !$empresa_id) {
    ob_end_clean();
    echo json_encode(['error' => 'Faltan parámetros', 'stock' => 0]);
    exit;
}

$inventario = get_data('inventario');
$stock = 0;

foreach ($inventario as $inv) {
    if ($inv['producto_id'] == $producto_id && 
        $inv['almacen_id'] == $almacen_id && 
        $inv['empresa_id'] == $empresa_id) {
        $stock = $inv['stock_actual'];
        break;
    }
}

ob_end_clean();
echo json_encode(['stock' => $stock]);
exit;
