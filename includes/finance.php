<?php
require_once __DIR__ . '/operations.php';

function sale_return_credit($sale) {
    $credit = 0;
    foreach ($sale['devoluciones'] ?? [] as $return) $credit += (float)($return['importe_credito'] ?? 0);
    return round($credit, 2);
}
function sale_financials($sale, $payments = null, $refunds = null, $today = null) {
    $payments = $payments ?? get_data('pagos');
    $refunds = $refunds ?? get_data('reembolsos');
    $paid = 0; $refunded = 0;
    foreach ($payments as $row) if (($row['venta_id'] ?? 0) == $sale['id']) $paid += (float)$row['importe'];
    foreach ($refunds as $row) if (($row['venta_id'] ?? 0) == $sale['id']) $refunded += (float)$row['importe'];
    $charge = ($sale['estado_documento'] ?? 'Vigente') === 'Anulada' ? 0 : max(0, (float)$sale['total'] - sale_return_credit($sale));
    $netPaid = max(0, $paid - $refunded);
    $balance = max(0, round($charge - $netPaid, 2));
    $credit = max(0, round($netPaid - $charge, 2));
    $today = $today ?? date('Y-m-d');
    $status = $balance <= 0 ? 'Pagado' : ($netPaid > 0 ? 'Parcialmente pagado' : 'Pendiente');
    if ($balance > 0 && !empty($sale['fecha_vencimiento']) && $sale['fecha_vencimiento'] < $today) $status = 'Vencido';
    return ['cargo'=>round($charge,2),'pagado'=>round($paid,2),'reembolsado'=>round($refunded,2),'saldo'=>$balance,'saldo_favor'=>$credit,'estado'=>$status];
}
function process_payment($input) {
    $sale = owned_record('ventas', $input['venta_id'] ?? '');
    if (($sale['estado_documento'] ?? 'Vigente') === 'Anulada') throw new InvalidArgumentException('No se registran pagos en una venta anulada.');
    if (!empty($sale['pago_contado_automatico'])) throw new InvalidArgumentException('La venta al contado ya quedó pagada al confirmarse.');
    $request = input_text($input, 'request_id', true);
    if (!preg_match('/^[a-f0-9]{32}$/D', $request)) throw new InvalidArgumentException('Identificador inválido.');
    $payments = get_data('pagos');
    foreach ($payments as $row) if (($row['request_id'] ?? '') === $request) return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id']),'message'=>'El pago ya estaba registrado.'];
    $date = input_text($input, 'fecha', true);
    if (!valid_date($date) || $date < $sale['fecha']) throw new InvalidArgumentException('Fecha de pago inválida.');
    $amount = operation_number($input['importe'] ?? null, 'importe', 0.01, 100000000);
    $financial = sale_financials($sale, $payments);
    if (round($amount - $financial['saldo'], 2) > 0) throw new InvalidArgumentException('El pago supera el saldo pendiente de ' . format_money($financial['saldo']) . '.');
    $method = input_text($input, 'medio', true);
    if (!in_array($method, ['Efectivo','Transferencia','Tarjeta','Yape/Plin','Otro'], true)) throw new InvalidArgumentException('Medio de pago inválido.');
    $payments[] = ['id'=>next_id('pagos'),'empresa_id'=>(int)$_SESSION['empresa_id'],'venta_id'=>$sale['id'],'request_id'=>$request,'fecha'=>$date,'importe'=>round($amount,2),'medio'=>$method,'referencia'=>input_text($input,'referencia')];
    $audit = get_data('auditoria'); append_audit($audit, 'REGISTRAR_PAGO', 'venta', $sale['id'], format_money($amount));
    save_batch(['pagos'=>$payments,'auditoria'=>$audit]);
    return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id']),'message'=>'Pago registrado.'];
}
function process_refund($input) {
    $sale = owned_record('ventas', $input['venta_id'] ?? '');
    $request = input_text($input, 'request_id', true); $refunds = get_data('reembolsos');
    foreach ($refunds as $row) if (($row['request_id'] ?? '') === $request) return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id'])];
    $financial = sale_financials($sale, null, $refunds);
    $amount = operation_number($input['importe'] ?? null, 'importe', 0.01, 100000000);
    if (round($amount - $financial['saldo_favor'], 2) > 0) throw new InvalidArgumentException('El reembolso supera el saldo a favor.');
    $date = input_text($input,'fecha',true); if (!valid_date($date)) throw new InvalidArgumentException('Fecha inválida.');
    $refunds[]=['id'=>next_id('reembolsos'),'empresa_id'=>(int)$_SESSION['empresa_id'],'venta_id'=>$sale['id'],'request_id'=>$request,'fecha'=>$date,'importe'=>round($amount,2),'medio'=>input_text($input,'medio',true),'referencia'=>input_text($input,'referencia')];
    $audit=get_data('auditoria'); append_audit($audit,'REGISTRAR_REEMBOLSO','venta',$sale['id'],format_money($amount));
    save_batch(['reembolsos'=>$refunds,'auditoria'=>$audit]);
    return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id']),'message'=>'Reembolso registrado.'];
}
