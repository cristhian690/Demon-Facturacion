<?php
// includes/helpers.php

function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function scoped_table($name) {
    return in_array($name, ['clientes','proveedores','productos','almacenes','compras','ventas','inventario','kardex'], true);
}
function read_data($name) {
    $path = DATA_PATH . $name . '.json';
    if (!is_file($path)) return [];
    $file = fopen($path, 'rb');
    if (!$file || !flock($file, LOCK_SH)) throw new RuntimeException('No se pudo leer ' . $name);
    try { $content = stream_get_contents($file); }
    finally { flock($file, LOCK_UN); fclose($file); }
    $rows = json_decode($content, true);
    if (!is_array($rows)) throw new RuntimeException('Archivo de datos inválido: ' . $name);
    return $rows;
}
function belongs_to_company($row) {
    return isset($row['empresa_id'], $_SESSION['empresa_id']) && (int)$row['empresa_id'] === (int)$_SESSION['empresa_id'];
}
function get_data($name) {
    $rows = read_data($name);
    return scoped_table($name) ? array_values(array_filter($rows, 'belongs_to_company')) : $rows;
}

function format_money($amount) {
    return 'S/ ' . number_format($amount, 2, '.', ',');
}

function get_empresa_activa() {
    return [
        'id' => $_SESSION['empresa_id'] ?? null,
        'nombre' => $_SESSION['empresa_nombre'] ?? 'Sin empresa'
    ];
}

function save_data($name, $rows) {
    if (scoped_table($name)) {
        foreach ($rows as $row) if (!belongs_to_company($row)) throw new RuntimeException('Empresa incorrecta.');
        $others = array_filter(read_data($name), function ($row) { return !belongs_to_company($row); });
        $rows = array_merge(array_values($others), array_values($rows));
    }
    $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $temporary = tempnam(DATA_PATH, '.pending-');
    if ($temporary === false) throw new RuntimeException('No se pudo preparar ' . $name);
    try {
        if (file_put_contents($temporary, $json, LOCK_EX) !== strlen($json) || !rename($temporary, DATA_PATH . $name . '.json')) throw new RuntimeException('No se pudo guardar ' . $name);
    } finally { if (is_file($temporary)) unlink($temporary); }
    return true;
}
// Lock covers reading, validating, allocating IDs and writing all business records.
function data_transaction($callback) {
    $lock = fopen(DATA_PATH . '.write.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('No se pudo bloquear el almacenamiento.');
    try { return $callback(); }
    finally { flock($lock, LOCK_UN); fclose($lock); }
}
function save_batch($tables) {
    $before = [];
    foreach ($tables as $name => $rows) $before[$name] = is_file(DATA_PATH . $name . '.json') ? file_get_contents(DATA_PATH . $name . '.json') : null;
    try { foreach ($tables as $name => $rows) save_data($name, $rows); }
    catch (Throwable $e) {
        foreach ($before as $name => $content) {
            if ($content !== null) file_put_contents(DATA_PATH . $name . '.json', $content, LOCK_EX);
            elseif (is_file(DATA_PATH . $name . '.json')) unlink(DATA_PATH . $name . '.json');
        }
        throw $e;
    }
}
function next_id($name) {
    return array_reduce(read_data($name), function ($max, $row) { return max($max, (int)($row['id'] ?? 0)); }, 0) + 1;
}
function form_context() {
    if (empty($_SESSION['form_token'])) $_SESSION['form_token'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="empresa_id" value="' . (int)($_SESSION['empresa_id'] ?? 0) . '"><input type="hidden" name="form_token" value="' . $_SESSION['form_token'] . '"><input type="hidden" name="request_id" value="' . bin2hex(random_bytes(16)) . '">';
}
function validate_context($input) {
    if (empty($_SESSION['empresa_id']) || !is_scalar($input['empresa_id'] ?? null) || (string)$input['empresa_id'] !== (string)$_SESSION['empresa_id']) throw new InvalidArgumentException('La empresa activa cambió. Vuelve a abrir el formulario en la empresa correcta.');
    if (!is_string($input['form_token'] ?? null) || empty($input['form_token']) || !hash_equals($_SESSION['form_token'] ?? '', $input['form_token'])) throw new InvalidArgumentException('El formulario venció. Recarga la página.');
}
function owned_record($table, $id) {
    if (!is_scalar($id) || !ctype_digit((string)$id)) throw new InvalidArgumentException('Identificador inválido.');
    foreach (get_data($table) as $row) if ((string)$row['id'] === (string)$id) return $row;
    throw new InvalidArgumentException('El registro de ' . $table . ' no existe en la empresa activa.');
}
function input_text($input, $name, $required = false) {
    $value = $input[$name] ?? '';
    if (!is_string($value) || strlen($value) > 1000 || ($required && trim($value) === '')) throw new InvalidArgumentException('Revisa el campo ' . $name . '.');
    return trim($value);
}
function action_response($callback) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('Usa el formulario para guardar.');
        validate_context($_POST);
        echo json_encode(data_transaction($callback), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } catch (InvalidArgumentException $e) {
        http_response_code(422); echo json_encode(['error' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log($e->getMessage()); http_response_code(500); echo json_encode(['error' => 'No se pudo guardar. Revisa el almacenamiento e intenta nuevamente.']);
    }
    exit;
}
