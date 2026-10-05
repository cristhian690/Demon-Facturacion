<?php
require_once __DIR__ . '/dispatches.php';

function returned_quantity($sale, $lineIndex) {
    $qty = 0;
    foreach ($sale['devoluciones'] ?? [] as $return) foreach ($return['detalles'] ?? [] as $detail) if ((int)$detail['linea'] === (int)$lineIndex) $qty += (float)$detail['cantidad'];
    return round($qty, 3);
}
function process_return($input) {
    $sale = owned_record('ventas', $input['venta_id'] ?? '');
    $request = input_text($input,'request_id',true);
    if (!preg_match('/^[a-f0-9]{32}$/D',$request)) throw new InvalidArgumentException('Identificador inválido.');
    foreach ($sale['devoluciones'] ?? [] as $return) if ($return['request_id'] === $request) return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id'])];
    $date=input_text($input,'fecha',true); if (!valid_date($date) || $date < $sale['fecha']) throw new InvalidArgumentException('Fecha de devolución inválida.');
    $reason=input_text($input,'motivo',true); $quantities=$input['cantidades']??null;
    if (!is_array($quantities) || array_keys($quantities)!==array_keys($sale['detalles'])) throw new InvalidArgumentException('Detalle incompleto.');
    $inventory=get_data('inventario'); $ledger=get_data('kardex'); $details=[]; $credit=0; $returnId=next_id('devoluciones'); $ledgerId=next_id('kardex');
    foreach ($sale['detalles'] as $i=>$line) {
        $product=owned_record('productos',$line['producto_id']);
        $qty=quantity_value($quantities[$i],$line['unidad_medida']??$product['unidad_medida']??'UN',true);
        $available=round(delivered_quantity($sale,$line)-returned_quantity($sale,$i),3);
        if (round($qty-$available,3)>0) throw new InvalidArgumentException('La línea '.($i+1).' supera lo entregado aún no devuelto.');
        if ($qty<=0) continue;
        $index=null; foreach($inventory as $key=>$row) if($row['producto_id']==$line['producto_id']&&$row['almacen_id']==$sale['almacen_id']){$index=$key;break;}
        if($index===null) throw new InvalidArgumentException('No existe inventario para recibir la devolución.');
        $remaining=$qty; $slices=[];
        foreach($sale['despachos']??[] as $di=>$dispatch) foreach($dispatch['detalles']??[] as $dd=>$sent) {
            if((int)($sent['linea']??-1)!==$i || $remaining<=0) continue;
            $already=0; foreach($sale['devoluciones']??[] as $old) foreach($old['detalles']??[] as $od) foreach($od['origenes']??[] as $origin) if(($origin['despacho']??-1)===$di&&($origin['detalle']??-1)===$dd)$already+=(float)$origin['cantidad'];
            $take=min($remaining,max(0,(float)$sent['cantidad']-$already)); if($take<=0)continue;
            $slices[]=['despacho'=>$di,'detalle'=>$dd,'cantidad'=>$take,'costo_unitario'=>(float)$sent['costo_unitario']]; $remaining=round($remaining-$take,3);
        }
        if($remaining>0.0001) throw new InvalidArgumentException('No se pudo trazar la devolución a sus despachos originales.');
        $returnedCost = 0;
        foreach($slices as $slice){
            $previous=$inventory[$index]; $value=round($slice['cantidad']*$slice['costo_unitario'],2); $stock=round($previous['stock_actual']+$slice['cantidad'],3); $newValue=round($previous['valor_inventario']+$value,2); $cpp=$stock>0?$newValue/$stock:0;
            $returnedCost += $value;
            $inventory[$index]=array_merge($previous,['stock_actual'=>$stock,'valor_inventario'=>$newValue,'cpp'=>$cpp]);
            $ledger[]=['id'=>$ledgerId++,'empresa_id'=>$sale['empresa_id'],'venta_id'=>$sale['id'],'devolucion_id'=>$returnId,'request_id'=>$request,'producto_id'=>$line['producto_id'],'almacen_id'=>$sale['almacen_id'],'fecha'=>$date,'documento'=>'DEV '.$sale['serie'].'-'.$sale['numero'],'tipo_operacion'=>'DEVOLUCION_VENTA','entrada_cantidad'=>$slice['cantidad'],'entrada_costo'=>$slice['costo_unitario'],'entrada_valor'=>$value,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>$stock,'saldo_valor'=>$newValue,'cpp'=>$cpp];
        }
        $sale['detalles'][$i]['costo_devuelto_total'] = round(($sale['detalles'][$i]['costo_devuelto_total'] ?? 0) + $returnedCost, 2);
        $sale['costo_devoluciones_total'] = round(($sale['costo_devoluciones_total'] ?? 0) + $returnedCost, 2);
        $lineCredit=round(($line['subtotal']/$line['cantidad'])*$qty*1.18,2); $credit+=$lineCredit;
        $details[]=['linea'=>$i,'producto_id'=>$line['producto_id'],'cantidad'=>$qty,'importe_credito'=>$lineCredit,'origenes'=>$slices];
    }
    if(!$details) throw new InvalidArgumentException('Ingresa al menos una cantidad a devolver.');
    $return=['id'=>$returnId,'empresa_id'=>$sale['empresa_id'],'venta_id'=>$sale['id'],'request_id'=>$request,'fecha'=>$date,'motivo'=>$reason,'importe_credito'=>round($credit,2),'detalles'=>$details];
    $sale['devoluciones'][]=$return; $returns=get_data('devoluciones'); $returns[]=$return; $sales=get_data('ventas'); foreach($sales as $key=>$row)if($row['id']==$sale['id']){$sales[$key]=$sale;break;}
    $audit=get_data('auditoria'); append_audit($audit,'REGISTRAR_DEVOLUCION','venta',$sale['id'],'Devolución #'.$returnId);
    save_batch(['ventas'=>$sales,'devoluciones'=>$returns,'inventario'=>$inventory,'kardex'=>$ledger,'auditoria'=>$audit]);
    return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id']),'message'=>'Devolución registrada.'];
}
function process_void_sale($input) {
    $sale=owned_record('ventas',$input['venta_id']??''); if(($sale['estado_documento']??'Vigente')==='Anulada') return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id'])];
    $reason=input_text($input,'motivo',true); $date=input_text($input,'fecha',true); if(!valid_date($date)||$date<$sale['fecha'])throw new InvalidArgumentException('Fecha de anulación inválida.');
    $sale['estado_documento']='Anulada'; $sale['anulacion']=['fecha'=>$date,'motivo'=>$reason,'request_id'=>input_text($input,'request_id',true)];
    $sales=get_data('ventas'); foreach($sales as $key=>$row)if($row['id']==$sale['id']){$sales[$key]=$sale;break;}
    $audit=get_data('auditoria'); append_audit($audit,'ANULAR_VENTA','venta',$sale['id'],$reason); save_batch(['ventas'=>$sales,'auditoria'=>$audit]);
    return ['redirect'=>url('pages/ventas/detalle.php?id='.$sale['id']),'message'=>'Venta anulada. Las entregas realizadas no se repusieron; registra la devolución física por separado.'];
}
