<?php
// All writes go to disposable fixtures, never to project data.
$temporary = sys_get_temp_dir() . '/company-test-' . bin2hex(random_bytes(8));
mkdir($temporary);
define('DATA_PATH', $temporary . '/');
define('BASE_URL', '/');
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/companies.php';
$_SESSION = ['empresa_id' => 1, 'empresa_nombre' => 'Empresa Uno', 'form_token' => 'test'];
$checks = 0;
function verify_company($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
try {
    $first = ['id'=>1, 'razon_social'=>'Empresa Uno', 'ruc'=>'20111111111', 'direccion'=>'A', 'telefono'=>'123', 'correo'=>'uno@example.com', 'logo'=>'uno.png', 'extra'=>'conservar'];
    $second = array_merge($first, ['id'=>2, 'razon_social'=>'Empresa Dos', 'ruc'=>'20222222222']);
    file_put_contents(DATA_PATH . 'empresas.json', json_encode([$first, $second]));
    $input = ['razon_social'=>'Empresa Nueva', 'ruc'=>'20333333333', 'direccion'=>'Calle Nueva', 'telefono'=>'987654321', 'correo'=>'nueva@example.com', 'logo'=>'nueva.png'];
    $result = data_transaction(function () use ($input) { return save_company($input); });
    verify_company($result['record']['id'] === 3, 'New ID');
    foreach ($input as $field => $value) verify_company($result['record'][$field] === $value, 'Preserved field: ' . $field);
    verify_company($result['message'] === 'Empresa agregada: Empresa Nueva', 'Create confirmation');
    verify_company($_SESSION['empresa_id'] === 1 && $_SESSION['empresa_nombre'] === 'Empresa Uno', 'Create preserves active company');
    verify_company(array_slice(read_data('empresas'), 0, 2) === [$first, $second], 'Existing companies unchanged');
    $edit = array_merge($input, ['id'=>'2', 'ruc'=>'20222222222', 'razon_social'=>'Dos editada']);
    data_transaction(function () use ($edit) { return save_company($edit); });
    verify_company($_SESSION['empresa_id'] === 1 && $_SESSION['empresa_nombre'] === 'Empresa Uno', 'Editing another company preserves selection');
    verify_company(read_data('empresas')[1]['extra'] === 'conservar', 'Unknown existing fields preserved');
    $edit['id']='1'; $edit['ruc']='20111111111'; $edit['razon_social']='Uno editada';
    $result = data_transaction(function () use ($edit) { return save_company($edit); });
    verify_company($_SESSION['empresa_id'] === 1 && $_SESSION['empresa_nombre'] === 'Uno editada', 'Active name updated without switching');
    verify_company($result['message'] === 'Empresa actualizada: Uno editada', 'Edit confirmation');
    $before = file_get_contents(DATA_PATH . 'empresas.json');
    foreach ([['razon_social'=>' '], ['ruc'=>'123'], ['ruc'=>'abcdefghijk'], ['ruc'=>'20111111111'], ['correo'=>'invalid'], ['id'=>'999'], ['id'=>['1']], ['logo'=>['bad']]] as $badFields) {
        $rejected = false;
        try { data_transaction(function () use ($input, $badFields) { return save_company(array_merge($input, ['ruc'=>'20444444444'], $badFields)); }); }
        catch (InvalidArgumentException $e) { $rejected = true; }
        verify_company($rejected, 'Invalid input rejected');
        verify_company(file_get_contents(DATA_PATH . 'empresas.json') === $before, 'Rejected write leaves file intact');
    }
    // Repeated create is rejected by unique RUC; repeated edit does not add a row.
    $rejected = false;
    try { data_transaction(function () use ($input) { return save_company($input); }); }
    catch (InvalidArgumentException $e) { $rejected = true; }
    verify_company($rejected && count(read_data('empresas')) === 3, 'No duplicate on repeated create');
    data_transaction(function () use ($edit) { return save_company($edit); });
    verify_company(count(read_data('empresas')) === 3, 'No duplicate on repeated edit');
    echo "OK: $checks verificaciones de Empresas; datos temporales exclusivamente.\n";
} finally {
    foreach (glob(DATA_PATH . '*') as $file) unlink($file);
    if (is_file(DATA_PATH . '.write.lock')) unlink(DATA_PATH . '.write.lock');
    rmdir($temporary);
}
