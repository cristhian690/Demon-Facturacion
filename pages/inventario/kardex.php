<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

require_once '../../includes/kardex.php';
$productos = get_data('productos');
$almacenes = get_data('almacenes');
$producto_id = is_string($_GET['producto_id'] ?? '') ? ($_GET['producto_id'] ?? '') : '';
$almacen_id = is_string($_GET['almacen_id'] ?? '') ? ($_GET['almacen_id'] ?? '') : '';
$desde = is_string($_GET['desde'] ?? '') ? ($_GET['desde'] ?? '') : '';
$hasta = is_string($_GET['hasta'] ?? '') ? ($_GET['hasta'] ?? '') : '';
$venta_id = is_string($_GET['venta_id'] ?? '') ? ($_GET['venta_id'] ?? '') : '';
$filter_error = '';
$movimientos = [];
$producto_seleccionado = null;
try {
    $movimientos = filter_kardex(get_data('kardex'), ['producto_id'=>$producto_id, 'almacen_id'=>$almacen_id, 'desde'=>$desde, 'hasta'=>$hasta, 'venta_id'=>$venta_id]);
    if ($producto_id) $producto_seleccionado = owned_record('productos', $producto_id);
} catch (InvalidArgumentException $e) { $filter_error = $e->getMessage(); }
$almacen_names = array_column($almacenes, 'nombre', 'id');
$producto_names = array_column($productos, 'nombre', 'id');
$kardexSummary = ['entrada_cantidad'=>0.0,'entrada_valor'=>0.0,'salida_cantidad'=>0.0,'salida_valor'=>0.0];
$movementDates = [];
foreach ($movimientos as $movement) {
    foreach ($kardexSummary as $field => $value) $kardexSummary[$field] += (float)($movement[$field] ?? 0);
    $dateKey = substr($movement['fecha'], 0, 10);
    if (!isset($movementDates[$dateKey])) $movementDates[$dateKey] = ['entrada'=>0.0,'salida'=>0.0];
    $movementDates[$dateKey]['entrada'] += (float)($movement['entrada_valor'] ?? 0);
    $movementDates[$dateKey]['salida'] += (float)($movement['salida_valor'] ?? 0);
}
$currentStock = 0.0; $currentValue = 0.0;
foreach (get_data('inventario') as $stockRow) {
    if ($producto_id !== '' && $stockRow['producto_id'] != $producto_id) continue;
    if ($almacen_id !== '' && $stockRow['almacen_id'] != $almacen_id) continue;
    $currentStock += (float)$stockRow['stock_actual'];
    $currentValue += (float)$stockRow['valor_inventario'];
}
?>
<?php include '../../includes/header.php'; ?>

<div class="page-heading d-flex justify-content-between align-items-end mb-4">
    <div><span class="page-eyebrow">Control de inventario</span><h1 class="h3 mb-1">Kardex valorizado</h1><p class="text-muted mb-0">Entradas por compras, salidas por despachos y saldo valorizado con CPP.</p></div>
    <span class="badge rounded-pill text-bg-primary px-3 py-2">Promedio ponderado</span>
</div>

<!-- Filtro de Búsqueda -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 font-weight-bold text-primary">Consulta de Producto</h6>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row align-items-center">
            <?php if ($venta_id): ?>
            <input type="hidden" name="venta_id" value="<?php echo htmlspecialchars($venta_id); ?>">
            <p>Movimientos de la venta #<?php echo htmlspecialchars($venta_id); ?>. <a href="<?php echo url('pages/inventario/kardex.php'); ?>">Ver todo el Kardex</a></p>
            <?php endif; ?>
            <div class="col-md-6 mb-2">
                <label class="form-label" for="filtroProducto">Producto</label>
                <select id="filtroProducto" name="producto_id" class="form-select">
                    <option value="">Todos los productos</option>
                    <?php foreach($productos as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($producto_id == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['sku'] . ' - ' . $p['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label" for="filtroAlmacen">Almacén</label>
                <select id="filtroAlmacen" name="almacen_id" class="form-select">
                    <option value="">Todos los almacenes</option>
                    <?php foreach ($almacenes as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo $almacen_id == $a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2"><label for="desde" class="form-label">Desde (incluido)</label><input id="desde" type="date" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>"></div>
            <div class="col-md-3 mb-2"><label for="hasta" class="form-label">Hasta (incluido)</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>"></div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?php echo url('pages/inventario/index.php'); ?>" class="btn btn-outline-secondary">
                    Volver a Stock
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($filter_error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($filter_error); ?></div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card summary-card summary-entry"><small>Entradas visibles</small><strong><?php echo quantity_display($kardexSummary['entrada_cantidad']); ?></strong><span><?php echo format_money($kardexSummary['entrada_valor']); ?></span></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card summary-card summary-exit"><small>Salidas visibles</small><strong><?php echo quantity_display($kardexSummary['salida_cantidad']); ?></strong><span><?php echo format_money($kardexSummary['salida_valor']); ?></span></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card summary-card summary-balance"><small>Stock actual</small><strong><?php echo quantity_display($currentStock); ?></strong><span>Según filtros de producto y almacén</span></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card summary-card summary-value"><small>Valor actual</small><strong><?php echo format_money($currentValue); ?></strong><span>Valorizado al CPP</span></div></div>
    </div>
    <div class="card chart-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><div><span class="chart-kicker">Flujo valorizado</span><h2 class="h6 mb-0">Entradas y salidas del periodo visible</h2></div><span class="text-muted small"><?php echo count($movimientos); ?> movimiento(s)</span></div>
        <div class="card-body"><div class="chart-wrap chart-wrap-kardex"><canvas id="kardexMovementChart" aria-label="Entradas y salidas valorizadas del Kardex"></canvas></div></div>
    </div>
    <script id="kardexChartData" type="application/json"><?php echo json_encode(['labels'=>array_map(fn($date)=>date('d/m', strtotime($date)), array_keys($movementDates)),'entries'=>array_column($movementDates,'entrada'),'exits'=>array_column($movementDates,'salida')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
    <div class="card shadow mb-4 border-top-info">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
            <h6 class="m-0 font-weight-bold text-info-emphasis">
                Kardex: <?php echo htmlspecialchars($producto_seleccionado['nombre'] ?? 'Todos los productos'); ?>
            </h6>
            <span class="text-muted small"><?php echo count($movimientos); ?> movimiento(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0 kardex-table" style="font-size: 0.85rem;">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th rowspan="2">Producto / Almacen</th><th rowspan="2" width="90">Fecha</th>
                            <th rowspan="2" width="120">Documento</th>
                            <th rowspan="2" width="100">Operación</th>
                            <th colspan="3" class="bg-success text-white bg-opacity-75">ENTRADAS</th>
                            <th colspan="3" class="bg-danger text-white bg-opacity-75">SALIDAS</th>
                            <th colspan="3" class="bg-primary text-white bg-opacity-75">SALDOS</th>
                        </tr>
                        <tr>
                            <!-- Entradas -->
                            <th width="60" class="bg-success bg-opacity-10">Cant.</th>
                            <th width="85" class="bg-success bg-opacity-10">C. Unit.</th>
                            <th width="90" class="bg-success bg-opacity-10">Total</th>
                            <!-- Salidas -->
                            <th width="60" class="bg-danger bg-opacity-10">Cant.</th>
                            <th width="85" class="bg-danger bg-opacity-10">C. Unit.</th>
                            <th width="90" class="bg-danger bg-opacity-10">Total</th>
                            <!-- Saldos -->
                            <th width="60" class="bg-primary bg-opacity-10 fw-bold">Cant.</th>
                            <th width="90" class="bg-primary text-warning-emphasis bg-opacity-10 fw-bold">CPP</th>
                            <th width="95" class="bg-primary bg-opacity-10 fw-bold">V. Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr><td colspan="13" class="text-center py-4">No hay movimientos registrados para este producto en la empresa actual.</td></tr>
                        <?php else: ?>
                            <?php foreach($movimientos as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($producto_names[$m['producto_id']] ?? 'Producto #' . $m['producto_id']) . ' / ' . ($almacen_names[$m['almacen_id']] ?? 'Almacen #' . $m['almacen_id'])); ?></td>
                                    <td class="text-center"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($m['documento']); ?></td>
                                    <td class="text-center">
                                        <?php if($m['tipo_operacion'] === 'COMPRA'): ?>
                                            <span class="badge bg-success bg-opacity-75 text-white w-100">COMPRA</span>
                                        <?php elseif($m['tipo_operacion'] === 'VENTA'): ?>
                                            <span class="badge bg-danger bg-opacity-75 text-white w-100">VENTA</span>
                                        <?php elseif($m['tipo_operacion'] === 'DEVOLUCION_VENTA'): ?>
                                            <span class="badge bg-info text-dark w-100">DEVOLUCIÓN</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary w-100"><?php echo htmlspecialchars($m['tipo_operacion']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Entradas -->
                                    <td class="text-center bg-success bg-opacity-10"><?php echo $m['entrada_cantidad'] > 0 ? $m['entrada_cantidad'] : ''; ?></td>
                                    <td class="text-end bg-success bg-opacity-10"><?php echo $m['entrada_cantidad'] > 0 ? number_format($m['entrada_costo'], 2) : ''; ?></td>
                                    <td class="text-end bg-success bg-opacity-10 fw-bold"><?php echo $m['entrada_cantidad'] > 0 ? number_format($m['entrada_valor'], 2) : ''; ?></td>
                                    
                                    <!-- Salidas -->
                                    <td class="text-center bg-danger bg-opacity-10"><?php echo $m['salida_cantidad'] > 0 ? $m['salida_cantidad'] : ''; ?></td>
                                    <td class="text-end bg-danger bg-opacity-10"><?php echo $m['salida_cantidad'] > 0 ? number_format($m['salida_costo'], 2) : ''; ?></td>
                                    <td class="text-end bg-danger bg-opacity-10 fw-bold text-danger"><?php echo $m['salida_cantidad'] > 0 ? number_format($m['salida_valor'], 2) : ''; ?></td>
                                    
                                    <!-- Saldos -->
                                    <td class="text-center bg-primary bg-opacity-10 fw-bold"><?php echo $m['saldo_cantidad']; ?></td>
                                    <td class="text-end bg-primary bg-opacity-10 fw-bold text-warning-emphasis"><?php echo number_format($m['cpp'], 2); ?></td>
                                    <td class="text-end bg-primary bg-opacity-10 fw-bold text-primary"><?php echo number_format($m['saldo_valor'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php $page_scripts = ['assets/js/kardex-chart.js']; include '../../includes/footer.php'; ?>
