<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$almacenes = get_data('almacenes');
$almacen = null;

if ($id) {
    foreach ($almacenes as $a) {
        if ($a['id'] == $id) {
            $almacen = $a;
            break;
        }
    }
}
$is_edit = $almacen !== null;
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800"><?php echo $is_edit ? 'Editar Almacén' : 'Nuevo Almacén'; ?></h2>
    <a href="<?php echo url('pages/almacenes/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo url('actions/guardar_almacen.php'); ?>" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $almacen['id']; ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-5">
                    <label class="form-label">Nombre del Almacén <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" required 
                           value="<?php echo $is_edit ? htmlspecialchars($almacen['nombre']) : ''; ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Ubicación</label>
                    <input type="text" class="form-control" name="ubicacion" 
                           value="<?php echo $is_edit ? htmlspecialchars($almacen['ubicacion'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select class="form-select" name="estado">
                        <option value="Activo" <?php echo ($is_edit && ($almacen['estado'] ?? '') == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo ($is_edit && ($almacen['estado'] ?? '') == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Almacén
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
