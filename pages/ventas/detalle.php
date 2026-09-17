<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "pages/ventas/index.php");
    exit;
}

$ventas = get_data('ventas');
$clientes = get_data('clientes');
$productos = get_data('productos');

$venta = null;
foreach ($ventas as $v) {
    if ($v['id'] == $id && $v['empresa_id'] == $_SESSION['empresa_id']) {
        $venta = $v;
        break;
    }
}

if (!$venta) {
    echo "Venta no encontrada.";
    exit;
}

$getCliName = function($cid) use ($clientes) {
    foreach($clientes as $c) { if ($c['id'] == $cid) return $c['nombre']; }
    return 'Desconocido';
};

$getProdName = function($pid) use ($productos) {
    foreach($productos as $p) { if ($p['id'] == $pid) return $p['sku'] . ' - ' . $p['nombre']; }
    return 'Desconocido';
};
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Simulación Visual: Impacto de la Venta</h2>
    <a href="<?php echo url('pages/ventas/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a Ventas
    </a>
</div>

<!-- Paso 1: Venta Registrada -->
<div class="card shadow mb-4 border-left-warning">
    <div class="card-header py-3 bg-warning bg-opacity-75 text-dark">
        <h6 class="m-0 font-weight-bold"><i class="bi bi-1-circle"></i> VENTA REGISTRADA</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($getCliName($venta['cliente_id'])); ?></p>
                <p><strong>Documento:</strong> <?php echo htmlspecialchars($venta['tipo_documento'] . ' ' . $venta['serie'] . '-' . $venta['numero']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h4 class="text-success fw-bold">TOTAL INGRESO: <?php echo format_money($venta['total']); ?></h4>
                <p class="text-muted mb-0">Subtotal: <?php echo format_money($venta['subtotal']); ?> | IGV: <?php echo format_money($venta['igv']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Flujo Visual -->
<div class="text-center my-3">
    <i class="bi bi-arrow-down fs-1 text-secondary"></i>
</div>

<?php foreach($venta['impacto_simulacion'] as $impacto): ?>
    <div class="row mb-5">
        <div class="col-12 mb-2">
            <h5 class="fw-bold text-primary">Producto: <?php echo htmlspecialchars($getProdName($impacto['producto_id'])); ?></h5>
        </div>
        
        <!-- Paso 2: Salida Inventario -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-left-danger">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 font-weight-bold"><i class="bi bi-2-circle"></i> SALIDA DE INVENTARIO</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1">Stock anterior: <strong><?php echo $impacto['stock_anterior']; ?></strong></p>
                    <p class="mb-1 text-danger fw-bold">- <?php echo $impacto['cantidad_vendida']; ?> despachados</p>
                    <hr>
                    <h5 class="text-danger text-center">Nuevo Stock: <?php echo $impacto['nuevo_stock']; ?></h5>
                </div>
            </div>
        </div>
        
        <!-- Paso 3: Movimiento Kardex -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-left-info">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold"><i class="bi bi-3-circle"></i> MOVIMIENTO EN KARDEX</h6>
                </div>
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-spreadsheet fs-1 text-info mb-3 d-block"></i>
                    <p>Se insertó una fila en el Kardex indicando una <strong>SALIDA</strong> de <?php echo $impacto['cantidad_vendida']; ?> unidades.</p>
                </div>
            </div>
        </div>
        
        <!-- Paso 4: Costo de Ventas -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-left-primary bg-primary bg-opacity-10">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="bi bi-4-circle"></i> COSTO DE VENTAS (CPP)</h6>
                </div>
                <div class="card-body" style="font-size: 0.9rem;">
                    <div class="alert alert-light border shadow-sm text-center p-2 mb-3">
                        <small>La salida utiliza el CPP vigente sin alterarlo.</small>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Cant. Vendida:</span>
                        <span><?php echo $impacto['cantidad_vendida']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>× CPP Vigente:</span>
                        <span>S/ <?php echo number_format($impacto['cpp_vigente'], 2); ?></span>
                    </div>
                    <hr class="my-1">
                    <div class="text-center mt-3">
                        <small class="text-muted d-block mb-1">Costo de Ventas Reconocido:</small>
                        <h4 class="text-primary-emphasis fw-bold"><?php echo format_money($impacto['costo_venta_linea']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="row mt-4 mb-5">
    <div class="col-md-6 offset-md-3">
        <div class="card bg-dark text-white text-center shadow">
            <div class="card-body py-4">
                <h5 class="mb-3">RESUMEN FINANCIERO DE LA VENTA</h5>
                <div class="d-flex justify-content-around">
                    <div>
                        <div class="text-white-50 small">Ingreso Total</div>
                        <div class="fs-4 text-success"><?php echo format_money($venta['total']); ?></div>
                    </div>
                    <div>
                        <div class="text-white-50 small">Costo de Mercadería</div>
                        <div class="fs-4 text-danger"><?php echo format_money($venta['costo_ventas_total']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <a href="<?php echo url('pages/ventas/documento.php?id=' . $venta['id']); ?>" target="_blank" class="btn btn-secondary btn-lg me-2">
        <i class="bi bi-printer"></i> Ver Documento Impreso
    </a>
    <a href="<?php echo url('pages/inventario/index.php'); ?>" class="btn btn-primary btn-lg">
        Ir a Verificar Inventario <i class="bi bi-arrow-right"></i>
    </a>
</div>

<?php include '../../includes/footer.php'; ?>
