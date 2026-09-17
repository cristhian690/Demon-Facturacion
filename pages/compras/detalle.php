<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "pages/compras/index.php");
    exit;
}

$compras = get_data('compras');
$proveedores = get_data('proveedores');
$productos = get_data('productos');

$compra = null;
foreach ($compras as $c) {
    if ($c['id'] == $id && $c['empresa_id'] == $_SESSION['empresa_id']) {
        $compra = $c;
        break;
    }
}

if (!$compra) {
    echo "Compra no encontrada.";
    exit;
}

$getProvName = function($pid) use ($proveedores) {
    foreach($proveedores as $p) { if ($p['id'] == $pid) return $p['nombre']; }
    return 'Desconocido';
};

$getProdName = function($pid) use ($productos) {
    foreach($productos as $p) { if ($p['id'] == $pid) return $p['sku'] . ' - ' . $p['nombre']; }
    return 'Desconocido';
};
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Simulación Visual: Impacto de la Compra</h2>
    <a href="<?php echo url('pages/compras/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver a Compras
    </a>
</div>

<!-- Paso 1: Compra Registrada -->
<div class="card shadow mb-4 border-left-success">
    <div class="card-header py-3 bg-success text-white">
        <h6 class="m-0 font-weight-bold"><i class="bi bi-1-circle"></i> COMPRA REGISTRADA</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Proveedor:</strong> <?php echo htmlspecialchars($getProvName($compra['proveedor_id'])); ?></p>
                <p><strong>Documento:</strong> <?php echo htmlspecialchars($compra['tipo_documento'] . ' ' . $compra['serie'] . '-' . $compra['numero']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($compra['fecha'])); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h4 class="text-success fw-bold">TOTAL: <?php echo format_money($compra['total']); ?></h4>
                <p class="text-muted mb-0">Subtotal: <?php echo format_money($compra['subtotal']); ?> | IGV: <?php echo format_money($compra['igv']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Flujo Visual -->
<div class="text-center my-3">
    <i class="bi bi-arrow-down fs-1 text-secondary"></i>
</div>

<?php foreach($compra['impacto_simulacion'] as $impacto): ?>
    <div class="row mb-5">
        <div class="col-12 mb-2">
            <h5 class="fw-bold text-primary">Producto: <?php echo htmlspecialchars($getProdName($impacto['producto_id'])); ?></h5>
        </div>
        
        <!-- Paso 2: Entrada Inventario -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-left-primary">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="bi bi-2-circle"></i> ENTRADA DE INVENTARIO</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1">Stock anterior: <strong><?php echo $impacto['stock_anterior']; ?></strong></p>
                    <p class="mb-1 text-success fw-bold">+ <?php echo $impacto['cantidad_comprada']; ?> comprados</p>
                    <hr>
                    <h5 class="text-primary text-center">Nuevo Stock: <?php echo $impacto['nuevo_stock']; ?></h5>
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
                    <p>Se insertó una fila en el Kardex Valorizado indicando una <strong>ENTRADA</strong> de <?php echo $impacto['cantidad_comprada']; ?> unidades a <?php echo format_money($impacto['costo_compra']); ?>.</p>
                </div>
            </div>
        </div>
        
        <!-- Paso 4: Actualización CPP -->
        <div class="col-md-4">
            <div class="card shadow h-100 border-left-warning bg-warning bg-opacity-10">
                <div class="card-header py-3 bg-warning text-dark">
                    <h6 class="m-0 font-weight-bold"><i class="bi bi-4-circle"></i> ACTUALIZACIÓN DEL CPP</h6>
                </div>
                <div class="card-body" style="font-size: 0.9rem;">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Valor Anterior:</span>
                        <span>S/ <?php echo number_format($impacto['stock_anterior'] * $impacto['cpp_anterior'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-success fw-bold mb-1">
                        <span>Valor Entrada:</span>
                        <span>+ S/ <?php echo number_format($impacto['cantidad_comprada'] * $impacto['costo_compra'], 2); ?></span>
                    </div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Nuevo Valor Total:</span>
                        <span>S/ <?php echo number_format(($impacto['stock_anterior'] * $impacto['cpp_anterior']) + ($impacto['cantidad_comprada'] * $impacto['costo_compra']), 2); ?></span>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted d-block mb-1">Cálculo: Nuevo Valor / Nuevo Stock</small>
                        <h4 class="text-warning-emphasis fw-bold">CPP: <?php echo format_money($impacto['nuevo_cpp']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="text-center mt-4">
    <a href="<?php echo url('pages/inventario/index.php'); ?>" class="btn btn-primary btn-lg">
        Ir a Verificar Inventario <i class="bi bi-arrow-right"></i>
    </a>
</div>

<?php include '../../includes/footer.php'; ?>
