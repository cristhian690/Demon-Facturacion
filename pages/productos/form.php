<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$productos = get_data('productos');
$producto = null;

if ($id) {
    foreach ($productos as $p) {
        if ($p['id'] == $id) {
            $producto = $p;
            break;
        }
    }
}
$is_edit = $producto !== null;
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800"><?php echo $is_edit ? 'Editar Producto' : 'Nuevo Producto'; ?></h2>
    <a href="<?php echo url('pages/productos/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo url('actions/guardar_producto.php'); ?>" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">SKU / Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sku" required 
                           value="<?php echo $is_edit ? htmlspecialchars($producto['sku']) : ''; ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" required 
                           value="<?php echo $is_edit ? htmlspecialchars($producto['nombre']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="descripcion" 
                           value="<?php echo $is_edit ? htmlspecialchars($producto['descripcion'] ?? '') : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Categoría</label>
                    <input type="text" class="form-control" name="categoria" 
                           value="<?php echo $is_edit ? htmlspecialchars($producto['categoria'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input type="text" class="form-control" name="marca" 
                           value="<?php echo $is_edit ? htmlspecialchars($producto['marca'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unidad de Medida <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="unidad_medida" required placeholder="UN, KG, M"
                           value="<?php echo $is_edit ? htmlspecialchars($producto['unidad_medida'] ?? '') : 'UN'; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Mínimo</label>
                    <input type="number" class="form-control" name="stock_minimo" min="0"
                           value="<?php echo $is_edit ? htmlspecialchars($producto['stock_minimo'] ?? '0') : '0'; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" name="estado">
                        <option value="Activo" <?php echo ($is_edit && ($producto['estado'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo ($is_edit && ($producto['estado'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Producto
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
