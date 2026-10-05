<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
$empresa=get_empresa_activa();
include '../../includes/header.php';
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
 <div><span class="page-eyebrow">Preparación futura</span><h1 class="h3 mb-1">SUNAT / Facturación electrónica</h1><p class="text-muted mb-0">Vista conceptual del ciclo tributario electrónico para <?php echo htmlspecialchars($empresa['nombre']); ?>.</p></div>
 <span class="sunat-development"><i class="bi bi-tools"></i>En desarrollo</span>
</div>
<section class="sunat-hero mb-4">
 <div><span class="sunat-hero-icon"><i class="bi bi-cloud"></i></span><div><small>Módulo demostrativo</small><h2>Integración tributaria preparada para una etapa futura</h2><p>Esta sección representa la futura integración de comprobantes electrónicos con SUNAT. En este prototipo no se realizan envíos reales.</p></div></div>
 <span class="sunat-no-live"><i class="bi bi-shield-lock"></i>Sin conexión externa</span>
</section>
<section class="card sunat-flow mb-4">
 <div class="card-header"><div><span class="chart-kicker">Ciclo futuro</span><h2 class="h6 mb-0">Flujo de facturación electrónica</h2></div></div>
 <div class="card-body"><div class="sunat-flow-line">
 <?php foreach([['receipt','Comprobante','Generado en Ventas'],['filetype-xml','Generar XML','Estructura tributaria'],['pen','Firmar XML','Certificado digital'],['send','Enviar a SUNAT','Servicio externo'],['inbox','Recibir CDR','Respuesta SUNAT'],['arrow-repeat','Actualizar estado','Trazabilidad']] as $i=>$step): ?>
  <?php if($i): ?><i class="bi bi-arrow-right sunat-flow-arrow"></i><?php endif; ?><div class="sunat-flow-step"><i class="bi bi-<?php echo $step[0]; ?>"></i><strong><?php echo $step[1]; ?></strong><small><?php echo $step[2]; ?></small></div>
 <?php endforeach; ?>
 </div></div>
</section>
<div class="sunat-module-grid mb-4">
 <?php foreach([
  ['documentos.php','files','Documentos electrónicos','Comprobantes existentes y su futuro estado de envío','En desarrollo'],
  ['nota_credito.php','file-earmark-minus','Notas de crédito','Anulación, devolución, descuento y corrección','Próximamente'],
  ['nota_debito.php','file-earmark-plus','Notas de débito','Ajustes que incrementen el importe','Próximamente'],
  ['estado.php','broadcast','Estado SUNAT','Consulta conceptual por comprobante','En desarrollo'],
  ['#xml','filetype-xml','XML','Representación electrónica del comprobante','Próximamente'],
  ['#cdr','inbox','CDR','Respuesta del procesamiento electrónico','Próximamente'],
  ['configuracion.php','sliders','Configuración SUNAT','Parámetros separados para cada empresa','Próximamente']
 ] as $item): ?><a class="sunat-module-card" href="<?php echo str_starts_with($item[0],'#')?$item[0]:url('pages/sunat/'.$item[0]); ?>"><i class="bi bi-<?php echo $item[1]; ?>"></i><span><strong><?php echo $item[2]; ?></strong><small><?php echo $item[3]; ?></small></span><em><?php echo $item[4]; ?></em></a><?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
 <div class="col-lg-6"><section class="card h-100" id="xml"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-filetype-xml"></i><span>XML</span></div><span class="future-label">Próximamente</span></div><div class="card-body"><p class="mb-2">El XML representa electrónicamente la información del comprobante.</p><p class="small text-muted mb-0">Este prototipo no genera, firma ni almacena archivos XML.</p></div></section></div>
 <div class="col-lg-6"><section class="card h-100" id="cdr"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-inbox"></i><span>CDR</span></div><span class="future-label">Próximamente</span></div><div class="card-body"><p class="mb-2">El CDR contiene la respuesta del procesamiento del comprobante electrónico.</p><p class="small text-muted mb-0">No se crean respuestas ficticias ni archivos descargables.</p></div></section></div>
</div>
<section class="card sunat-separation mb-4"><div class="card-header"><div><span class="chart-kicker">Procesos relacionados</span><h2 class="h6 mb-0">SUNAT e inventario conservan responsabilidades separadas</h2></div></div><div class="card-body">
 <p>El movimiento de inventario y la validación tributaria son procesos relacionados, pero independientes.</p>
 <div class="sunat-branch"><strong>Venta</strong><div><span><i class="bi bi-boxes"></i>Inventario <b>→</b> Kardex</span><span><i class="bi bi-receipt"></i>Comprobante <b>→</b> SUNAT</span></div></div>
 <small>Un estado visual de SUNAT no calcula stock, no modifica inventario y no recalcula el Kardex.</small>
</div></section>
<?php include '../../includes/footer.php'; ?>

