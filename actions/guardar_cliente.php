<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientes = get_data('clientes');
    
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    
    $cliente_data = [
        'tipo_documento' => $_POST['tipo_documento'] ?? 'DNI',
        'numero_documento' => $_POST['numero_documento'] ?? '',
        'nombre' => $_POST['nombre'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo' => $_POST['correo'] ?? ''
    ];
    
    if ($is_edit) {
        $id = (int)$_POST['id'];
        $cliente_data['id'] = $id;
        foreach ($clientes as $key => $c) {
            if ($c['id'] == $id) {
                $clientes[$key] = $cliente_data;
                break;
            }
        }
    } else {
        $max_id = 0;
        foreach ($clientes as $c) {
            if (isset($c['id']) && $c['id'] > $max_id) $max_id = $c['id'];
        }
        $cliente_data['id'] = $max_id + 1;
        $clientes[] = $cliente_data;
    }
    
    save_data('clientes', $clientes);
}

header("Location: " . BASE_URL . "pages/clientes/index.php");
exit;
