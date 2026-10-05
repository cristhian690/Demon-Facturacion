<?php
require_once '../../config.php'; require_once '../../includes/helpers.php';
$active=get_empresa_activa(); $company=null; foreach(get_data('empresas') as $row)if((string)$row['id']===(string)$active['id']){$company=$row;break;}
include '../../includes/header.php';
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><span class="page-eyebrow">SUNAT / Preparación futura</span><h1 class="h3 mb-1">Configuración SUNAT</h1><p class="text-muted mb-0">Parámetros conceptuales separados para la empresa activa.</p></div><span class="future-label">Próximamente</span></div>
<div class="sunat-security-note mb-4"><i class="bi bi-shield-lock"></i><span><strong>Configuración deshabilitada.</strong> En la versión final las credenciales y certificados se almacenarán de forma segura.</span></div>
<section class="card mb-4"><div class="card-header"><div><span class="chart-kicker">Empresa activa</span><h2 class="h6 mb-0"><?php echo htmlspecialchars($company['razon_social']??$active['nombre']); ?></h2></div><span class="active-company"><i class="bi bi-building"></i>Configuración independiente</span></div><div class="card-body">
 <div class="sunat-config-grid">
  <label><span>Empresa</span><input class="form-control" disabled value="<?php echo htmlspecialchars($company['razon_social']??$active['nombre']); ?>"></label>
  <label><span>RUC</span><input class="form-control" disabled value="<?php echo htmlspecialchars($company['ruc']??'No registrado'); ?>"></label>
  <label><span>Razón social</span><input class="form-control" disabled value="<?php echo htmlspecialchars($company['razon_social']??'No registrada'); ?>"></label>
  <label><span>Usuario SOL</span><input class="form-control" disabled placeholder="Se configurará de forma segura"></label>
  <label><span>Certificado digital</span><input class="form-control" disabled placeholder="No se aceptan certificados en el prototipo"></label>
  <label><span>Vencimiento del certificado</span><input class="form-control" disabled placeholder="dd/mm/aaaa"></label>
  <label><span>Entorno</span><select class="form-select" disabled><option>Pruebas</option><option>Producción</option></select></label>
 </div>
</div></section>
<section class="card mb-4"><div class="card-header"><div><span class="chart-kicker">Aislamiento multiempresa</span><h2 class="h6 mb-0">Una configuración por empresa</h2></div></div><div class="card-body"><div class="sunat-company-scope"><?php foreach([['123','RUC'],['collection','Series'],['key','Certificado'],['person-lock','Credenciales'],['files','Documentos electrónicos']] as $item): ?><span><i class="bi bi-<?php echo $item[0]; ?>"></i><?php echo $item[1]; ?></span><?php endforeach; ?></div><p class="small text-muted mt-3 mb-0">Estos elementos no se compartirán entre empresas. En esta etapa no se guarda ninguna configuración SUNAT.</p></div></section>
<a class="btn btn-outline-primary" href="<?php echo url('pages/sunat/index.php'); ?>">Volver al módulo SUNAT</a>
<?php include '../../includes/footer.php'; ?>
