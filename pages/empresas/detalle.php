<?php
require_once '../../config.php'; require_once '../../includes/helpers.php'; require_once '../../includes/companies.php'; require_once '../../includes/company_logos.php'; require_once '../../includes/management_ui.php';
try{$row=owned_record('empresas',$_GET['id']??'');}catch(Throwable $e){http_response_code(404);exit('Empresa no disponible.');}
$logo_url=company_logo_url($row);
include '../../includes/header.php';
?>
<div class="company-logo-detail mb-4">
    <?php if($logo_url): ?><img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo de <?php echo htmlspecialchars($row['razon_social']); ?>">
    <?php else: ?><div class="company-logo-placeholder"><i class="bi bi-image"></i><span>Sin logo</span></div><?php endif; ?>
    <div><small>Identidad visual</small><strong><?php echo htmlspecialchars(($row['nombre_comercial'] ?? '') ?: $row['razon_social']); ?></strong><span><?php echo $logo_url ? 'Logo registrado para esta empresa' : 'Esta empresa aún no tiene logo'; ?></span></div>
</div>
<?php
render_management_detail('empresas','empresa',$row,['Razón social'=>'razon_social','Nombre comercial'=>'nombre_comercial','RUC'=>'ruc','Dirección'=>'direccion','Teléfono'=>'telefono','Correo'=>'correo'],'razon_social',company_history_count($row['id']));
include '../../includes/footer.php';
