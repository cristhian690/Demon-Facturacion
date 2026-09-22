<?php
function save_company($input) {
    $id = input_text($input, 'id');
    $companies = get_data('empresas');
    // Start from the existing record so additional stored fields are preserved.
    $record = $id !== '' ? owned_record('empresas', $id) : ['id' => next_id('empresas')];
    foreach (['razon_social', 'ruc', 'direccion', 'telefono', 'correo', 'logo'] as $field) {
        $record[$field] = input_text($input, $field, in_array($field, ['razon_social', 'ruc'], true));
    }
    if (!preg_match('/^[0-9]{11}$/', $record['ruc'])) throw new InvalidArgumentException('El RUC debe contener 11 dígitos.');
    if ($record['correo'] !== '' && !filter_var($record['correo'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresa un correo válido.');
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
