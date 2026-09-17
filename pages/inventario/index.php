<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$inventario = get_data('inventario');
$productos = get_data('productos');
$almacenes = get_data('almacenes');

// Helpers
$getProd = function($id) use ($productos) {
    foreach($productos as $p) { if ($p['id'] == $id) return $p; }
    return null;
};
$getAlmName = function($id) use ($almacenes) {
    foreach($almacenes as $a) { if ($a['id'] == $id) return $a['nombre']; }
    return 'Desconocido';
};

// Filtrar por empresa activa
$inventario_actual = array_filter($inventario, function($inv) {
    return $inv['empresa_id'] == $_SESSION['empresa_id'];
});
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Inventario Actual</h2>
</div>

<div class="card shadow mb-4 border-top-primary">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 font-weight-bold text-primary">Stock y Costos Promedio</h6>
        <span class="badge bg-primary text-white fs-6">Validación Fase 3</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Almacén</th>
                        <th class="text-center">Stock Actual</th>
                        <th class="text-end">Costo Promedio (CPP)</th>
                        <th class="text-end">Valor Total</th>
                        <th width="100" class="text-center">Kardex</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventario_actual)): ?>
                        <tr><td colspan="5" class="text-center">No hay productos en inventario</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventario_actual as $inv): 
                            $prod = $getProd($inv['producto_id']);
                            if(!$prod) continue;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($prod['sku']); ?></strong> - 
                                    <?php echo htmlspecialchars($prod['nombre']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($getAlmName($inv['almacen_id'])); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo ($inv['stock_actual'] <= ($prod['stock_minimo'] ?? 0)) ? 'danger' : 'success'; ?> fs-6">
                                        <?php echo $inv['stock_actual']; ?> <?php echo htmlspecialchars($prod['unidad_medida']); ?>
                                    </span>
                                </td>
                                <td class="text-end text-warning-emphasis fw-bold">
                                    <?php echo format_money($inv['cpp']); ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?php echo format_money($inv['valor_inventario']); ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo url('pages/inventario/kardex.php?producto_id=' . $prod['id']); ?>" class="btn btn-sm btn-outline-info" title="Ver Movimientos">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
