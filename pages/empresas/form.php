<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/company_logos.php';

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
if ($id !== null && (!is_string($id) || !ctype_digit($id) || !$empresa)) {
    http_response_code(404);
    exit('Empresa no disponible.');
}
$is_edit = $empresa !== null;
$logo_url = $is_edit ? company_logo_url($empresa) : null;
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
        <form action="<?php echo url('actions/guardar_empresa.php'); ?>" method="POST" enctype="multipart/form-data">
            <?php echo form_context(); ?>
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
                    <input type="text" class="form-control" name="ruc" required pattern="[0-9]{11}" maxlength="11" inputmode="numeric" title="Ingresa 11 dígitos"
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['ruc']) : ''; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12"><label class="form-label">Nombre comercial</label><input type="text" class="form-control" name="nombre_comercial" value="<?php echo $is_edit ? htmlspecialchars($empresa['nombre_comercial'] ?? '') : ''; ?>"></div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['direccion']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['telefono']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" 
                           value="<?php echo $is_edit ? htmlspecialchars($empresa['correo']) : ''; ?>">
                </div>
            </div>

            <div class="company-logo-editor mb-4">
                <div class="company-logo-preview">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo actual de <?php echo htmlspecialchars($empresa['razon_social']); ?>">
                    <?php else: ?>
                        <div class="company-logo-placeholder"><i class="bi bi-image"></i><span>Sin logo</span></div>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <label class="form-label" for="companyLogo">Logo de empresa</label>
                    <input type="file" class="form-control" id="companyLogo" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                    <div class="form-text">PNG, JPG, JPEG o WEBP. Tamaño máximo: 2 MB.</div>
                    <?php if ($logo_url): ?>
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" value="1" name="quitar_logo" id="removeCompanyLogo"><label class="form-check-label" for="removeCompanyLogo">Quitar logo actual</label></div>
                    <?php endif; ?>
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
