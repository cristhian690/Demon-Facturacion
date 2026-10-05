<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/finance.php';
require_once '../includes/dispatches.php';

$empresa = get_empresa_activa();
$ventas = get_data('ventas'); $compras = get_data('compras');
$inventario = get_data('inventario'); $productos = get_data('productos');
$almacenes = get_data('almacenes'); $pagos = get_data('pagos');
$ventas_vigentes = array_values(array_filter($ventas, fn($v) => ($v['estado_documento'] ?? 'Vigente') !== 'Anulada'));
$fecha_valida = function ($fecha) { $d = is_string($fecha) ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha) : false; return $d && $d->format('Y-m-d') === $fecha; };
$desde = $_GET['desde'] ?? (new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$rango_valido = $fecha_valida($desde) && $fecha_valida($hasta) && $desde <= $hasta;
if ($rango_valido) { $diferencia=(new DateTimeImmutable($desde))->diff(new DateTimeImmutable($hasta)); $rango_valido=($diferencia->y*12+$diferencia->m)<=60; }
if (!$rango_valido) { $desde=(new DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-d'); $hasta=date('Y-m-d'); }
$en_periodo = fn($fila) => substr($fila['fecha'] ?? '',0,10) >= $desde && substr($fila['fecha'] ?? '',0,10) <= $hasta;
$ventas_periodo = array_values(array_filter($ventas_vigentes, $en_periodo));
$compras_periodo = array_values(array_filter($compras, $en_periodo));
$pagos_periodo = array_values(array_filter($pagos, $en_periodo));
$total_ventas_periodo = array_sum(array_column($ventas_periodo, 'total'));
$total_compras_periodo = array_sum(array_column($compras_periodo, 'total'));
$costo_ventas_periodo = array_sum(array_map(fn($v) => (float)($v['costo_ventas_total'] ?? 0) - (float)($v['costo_devoluciones_total'] ?? 0), $ventas_periodo));
$margen_bruto_periodo = $total_ventas_periodo - $costo_ventas_periodo;
$cobros_periodo = array_sum(array_column($pagos_periodo, 'importe'));
$saldo_por_cobrar = array_sum(array_map(fn($v) => sale_financials($v)['saldo'], $ventas_vigentes));
$entregas_pendientes = count(array_filter($ventas_vigentes, fn($v) => dispatch_status($v) !== 'Entregada'));

$producto_por_id = []; foreach ($productos as $p) $producto_por_id[$p['id']] = $p;
$almacen_por_id = array_column($almacenes, 'nombre', 'id');
$valor_inventario = 0; $stock_critico = []; $valor_por_almacen = [];
foreach ($inventario as $fila) {
    $valor = (float)($fila['valor_inventario'] ?? 0); $valor_inventario += $valor;
    $nombre_almacen = $almacen_por_id[$fila['almacen_id']] ?? ('Almacén #' . $fila['almacen_id']);
    $valor_por_almacen[$nombre_almacen] = ($valor_por_almacen[$nombre_almacen] ?? 0) + $valor;
    $producto = $producto_por_id[$fila['producto_id']] ?? null;
    if (!$producto || ($producto['estado'] ?? 'Activo') !== 'Activo') continue;
    $minimo = (float)($producto['stock_minimo'] ?? 0);
    if ((float)$fila['stock_actual'] <= $minimo) $stock_critico[] = ['producto_id'=>$producto['id'], 'nombre'=>($producto['sku'] ?? '').' · '.$producto['nombre'], 'almacen'=>$nombre_almacen, 'stock'=>(float)$fila['stock_actual'], 'minimo'=>$minimo, 'unidad'=>$producto['unidad_medida'] ?? 'UN'];
}
usort($stock_critico, fn($a,$b) => ($a['stock'] <=> $b['stock']) ?: strcmp($a['nombre'],$b['nombre']));

$operaciones = [];
foreach (array_filter($ventas, $en_periodo) as $v) $operaciones[] = ['fecha'=>$v['fecha'], 'tipo'=>'Venta', 'documento'=>$v['tipo_documento'].' '.$v['serie'].'-'.$v['numero'], 'total'=>(float)$v['total'], 'estado'=>$v['estado_documento'] ?? 'Vigente', 'url'=>url('pages/ventas/detalle.php?id='.$v['id'])];
foreach ($compras_periodo as $c) $operaciones[] = ['fecha'=>$c['fecha'], 'tipo'=>'Compra', 'documento'=>$c['tipo_documento'].' '.$c['serie'].'-'.$c['numero'], 'total'=>(float)$c['total'], 'estado'=>'Registrada', 'url'=>url('pages/compras/detalle.php?id='.$c['id'])];
usort($operaciones, fn($a,$b) => strcmp($b['fecha'],$a['fecha'])); $operaciones = array_slice($operaciones,0,6);

$meses = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic']; $serie = [];
$inicio_grafico = new DateTimeImmutable(substr($desde,0,7).'-01'); $fin_grafico = new DateTimeImmutable(substr($hasta,0,7).'-01');
while ($inicio_grafico <= $fin_grafico) { $serie[$inicio_grafico->format('Y-m')]=['label'=>$meses[(int)$inicio_grafico->format('n')].' '.$inicio_grafico->format('y'),'ventas'=>0.0,'compras'=>0.0]; $inicio_grafico=$inicio_grafico->modify('+1 month'); }
foreach ($ventas_periodo as $v) { $k=substr($v['fecha'],0,7); if(isset($serie[$k])) $serie[$k]['ventas']+=(float)$v['total']; }
foreach ($compras_periodo as $c) { $k=substr($c['fecha'],0,7); if(isset($serie[$k])) $serie[$k]['compras']+=(float)$c['total']; }
include '../includes/header.php';
?>
<section class="dashboard-hero mb-4">
 <div><span class="dashboard-kicker"><i class="bi bi-grid-1x2-fill"></i> Resumen operativo</span><h1>Hola, <?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Administrador'); ?></h1><p>Resultados de <strong><?php echo htmlspecialchars($empresa['nombre']); ?></strong> del <?php echo date('d/m/Y',strtotime($desde)); ?> al <?php echo date('d/m/Y',strtotime($hasta)); ?>.</p></div>
 <div class="dashboard-actions"><a class="btn btn-outline-light" href="<?php echo url('pages/compras/nueva.php'); ?>"><i class="bi bi-cart-plus"></i>Nueva compra</a><a class="btn btn-warning" href="<?php echo url('pages/ventas/nueva.php'); ?>"><i class="bi bi-receipt-cutoff"></i>Nuevo comprobante</a></div>
</section>
<div class="dashboard-metrics mb-4">
<?php foreach ([['Ventas del periodo',$total_ventas_periodo,'bi-graph-up-arrow','primary','pages/ventas/index.php',count($ventas_periodo).' comprobantes vigentes'],['Margen del periodo',$margen_bruto_periodo,'bi-pie-chart-fill',$margen_bruto_periodo<0?'danger':'success','pages/reportes/index.php','Ventas menos costo despachado'],['Compras del periodo',$total_compras_periodo,'bi-bag-check-fill','info','pages/compras/index.php',count($compras_periodo).' compras registradas'],['Inventario actual',$valor_inventario,'bi-boxes','violet','pages/inventario/index.php',count($inventario).' existencias por almacén']] as [$titulo,$valor,$icono,$tono,$ruta,$detalle]): ?>
 <a class="dashboard-metric metric-<?php echo $tono; ?>" href="<?php echo url($ruta); ?>"><span class="dashboard-metric-icon"><i class="bi <?php echo $icono; ?>"></i></span><span class="dashboard-metric-copy"><small><?php echo $titulo; ?></small><strong><?php echo format_money($valor); ?></strong><span><?php echo $detalle; ?></span></span><i class="bi bi-arrow-up-right dashboard-metric-arrow"></i></a>
<?php endforeach; ?>
</div>
<div class="dashboard-status mb-4">
 <a href="<?php echo url('pages/cuentas_cobrar/index.php'); ?>"><span class="status-icon status-success"><i class="bi bi-wallet2"></i></span><span><small>Cobrado en el periodo</small><strong><?php echo format_money($cobros_periodo); ?></strong></span></a>
 <a href="<?php echo url('pages/cuentas_cobrar/index.php'); ?>"><span class="status-icon status-primary"><i class="bi bi-cash-coin"></i></span><span><small>Saldo por cobrar</small><strong><?php echo format_money($saldo_por_cobrar); ?></strong></span></a>
 <a href="<?php echo url('pages/ventas/index.php?entrega=Pendiente'); ?>"><span class="status-icon status-warning"><i class="bi bi-truck"></i></span><span><small>Entregas pendientes</small><strong><?php echo $entregas_pendientes; ?></strong></span></a>
 <a href="<?php echo url('pages/inventario/index.php'); ?>"><span class="status-icon status-danger"><i class="bi bi-exclamation-triangle"></i></span><span><small>Alertas de stock</small><strong><?php echo count($stock_critico); ?></strong></span></a>
</div>
<form class="card dashboard-filter mb-4" method="get">
 <div class="dashboard-filter-title"><span><i class="bi bi-funnel"></i></span><div><strong>Filtrar gráficos y resultados</strong><small>El periodo se aplica a ventas, compras, margen, cobros y actividad.</small></div></div>
 <div class="dashboard-filter-fields"><label><span>Desde</span><input class="form-control" type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>" required></label><label><span>Hasta</span><input class="form-control" type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>" required></label><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i>Aplicar</button><a class="btn btn-light" href="<?php echo url('pages/dashboard.php'); ?>">Últimos 6 meses</a></div>
</form>
<div class="row g-4 mb-4">
 <div class="col-xl-8"><section class="card dashboard-panel h-100"><header class="dashboard-panel-header"><div><span class="chart-kicker">Evolución semestral</span><h2>Ventas y compras</h2><p>Importes totales registrados por mes</p></div><a href="<?php echo url('pages/reportes/index.php'); ?>">Abrir reportes <i class="bi bi-arrow-right"></i></a></header><div class="card-body"><div class="chart-wrap chart-wrap-lg"><canvas id="salesPurchasesChart"></canvas></div></div></section></div>
 <div class="col-xl-4"><section class="card dashboard-panel h-100"><header class="dashboard-panel-header"><div><span class="chart-kicker">Inventario actual</span><h2>Valor por almacén</h2><p>Distribución del saldo valorizado</p></div><a href="<?php echo url('pages/inventario/kardex.php'); ?>">Ver Kardex <i class="bi bi-arrow-right"></i></a></header><div class="card-body"><div class="chart-wrap"><canvas id="warehouseValueChart"></canvas></div><div class="chart-total"><span>Valor total</span><strong><?php echo format_money($valor_inventario); ?></strong></div></div></section></div>
</div>
<div class="row g-4">
 <div class="col-xl-7"><section class="card dashboard-panel h-100"><header class="dashboard-panel-header"><div><span class="chart-kicker">Actividad reciente</span><h2>Últimas operaciones</h2></div><a href="<?php echo url('pages/historial/index.php'); ?>">Ver historial <i class="bi bi-arrow-right"></i></a></header><div class="table-responsive"><table class="table dashboard-table align-middle"><thead><tr><th>Fecha</th><th>Movimiento</th><th>Documento</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
 <?php if(!$operaciones): ?><tr><td colspan="5" class="empty-state"><i class="bi bi-inbox"></i><strong>Aún no hay operaciones</strong><span>Las compras y ventas aparecerán aquí.</span></td></tr><?php endif; ?>
 <?php foreach($operaciones as $op): ?><tr class="<?php echo $op['estado']==='Anulada'?'opacity-50':''; ?>"><td><span class="operation-date"><?php echo date('d',strtotime($op['fecha'])); ?></span><small><?php echo date('m/Y',strtotime($op['fecha'])); ?></small></td><td><span class="operation-badge <?php echo $op['tipo']==='Venta'?'sale':'purchase'; ?>"><i class="bi <?php echo $op['tipo']==='Venta'?'bi-arrow-up-right':'bi-arrow-down-left'; ?>"></i><?php echo $op['tipo']; ?></span></td><td><strong><?php echo htmlspecialchars($op['documento']); ?></strong><?php if($op['estado']==='Anulada'): ?><small class="d-block text-danger">Anulada</small><?php endif; ?></td><td class="text-end fw-semibold"><?php echo format_money($op['total']); ?></td><td><a class="btn btn-sm btn-light" href="<?php echo $op['url']; ?>"><i class="bi bi-chevron-right"></i></a></td></tr><?php endforeach; ?>
 </tbody></table></div></section></div>
 <div class="col-xl-5"><section class="card dashboard-panel h-100"><header class="dashboard-panel-header"><div><span class="chart-kicker">Control de existencias</span><h2>Stock por atender</h2></div><a href="<?php echo url('pages/inventario/index.php'); ?>">Ver inventario <i class="bi bi-arrow-right"></i></a></header><div class="stock-alert-list">
 <?php if(!$stock_critico): ?><div class="dashboard-empty-success"><i class="bi bi-shield-check"></i><strong>Inventario bajo control</strong><span>No hay productos en su nivel mínimo.</span></div><?php endif; ?>
 <?php foreach(array_slice($stock_critico,0,5) as $a): ?><a href="<?php echo url('pages/inventario/kardex.php?producto_id='.$a['producto_id']); ?>" class="stock-alert-item"><span class="stock-alert-icon"><i class="bi bi-box-seam"></i></span><span class="stock-alert-copy"><strong><?php echo htmlspecialchars($a['nombre']); ?></strong><small><?php echo htmlspecialchars($a['almacen']); ?> · mínimo <?php echo $a['minimo'].' '.htmlspecialchars($a['unidad']); ?></small></span><span class="stock-alert-value"><?php echo $a['stock']; ?></span></a><?php endforeach; ?>
 </div></section></div>
</div>
<script id="dashboardChartData" type="application/json"><?php echo json_encode(['labels'=>array_column($serie,'label'),'sales'=>array_column($serie,'ventas'),'purchases'=>array_column($serie,'compras'),'warehouseLabels'=>array_keys($valor_por_almacen),'warehouseValues'=>array_values($valor_por_almacen)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<?php $page_scripts=['assets/js/dashboard.js']; include '../includes/footer.php'; ?>
