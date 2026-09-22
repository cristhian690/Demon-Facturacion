<?php
$temp = sys_get_temp_dir() . '/dispatch-test-' . bin2hex(random_bytes(8));
mkdir($temp);
define('DATA_PATH', $temp . '/'); define('BASE_URL', '/');
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/dispatches.php';
require __DIR__ . '/../includes/kardex.php';
$_SESSION = ['empresa_id'=>1, 'form_token'=>'test'];
$checks = 0;
function verify($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function fixture($table, $rows) { file_put_contents(DATA_PATH . $table . '.json', json_encode($rows)); }
function hashes() { $out=[]; foreach (glob(DATA_PATH . '*.json') as $file) $out[basename($file)]=hash_file('sha256',$file); return $out; }
function reject_unchanged($callback, $message) {
    $before=hashes(); $rejected=false;
    try { data_transaction($callback); } catch (InvalidArgumentException $e) { $rejected=true; }
    verify($rejected, $message); verify($before===hashes(), 'Rejected operation changed files: ' . $message);
}
function invoice($changes=[]) {
    static $number=0; $number++;
    return array_replace(['empresa_id'=>'1','form_token'=>'test','request_id'=>bin2hex(random_bytes(16)), 'cliente_id'=>'1','proveedor_id'=>'1','almacen_id'=>'1','tipo_documento'=>'Factura','serie'=>'T001','numero'=>(string)$number,'fecha'=>'2026-09-22','productos'=>['1'],'cantidades'=>['2.5'],'precios'=>['150'],'costos'=>['100'],'descuentos'=>['0']], $changes);
}
function submit_operation($sale,$input) { return data_transaction(function()use($sale,$input){return process_operation($sale,$input);}); }
function submit_dispatch($input) { return data_transaction(function()use($input){return process_dispatch($input);}); }
try {
    foreach (['clientes','proveedores','almacenes'] as $table) fixture($table,[['id'=>1,'empresa_id'=>1,'nombre'=>'Own','estado'=>'Activo'],['id'=>2,'empresa_id'=>2,'nombre'=>'Other','estado'=>'Activo']]);
    fixture('productos', [['id'=>1,'empresa_id'=>1,'nombre'=>'Kilos','unidad_medida'=>'KG'],['id'=>2,'empresa_id'=>1,'nombre'=>'Piezas','unidad_medida'=>'PZA'],['id'=>3,'empresa_id'=>2,'nombre'=>'Other','unidad_medida'=>'KG']]);
    foreach (['ventas','compras','inventario','kardex'] as $table) fixture($table,[]);
    foreach (['KG','TN','T','M','L'] as $unit) verify(quantity_value('0.125',$unit)===0.125,'Fractional unit ' . $unit);
    foreach (['UN','UND','PIEZA','PZAS'] as $unit) reject_unchanged(function()use($unit){quantity_value('1.5',$unit);},'Whole unit ' . $unit);
    foreach (['0','-1','1.2345','NaN','1e-3',[],true] as $qty) reject_unchanged(function()use($qty){quantity_value($qty,'KG');},'Invalid quantity');
    submit_operation(false, invoice(['cantidades'=>['10.125']]));
    $inv=get_data('inventario')[0]; verify($inv['stock_actual']==10.125 && $inv['cpp']==100,'Decimal purchase CPP');
    $pending=invoice(['entrega'=>'pendiente','productos'=>['1','1'],'cantidades'=>['2.5','1.125'],'precios'=>['150','160'],'costos'=>['100','100'],'descuentos'=>['0','0']]);
    $stockHash=hash_file('sha256',DATA_PATH.'inventario.json'); $ledgerHash=hash_file('sha256',DATA_PATH.'kardex.json');
    submit_operation(true,$pending);
    $sale=get_data('ventas')[0];
    verify(dispatch_status($sale)==='Pendiente' && $sale['costo_ventas_total']==0,'Pending state');
    verify(hash_file('sha256',DATA_PATH.'inventario.json')===$stockHash && hash_file('sha256',DATA_PATH.'kardex.json')===$ledgerHash,'Pending invoice does not touch stock/ledger');
    $snapshot=hashes(); submit_operation(true,$pending); verify(hashes()===$snapshot,'Invoice retry');
    $dispatch=['empresa_id'=>'1','form_token'=>'test','venta_id'=>(string)$sale['id'],'request_id'=>bin2hex(random_bytes(16)),'fecha'=>'2026-09-22','cantidades'=>['1.25','0.125']];
    submit_dispatch($dispatch);
    $sale=get_data('ventas')[0]; verify(dispatch_status($sale)==='Entrega parcial','Partial status');
    verify($sale['detalles'][0]['despachado']==1.25 && $sale['detalles'][1]['despachado']==0.125,'Delivered by line');
    verify(get_data('inventario')[0]['stock_actual']==8.75 && count(get_data('kardex'))===3,'One output per dispatched line');
    verify($sale['costo_ventas_total']==137.5,'Only delivered cost recognized');
    $snapshot=hashes(); submit_dispatch($dispatch); verify(hashes()===$snapshot,'Dispatch retry does not duplicate');
    foreach ([['cantidades'=>['2.5','0']],['empresa_id'=>'2'],['form_token'=>'stale'],['cantidades'=>['0','0']],['cantidades'=>['0.0001','0']],['fecha'=>'2026-09-21'],['cantidades'=>['1']]] as $bad) {
        $input=array_replace($dispatch,['request_id'=>bin2hex(random_bytes(16))],$bad);
        reject_unchanged(function()use($input){process_dispatch($input);},'Invalid dispatch');
    }
    // A new purchase changes CPP; each subsequent output keeps its own historical cost.
    submit_operation(false,invoice(['cantidades'=>['1.25'],'costos'=>['200']]));
    verify(get_data('inventario')[0]['cpp']==112.5,'Weighted cost after second purchase');
    $finish=array_replace($dispatch,['request_id'=>bin2hex(random_bytes(16)),'cantidades'=>['1.25','1']]);
    submit_dispatch($finish); $sale=get_data('ventas')[0];
    verify(dispatch_status($sale)==='Entregada' && $sale['costo_ventas_total']==390.625,'Completed with costs from each delivery');
    verify($sale['total']==654.9 && $sale['detalles'][0]['cantidad']==2.5,'Billed amounts unchanged');
    verify(get_data('inventario')[0]['stock_actual']==7.75,'Final fractional stock');
    $again=array_replace($finish,['request_id'=>bin2hex(random_bytes(16))]);
    reject_unchanged(function()use($again){process_dispatch($again);},'Cannot dispatch completed invoice');
    $filtered=filter_kardex(get_data('kardex'),['venta_id'=>(string)$sale['id']]);
    verify(count($filtered)===4 && $filtered[0]['salida_costo']==100 && $filtered[3]['salida_costo']==112.5,'Sale ledger excludes purchases, preserves CPP history');
    // Invoice may exceed stock when delivery is pending, but actual delivery may not.
    $large=invoice(['entrega'=>'pendiente','productos'=>['1','1'],'cantidades'=>['5','5'],'precios'=>['100','100'],'costos'=>['100','100'],'descuentos'=>['0','0']]);
    submit_operation(true,$large); $second=get_data('ventas')[1];
    $over=array_replace($dispatch,['venta_id'=>(string)$second['id'],'request_id'=>bin2hex(random_bytes(16)),'cantidades'=>['4','4']]);
    reject_unchanged(function()use($over){process_dispatch($over);},'Aggregate duplicate product stock check');
    $immediate=invoice(['cantidades'=>['0.125']]); submit_operation(true,$immediate);
    $third=get_data('ventas')[2]; verify(dispatch_status($third)==='Entregada' && count($third['despachos'])===1,'Immediate delivery tracked');
    $old=$third; unset($old['entrega'],$old['despachos']); $old['id']=99;
    foreach($old['detalles'] as &$line) unset($line['despachado']); unset($line);
    $sales=get_data('ventas');$sales[]=$old;save_data('ventas',$sales);
    verify(dispatch_status($old)==='Entregada','Historical invoice treated as delivered');
    $legacy=array_replace($dispatch,['venta_id'=>'99','request_id'=>bin2hex(random_bytes(16)),'cantidades'=>['0.125']]);
    reject_unchanged(function()use($legacy){process_dispatch($legacy);},'Historical stock never deducted again');
    $_SESSION['empresa_id']=2;
    $foreign=array_replace($over,['empresa_id'=>'2']);
    reject_unchanged(function()use($foreign){process_dispatch($foreign);},'Foreign sale rejected');
    verify(count(get_data('ventas'))===0 && count(get_data('kardex'))===0,'Company isolation');
    echo "OK: $checks verificaciones de cantidades y despachos, solo datos temporales.\n";
} finally {
    foreach(glob(DATA_PATH.'*') as $file) unlink($file);
    if(is_file(DATA_PATH.'.write.lock')) unlink(DATA_PATH.'.write.lock');
    rmdir($temp);
}
