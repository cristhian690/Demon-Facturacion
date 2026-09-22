<?php
// Synthetic fixtures only: this script never reads or writes the project's data directory.
$temp = sys_get_temp_dir() . '/facturacion-test-' . bin2hex(random_bytes(8));
mkdir($temp);
define('DATA_PATH', $temp . '/');
define('BASE_URL', '/');
$_SESSION = ['empresa_id' => 1, 'form_token' => 'test-token'];
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/catalog.php';
require __DIR__ . '/../includes/operations.php';
require __DIR__ . '/../includes/kardex.php';
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function rejected($callback, $message) {
    try { $callback(); } catch (InvalidArgumentException $e) { check(true, $message); return; }
    throw new RuntimeException($message);
}
function fixture($name, $rows) { file_put_contents(DATA_PATH . $name . '.json', json_encode($rows)); }
function snapshot() { $out = []; foreach (glob(DATA_PATH . '*.json') as $file) $out[$file] = hash_file('sha256', $file); return $out; }
try {
    foreach (['clientes','proveedores','productos','almacenes'] as $table) fixture($table, [
        ['id'=>1,'empresa_id'=>1,'nombre'=>'Uno','sku'=>'P1','estado'=>'Activo'],
        ['id'=>2,'empresa_id'=>2,'nombre'=>'Dos','sku'=>'P2','estado'=>'Activo'],
        ['id'=>3,'nombre'=>'Sin asignar','sku'=>'P3','estado'=>'Activo']
    ]);
    fixture('inventario', [['producto_id'=>1,'almacen_id'=>1,'empresa_id'=>1,'stock_actual'=>10,'cpp'=>5,'valor_inventario'=>50], ['producto_id'=>2,'almacen_id'=>2,'empresa_id'=>2,'stock_actual'=>30,'cpp'=>9,'valor_inventario'=>270]]);
    foreach (['compras','ventas','kardex'] as $name) fixture($name, []);
    foreach (['clientes','proveedores','productos','almacenes'] as $table) {
        check(count(get_data($table)) === 1, 'List isolation: ' . $table);
        rejected(function () use ($table) { owned_record($table, 2); }, 'Foreign ID rejected');
        rejected(function () use ($table) { save_catalog($table, ['id'=>'2']); }, 'Foreign edit rejected');
        rejected(function () use ($table) { owned_record($table, 3); }, 'Unassigned record hidden');
    }
    $base = ['empresa_id'=>'1','form_token'=>'test-token','request_id'=>str_repeat('a',32),'cliente_id'=>'1','proveedor_id'=>'1','almacen_id'=>'1','tipo_documento'=>'Factura','serie'=>'F001','numero'=>'1','fecha'=>'2026-09-17','productos'=>['1','1'],'cantidades'=>['3','2'],'precios'=>['20','30'],'costos'=>['10','10'],'descuentos'=>['0','10'],'res_total'=>'0'];
    $before = snapshot();
    foreach (['empresa_id'=>'2','form_token'=>'old','cliente_id'=>'2','almacen_id'=>'2','productos'=>['2','1'],'cantidades'=>['6','5']] as $field=>$value) {
        $bad = $base; $bad[$field] = $value;
        rejected(function () use ($bad) { data_transaction(function () use ($bad) { return process_operation(true, $bad); }); }, 'Invalid operation: '.$field);
        check(snapshot() === $before, 'Rejected operation must not write');
    }
    foreach (['-1','0','1.5','NaN',[], '100000001'] as $quantity) {
        $bad = $base; $bad['cantidades'][0] = $quantity;
        rejected(function () use ($bad) { process_operation(true, $bad); }, 'Invalid quantity');
    }
    $result = data_transaction(function () use ($base) { return process_operation(true, $base); });
    $sale = get_data('ventas')[0];
    check($sale['subtotal'] == 114 && $sale['igv'] == 20.52 && $sale['total'] == 134.52, 'Server totals');
    check(get_data('inventario')[0]['stock_actual'] == 5, 'Stock reduced exactly once');
    check(count(get_data('kardex')) === 2, 'One movement per line');
    check($sale['costo_ventas_total'] == 25 && get_data('inventario')[0]['cpp'] == 5, 'CPP separate from sale price');
    $after = snapshot();
    check(data_transaction(function () use ($base) { return process_operation(true, $base); }) === $result, 'Retry returns same document');
    check(snapshot() === $after, 'Retry never writes twice');
    $duplicate = $base; $duplicate['request_id'] = str_repeat('b',32);
    rejected(function () use ($duplicate) { process_operation(true, $duplicate); }, 'Duplicate document rejected');
    $purchase = $base; $purchase['request_id'] = str_repeat('c',32); $purchase['productos']=['1']; $purchase['cantidades']=['5']; $purchase['costos']=['10']; $purchase['descuentos']=['20'];
    data_transaction(function () use ($purchase) { return process_operation(false, $purchase); });
    $inv = get_data('inventario')[0];
    check($inv['stock_actual']==10 && $inv['valor_inventario']==65 && $inv['cpp']==6.5, 'Discounted purchase CPP');
    $rows = get_data('kardex');
    $filtered = filter_kardex($rows, ['producto_id'=>'1','almacen_id'=>'1','desde'=>'2026-09-17','hasta'=>'2026-09-17']);
    check(count($filtered)===3 && $filtered===$rows, 'Inclusive dates preserve snapshots');
    check(count(filter_kardex($rows,['desde'=>'2026-09-18']))===0, 'Date excludes earlier records');
    rejected(function () use ($rows) { filter_kardex($rows, ['almacen_id'=>'2']); }, 'Foreign Kardex filter');
    rejected(function () use ($rows) { filter_kardex($rows, ['desde'=>'2026-09-18','hasta'=>'2026-09-17']); }, 'Reversed dates');
    $backdated = $base; $backdated['request_id']=str_repeat('d',32); $backdated['numero']='2'; $backdated['fecha']='2026-09-16';
    rejected(function () use ($backdated) { process_operation(true, $backdated); }, 'Backdated movement cannot invalidate historical balances');
    $foreignPurchase = $purchase; $foreignPurchase['request_id']=str_repeat('e',32); $foreignPurchase['proveedor_id']='2';
    rejected(function () use ($foreignPurchase) { process_operation(false, $foreignPurchase); }, 'Foreign supplier rejected');
    $dateRows = $rows;
    $dateRows[0]['fecha']='2026-09-16';
    $dateRows[1]['fecha']='2026-09-17 23:59:59';
    $oneDay = filter_kardex($dateRows, ['desde'=>'2026-09-17','hasta'=>'2026-09-17']);
    check(count($oneDay)===2 && $oneDay[1]['saldo_cantidad']===$dateRows[1]['saldo_cantidad'], 'End date includes the entire day without rebasing balance');
    $record = ['nombre'=>'Cliente nuevo','tipo_documento'=>'DNI','numero_documento'=>'12345678','direccion'=>'','telefono'=>'','correo'=>'test@example.com'];
    data_transaction(function () use ($record) { return save_catalog('clientes', $record); });
    check(count(read_data('clientes'))===4 && get_data('clientes')[1]['id']===4, 'Global IDs preserve legacy and other company records');
    $beforeEdit = read_data('clientes');
    $record['id']='4'; $record['nombre']='Cliente editado'; save_catalog('clientes', $record);
    check(get_data('clientes')[1]['nombre']==='Cliente editado', 'Own edit succeeds');
    check(read_data('clientes')[0] === $beforeEdit[0], 'Foreign record unchanged');
    $_SESSION['empresa_id']=2;
    check(count(get_data('ventas'))===0 && count(get_data('compras'))===0 && count(get_data('kardex'))===0, 'Operation isolation');
    check(get_data('inventario')[0]['stock_actual']===30, 'Other company stock unchanged');
    rejected(function () use ($base) { validate_context($base); }, 'Stale company form');
    $_SESSION['empresa_id']=1; $_SESSION['form_token']='rotated';
    rejected(function () use ($base) { validate_context($base); }, 'Switch away and back invalidates form');
    echo "OK: $checks verificaciones; solo datos temporales.\n";
} finally {
    foreach (glob(DATA_PATH . '*') as $file) unlink($file);
    if (is_file(DATA_PATH . '.write.lock')) unlink(DATA_PATH . '.write.lock');
    rmdir($temp);
}
