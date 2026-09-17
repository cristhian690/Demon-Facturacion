<?php
require_once '../config.php';
require_once '../includes/helpers.php';

$empresa_id = $_SESSION['empresa_id'];
$mes_actual = date('Y-m');

// Obtener datos
$ventas = get_data('ventas');
$compras = get_data('compras');
$inventario = get_data('inventario');
$productos = get_data('productos');

// 1. Cálculos de KPIs
$total_ingresos_mes = 0;
$costo_ventas_mes = 0;
$total_compras_mes = 0;

$operaciones_recientes = [];

// Procesar Ventas
foreach ($ventas as $v) {
    if ($v['empresa_id'] == $empresa_id) {
        // Para operaciones recientes
        $operaciones_recientes[] = [
            'fecha' => $v['fecha'],
            'tipo' => 'VENTA',
            'doc' => $v['tipo_documento'] . ' ' . $v['serie'] . '-' . $v['numero'],
            'total' => $v['total'],
            'id' => $v['id'],
            'url' => url('pages/ventas/detalle.php?id=' . $v['id'])
        ];
        
        // Filtro mensual
        if (substr($v['fecha'], 0, 7) === $mes_actual) {
            $total_ingresos_mes += $v['total'];
            $costo_ventas_mes += $v['costo_ventas_total'] ?? 0;
        }
    }
}

// Procesar Compras
foreach ($compras as $c) {
    if ($c['empresa_id'] == $empresa_id) {
        // Para operaciones recientes
        $operaciones_recientes[] = [
            'fecha' => $c['fecha'],
            'tipo' => 'COMPRA',
            'doc' => $c['tipo_documento'] . ' ' . $c['serie'] . '-' . $c['numero'],
            'total' => $c['total'],
            'id' => $c['id'],
            'url' => url('pages/compras/detalle.php?id=' . $c['id'])
        ];
        
        // Filtro mensual
        if (substr($c['fecha'], 0, 7) === $mes_actual) {
            $total_compras_mes += $c['total'];
        }
    }
}

// Ordenar operaciones por fecha desc
usort($operaciones_recientes, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});
$operaciones_recientes = array_slice($operaciones_recientes, 0, 5); // Últimas 5

$margen_bruto_mes = $total_ingresos_mes - $costo_ventas_mes;

// 2. Alertas de Stock
$productos_criticos = [];
$valor_inventario_total = 0;

// Mapeo rápido de stock mínimo y nombre por producto
$prod_info = [];
foreach($productos as $p) {
    $prod_info[$p['id']] = [
        'nombre' => $p['sku'] . ' - ' . $p['nombre'],
        'minimo' => $p['stock_minimo'] ?? 0
    ];
}

foreach ($inventario as $inv) {
    if ($inv['empresa_id'] == $empresa_id) {
        $valor_inventario_total += $inv['valor_inventario'];
        $pid = $inv['producto_id'];
        if (isset($prod_info[$pid])) {
            if ($inv['stock_actual'] <= $prod_info[$pid]['minimo']) {
                $productos_criticos[] = [
                    'nombre' => $prod_info[$pid]['nombre'],
                    'stock' => $inv['stock_actual'],
                    'minimo' => $prod_info[$pid]['minimo'],
                    'producto_id' => $pid
                ];
            }
        }
    }
}
$cantidad_alertas = count($productos_criticos);

?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Dashboard</h2>
    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars(get_empresa_activa()['nombre']); ?></span>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 card-stats">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Ingresos (Mes)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_money($total_ingresos_mes); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-2 text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 card-stats">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Margen Bruto (Mes)</div>
                        <div class="h5 mb-0 font-weight-bold <?php echo $margen_bruto_mes < 0 ? 'text-danger' : 'text-gray-800'; ?>">
                            <?php echo format_money($margen_bruto_mes); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up-arrow fs-2 text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 card-stats">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Compras (Mes)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_money($total_compras_mes); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cart fs-2 text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2 card-stats">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Alertas Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cantidad_alertas; ?> Productos</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-2 text-secondary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos y Tablas -->
<div class="row">
    <!-- Operaciones Recientes -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow mb-4 h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Últimas Operaciones</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Documento</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($operaciones_recientes)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No hay operaciones recientes</td></tr>
                            <?php else: ?>
                                <?php foreach($operaciones_recientes as $op): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($op['fecha'])); ?></td>
                                    <td>
                                        <?php if($op['tipo'] == 'VENTA'): ?>
                                            <span class="badge bg-success bg-opacity-75">VENTA</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">COMPRA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($op['doc']); ?></td>
                                    <td class="text-end fw-bold <?php echo $op['tipo'] == 'VENTA' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo format_money($op['total']); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo $op['url']; ?>" class="btn btn-sm btn-outline-secondary" title="Ver Detalle">
                                            <i class="bi bi-arrow-right-short"></i>
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
    </div>

    <!-- Alertas de Stock -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow mb-4 h-100">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-danger">Alertas de Stock Crítico</h6>
            </div>
            <div class="card-body p-0">
                <?php if(empty($productos_criticos)): ?>
                    <div class="p-4 text-center text-success">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        Todos los productos tienen stock por encima del mínimo.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($productos_criticos as $pc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($pc['nombre']); ?><br>
                                <small class="text-muted">Mínimo: <?php echo $pc['minimo']; ?></small>
                            </div>
                            <span class="badge bg-danger rounded-pill fs-6 px-3 py-2">
                                <?php echo $pc['stock']; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
