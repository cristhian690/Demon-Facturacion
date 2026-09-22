<?php
function save_catalog($table, $input) {
    $fields = [
        'clientes' => ['tipo_documento','numero_documento','nombre','direccion','telefono','correo'],
        'proveedores' => ['tipo_documento','numero_documento','nombre','direccion','telefono','correo'],
        'productos' => ['sku','nombre','descripcion','categoria','marca','unidad_medida','stock_minimo','estado'],
        'almacenes' => ['nombre','ubicacion','estado']
    ];
    $required = ['nombre','sku','unidad_medida','tipo_documento','numero_documento','estado'];
    $id = input_text($input, 'id');
    $record = $id !== '' ? owned_record($table, $id) : ['id' => next_id($table), 'empresa_id' => (int)$_SESSION['empresa_id']];
    foreach ($fields[$table] as $field) $record[$field] = input_text($input, $field, in_array($field, $required, true));
    if (!empty($record['correo']) && !filter_var($record['correo'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresa un correo válido.');
    if (isset($record['tipo_documento'])) {
        if (!in_array($record['tipo_documento'], ['DNI','RUC','CE'], true)) throw new InvalidArgumentException('Tipo de documento inválido.');
        $pattern = ['DNI' => '/^\d{8}$/', 'RUC' => '/^\d{11}$/', 'CE' => '/^[a-zA-Z0-9]{6,20}$/'][$record['tipo_documento']];
        if (!preg_match($pattern, $record['numero_documento'])) throw new InvalidArgumentException('Revisa el número de documento.');
    }
    if (isset($record['estado']) && !in_array($record['estado'], ['Activo','Inactivo'], true)) throw new InvalidArgumentException('Estado inválido.');
    if ($table === 'productos') {
        if (filter_var($record['stock_minimo'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) throw new InvalidArgumentException('Stock mínimo inválido.');
        $record['stock_minimo'] = (int)$record['stock_minimo'];
    }
    $rows = get_data($table);
    $unique = $table === 'productos' ? 'sku' : (isset($record['numero_documento']) ? 'numero_documento' : 'nombre');
    foreach ($rows as $row) if ($row['id'] != $record['id'] && strcasecmp($row[$unique] ?? '', $record[$unique]) === 0) throw new InvalidArgumentException('Ya existe un registro con ese ' . $unique . '.');
    $found = false;
    foreach ($rows as &$row) if ($row['id'] == $record['id']) { $row = $record; $found = true; }
    unset($row);
    if (!$found) $rows[] = $record;
    save_data($table, $rows);
    $labels = ['clientes'=>'Cliente','proveedores'=>'Proveedor','productos'=>'Producto','almacenes'=>'Almacén'];
    return ['record' => $record, 'message' => $labels[$table] . ($id !== '' ? ' actualizado: ' : ' agregado: ') . $record['nombre']];
}
