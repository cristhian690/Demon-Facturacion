<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedores = get_data('proveedores');
    
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    
    $prov_data = [
        'tipo_documento' => $_POST['tipo_documento'] ?? 'RUC',
        'numero_documento' => $_POST['numero_documento'] ?? '',
        'nombre' => $_POST['nombre'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo' => $_POST['correo'] ?? ''
    ];
    
    if ($is_edit) {
        $id = (int)$_POST['id'];
        $prov_data['id'] = $id;
        foreach ($proveedores as $key => $p) {
            if ($p['id'] == $id) {
                $proveedores[$key] = $prov_data;
                break;
            }
        }
    } else {
        $max_id = 0;
        foreach ($proveedores as $p) {
            if (isset($p['id']) && $p['id'] > $max_id) $max_id = $p['id'];
        }
        $prov_data['id'] = $max_id + 1;
        $proveedores[] = $prov_data;
    }
    
    save_data('proveedores', $proveedores);
}

header("Location: " . BASE_URL . "pages/proveedores/index.php");
exit;
