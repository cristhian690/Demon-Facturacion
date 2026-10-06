<?php
// XLSX Kardex checks. All fixtures and generated files live in a disposable directory.
$temp = sys_get_temp_dir().'/kardex-export-test-'.bin2hex(random_bytes(6));
mkdir($temp);
define('DATA_PATH', $temp.'/');
define('BASE_URL', '/');
$_SESSION = ['empresa_id' => 1, 'empresa_nombre' => 'Empresa Uno'];
require __DIR__.'/../includes/helpers.php';
require __DIR__.'/../includes/kardex_export.php';

$checks = 0;
function ke_check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function ke_fixture($name, $rows) { file_put_contents(DATA_PATH.$name.'.json', json_encode($rows, JSON_UNESCAPED_UNICODE)); }

try {
    ke_fixture('empresas', [
        ['id'=>1,'razon_social'=>'Empresa Uno S.A.C.','ruc'=>'20111111111'],
        ['id'=>2,'razon_social'=>'Empresa Ajena S.A.C.','ruc'=>'20222222222'],
    ]);
    ke_fixture('productos', [
        ['id'=>1,'empresa_id'=>1,'sku'=>'P-001','nombre'=>'Producto Activo','estado'=>'Activo'],
        ['id'=>2,'empresa_id'=>1,'sku'=>'P-002','nombre'=>'Producto Inactivo','estado'=>'Inactivo'],
        ['id'=>3,'empresa_id'=>2,'sku'=>'X-003','nombre'=>'Producto Ajeno','estado'=>'Activo'],
    ]);
    ke_fixture('almacenes', [
        ['id'=>1,'empresa_id'=>1,'nombre'=>'Principal'],
        ['id'=>2,'empresa_id'=>1,'nombre'=>'Secundario'],
        ['id'=>3,'empresa_id'=>2,'nombre'=>'Almacén Ajeno'],
    ]);
    ke_fixture('inventario', [
        ['id'=>1,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'stock_actual'=>7.123,'cpp'=>7.123456,'valor_inventario'=>50.74],
    ]);
    ke_fixture('ventas', []);
    foreach (['clientes','proveedores','compras'] as $table) ke_fixture($table, []);
    ke_fixture('kardex', [
        ['id'=>1,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'fecha'=>'2026-09-01 09:00:00','documento'=>'C-001','tipo_operacion'=>'COMPRA','entrada_cantidad'=>10.123,'entrada_costo'=>7.123456,'entrada_valor'=>72.109347,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>10.123,'cpp'=>7.123456,'saldo_valor'=>72.109347],
        ['id'=>2,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'fecha'=>'2026-09-02 10:00:00','documento'=>'F001-1','tipo_operacion'=>'VENTA','entrada_cantidad'=>0,'entrada_costo'=>0,'entrada_valor'=>0,'salida_cantidad'=>3,'salida_costo'=>7.123456,'salida_valor'=>21.370368,'saldo_cantidad'=>7.123,'cpp'=>7.123456,'saldo_valor'=>50.738979],
        ['id'=>3,'empresa_id'=>1,'producto_id'=>2,'almacen_id'=>2,'fecha'=>'2026-09-03 11:00:00','documento'=>'AJ-001','tipo_operacion'=>'AJUSTE','entrada_cantidad'=>2,'entrada_costo'=>5,'entrada_valor'=>10,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>2,'cpp'=>5,'saldo_valor'=>10],
        ['id'=>4,'empresa_id'=>2,'producto_id'=>3,'almacen_id'=>3,'fecha'=>'2026-09-01','documento'=>'OTRA','tipo_operacion'=>'COMPRA','entrada_cantidad'=>99,'entrada_costo'=>1,'entrada_valor'=>99,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>99,'cpp'=>1,'saldo_valor'=>99],
    ]);

    $before = array_map('sha1_file', glob(DATA_PATH.'*.json'));
    $all = kardex_export_context([]);
    ke_check(count($all['rows']) === 3, 'Only active-company movements must export');
    ke_check($all['company']['razon_social'] === 'Empresa Uno S.A.C.', 'Active company heading');
    ke_check(count(kardex_export_context(['producto_id'=>'1'])['rows']) === 2, 'Product filter');
    ke_check(count(kardex_export_context(['almacen_id'=>'2'])['rows']) === 1, 'Warehouse filter');
    ke_check(count(kardex_export_context(['tipo_operacion'=>'VENTA'])['rows']) === 1, 'Operation filter');
    ke_check(count(kardex_export_context(['desde'=>'2026-09-02','hasta'=>'2026-09-03'])['rows']) === 2, 'Inclusive date filter');
    $combined = kardex_export_context(['producto_id'=>'1','almacen_id'=>'1','tipo_operacion'=>'COMPRA','desde'=>'2026-09-01','hasta'=>'2026-09-01']);
    ke_check(count($combined['rows']) === 1 && $combined['rows'][0]['id'] === 1, 'Combined filters');
    ke_check(count(kardex_export_context(['producto_id'=>'2'])['rows']) === 1, 'Inactive product remains available historically');
    foreach ([['empresa_id'=>'2'], ['producto_id'=>'3'], ['almacen_id'=>'3']] as $invalid) {
        try { kardex_export_context($invalid); throw new RuntimeException('Foreign scope accepted'); }
        catch (InvalidArgumentException $e) { ke_check(true, 'Foreign scope rejected'); }
    }
    try { kardex_export_context(['desde'=>'2027-01-01']); throw new RuntimeException('Empty export accepted'); }
    catch (InvalidArgumentException $e) { ke_check($e->getMessage()==='No existen movimientos para exportar con los filtros seleccionados.', 'Clear empty-result message'); }

    $generalPath = $temp.'/general.xlsx';
    write_kardex_xlsx($all, $generalPath);
    ke_check(substr(file_get_contents($generalPath), 0, 2) === 'PK', 'Real ZIP-based XLSX');
    $zip = new ZipArchive();
    ke_check($zip->open($generalPath) === true, 'XLSX opens as ZIP');
    foreach (['[Content_Types].xml','xl/workbook.xml','xl/styles.xml','xl/worksheets/sheet1.xml'] as $part) ke_check($zip->locateName($part) !== false, 'Required XLSX part: '.$part);
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    ke_check(simplexml_load_string($sheet) !== false, 'Worksheet XML is valid');
    ke_check(str_contains($sheet, 'Empresa Uno S.A.C.') && !str_contains($sheet, 'Empresa Ajena'), 'No foreign company data in workbook');
    ke_check(str_contains($sheet, '>7.123456<') && str_contains($sheet, '>50.738979<'), 'Stored CPP and balance snapshot preserved exactly');
    ke_check(!str_contains($sheet, 'SALDO ACTUAL'), 'General export does not imply a current balance');
    ke_check(str_contains($sheet, 'autoFilter') && str_contains($sheet, 'state="frozen"'), 'Autofilter and frozen headers');

    $exactPath = $temp.'/exact.xlsx';
    $exact = kardex_export_context(['producto_id'=>'1','almacen_id'=>'1']);
    write_kardex_xlsx($exact, $exactPath);
    $zip = new ZipArchive(); $zip->open($exactPath); $exactSheet = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
    ke_check(str_contains($exactSheet, 'SALDO ACTUAL'), 'Current balance only for exact product and warehouse');
    ke_check(str_ends_with(kardex_export_filename($exact), '.xlsx') && !preg_match('/[^A-Za-z0-9_.-]/', kardex_export_filename($exact)), 'Safe XLSX filename');
    ke_check($before === array_map('sha1_file', glob(DATA_PATH.'*.json')), 'Export never changes JSON data');
    echo "XLSX KARDEX OK: $checks verificaciones; datos temporales intactos.\n";
} finally {
    foreach (glob($temp.'/*') as $file) unlink($file);
    rmdir($temp);
}
