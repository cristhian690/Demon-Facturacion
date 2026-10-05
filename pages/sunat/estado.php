<?php require_once '../../config.php'; require_once '../../includes/helpers.php'; $showFiles=false; include '../../includes/header.php'; ?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><span class="page-eyebrow">SUNAT / Seguimiento futuro</span><h1 class="h3 mb-1">Estado SUNAT</h1><p class="text-muted mb-0">Vista conceptual del seguimiento electrónico por comprobante.</p></div><span class="sunat-development"><i class="bi bi-tools"></i>En desarrollo</span></div>
<div class="sunat-prototype-note mb-4"><i class="bi bi-cloud-slash"></i><span><strong>Sin consulta externa.</strong> “No enviado” indica que este prototipo no transmite comprobantes ni recibe respuestas.</span></div>
<section class="card mb-4"><div class="card-header"><div><span class="chart-kicker">Empresa activa</span><h2 class="h6 mb-0">Comprobantes y estado conceptual</h2></div></div><?php include '../../includes/sunat_documents_table.php'; ?></section>
<a class="btn btn-outline-primary" href="<?php echo url('pages/sunat/index.php'); ?>">Volver al módulo SUNAT</a>
<?php include '../../includes/footer.php'; ?>
