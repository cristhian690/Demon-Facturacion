<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$proveedores = get_data('proveedores');
$proveedor = null;

if ($id) {
    foreach ($proveedores as $p) {
        if ($p['id'] == $id) {
            $proveedor = $p;
            break;
        }
    }
}
$is_edit = $proveedor !== null;
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800"><?php echo $is_edit ? 'Editar Proveedor' : 'Nuevo Proveedor'; ?></h2>
    <a href="<?php echo url('pages/proveedores/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo url('actions/guardar_proveedor.php'); ?>" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label">Tipo Doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_documento" required>
                        <option value="RUC" <?php echo ($is_edit && ($proveedor['tipo_documento'] ?? '') == 'RUC') ? 'selected' : ''; ?>>RUC</option>
                        <option value="DNI" <?php echo ($is_edit && ($proveedor['tipo_documento'] ?? '') == 'DNI') ? 'selected' : ''; ?>>DNI</option>
                        <option value="CE" <?php echo ($is_edit && ($proveedor['tipo_documento'] ?? '') == 'CE') ? 'selected' : ''; ?>>CE</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nro. Documento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="numero_documento" required 
                           value="<?php echo $is_edit ? htmlspecialchars($proveedor['numero_documento'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Razón Social / Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" required 
                           value="<?php echo $is_edit ? htmlspecialchars($proveedor['nombre']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?php echo $is_edit ? htmlspecialchars($proveedor['direccion'] ?? '') : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" 
                           value="<?php echo $is_edit ? htmlspecialchars($proveedor['telefono'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" 
                           value="<?php echo $is_edit ? htmlspecialchars($proveedor['correo'] ?? '') : ''; ?>">
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
