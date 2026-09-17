<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$empresas = get_data('empresas');
$empresa = null;

if ($id) {
    foreach ($empresas as $e) {
        if ($e['id'] == $id) {
            $empresa = $e;
            break;
        }
    }
}
$is_edit = $empresa !== null;
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800"><?php echo $is_edit ? 'Editar Empresa' : 'Nueva Empresa'; ?></h2>
    <a href="<?php echo url('pages/empresas/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo url('actions/guardar_empresa.php'); ?>" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $empresa['id']; ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="razon_social" required 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['razon_social']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">RUC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="ruc" required 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['ruc']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['direccion']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['telefono']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['correo']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Logo (Nombre archivo simulado)</label>
                    <input type="text" class="form-control" name="logo" placeholder="logo.png"
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['logo']) : ''; ?>">
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Empresa
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
