<?php
require_once '../config.php'; require_once '../includes/helpers.php'; require_once '../includes/finance.php';
$safe=function($value){$value=(string)$value;if(preg_match('/^[=+\-@]/',$value))$value="'".$value;return $value;};
$report=$_GET['reporte']??''; $from=$_GET['desde']??''; $to=$_GET['hasta']??'';
header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="reporte-'.$report.'-'.date('Ymd').'.csv"'); echo "\xEF\xBB\xBF"; $out=fopen('php://output','wb');
if($report==='ventas'){fputcsv($out,['Fecha','Documento','Total','Estado documento','Costo despachado'],';');foreach(get_data('ventas') as $v){if(($from&&$v['fecha']<$from)||($to&&$v['fecha']>$to))continue;fputcsv($out,array_map($safe,[$v['fecha'],$v['tipo_documento'].' '.$v['serie'].'-'.$v['numero'],$v['total'],$v['estado_documento']??'Vigente',$v['costo_ventas_total']??0]),';');}}
elseif($report==='cobros'){fputcsv($out,['Venta','Vencimiento','Estado','Cargo','Cobrado','Saldo','Saldo a favor'],';');foreach(get_data('ventas') as $v){$f=sale_financials($v);fputcsv($out,array_map($safe,[$v['serie'].'-'.$v['numero'],$v['fecha_vencimiento']??'',$f['estado'],$f['cargo'],$f['pagado'],$f['saldo'],$f['saldo_favor']]),';');}}
else{http_response_code(400);fputcsv($out,['Reporte no válido'],';');} fclose($out); exit;
