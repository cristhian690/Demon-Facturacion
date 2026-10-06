<?php
function save_company($input) {
    $id = input_text($input, 'id');
    $companies = get_data('empresas');
    // Start from the existing record so additional stored fields are preserved.
    $record = $id !== '' ? owned_record('empresas', $id) : ['id' => next_id('empresas')];
    if (!array_key_exists('estado', $input)) $input['estado'] = $record['estado'] ?? 'Activo';
    if (!array_key_exists('logo', $input)) $input['logo'] = $record['logo'] ?? '';
    foreach (['razon_social', 'nombre_comercial', 'ruc', 'direccion', 'telefono', 'correo', 'logo', 'estado'] as $field) {
        $record[$field] = input_text($input, $field, in_array($field, ['razon_social', 'ruc'], true));
    }
    if (!preg_match('/^[0-9]{11}$/', $record['ruc'])) throw new InvalidArgumentException('El RUC debe contener 11 dígitos.');
    if ($record['correo'] !== '' && !filter_var($record['correo'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresa un correo válido.');
    if (!in_array($record['estado'], ['Activo','Inactivo'], true)) throw new InvalidArgumentException('Estado inválido.');
    foreach ($companies as $company) {
        if ($company['id'] != $record['id'] && ($company['ruc'] ?? '') === $record['ruc']) throw new InvalidArgumentException('Ya existe una empresa con ese RUC.');
    }
    if ($id === '') {
        $companies[] = $record;
    } else {
        foreach ($companies as $key => $company) {
            if ($company['id'] == $record['id']) { $companies[$key] = $record; break; }
        }
    }
    save_data('empresas', $companies);
    // Change the displayed name only after a successful write; never switch company.
    if (($_SESSION['empresa_id'] ?? null) == $record['id']) $_SESSION['empresa_nombre'] = $record['razon_social'];
    return ['record' => $record, 'message' => 'Empresa ' . ($id === '' ? 'agregada: ' : 'actualizada: ') . $record['razon_social']];
}

function company_history_count($id) {
    $count = 0;
    foreach (['clientes','proveedores','productos','almacenes','compras','ventas','inventario','kardex','devoluciones','pagos'] as $table) {
        foreach (read_data($table) as $row) if ((string)($row['empresa_id'] ?? '') === (string)$id) $count++;
    }
    return $count;
}

function set_company_status($id, $status) {
    if (!in_array($status, ['Activo','Inactivo'], true)) throw new InvalidArgumentException('Estado inválido.');
    $record = owned_record('empresas', $id);
    if ($status === 'Inactivo' && (string)($_SESSION['empresa_id'] ?? '') === (string)$record['id']) {
        throw new InvalidArgumentException('Cambia a otra empresa activa antes de desactivar la empresa actual.');
    }
    $rows = get_data('empresas');
    foreach ($rows as &$row) if ((string)$row['id'] === (string)$record['id']) { $row['estado'] = $status; $record = $row; break; }
    unset($row);
    save_data('empresas', $rows);
    return ['record'=>$record, 'history_count'=>company_history_count($record['id']), 'message'=>'Empresa ' . ($status === 'Activo' ? 'reactivada: ' : 'desactivada: ') . $record['razon_social']];
}
