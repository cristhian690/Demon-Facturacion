<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresas = get_data('empresas');
    
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    
    $empresa_data = [
        'razon_social' => $_POST['razon_social'] ?? '',
        'ruc' => $_POST['ruc'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo' => $_POST['correo'] ?? '',
        'logo' => $_POST['logo'] ?? ''
    ];
    
    if ($is_edit) {
        $id = (int)$_POST['id'];
        $empresa_data['id'] = $id;
        foreach ($empresas as $key => $emp) {
            if ($emp['id'] == $id) {
                $empresas[$key] = $empresa_data;
                break;
            }
        }
        
        // Si se actualizó la empresa activa, actualizar sesión
        if ($_SESSION['empresa_id'] == $id) {
            $_SESSION['empresa_nombre'] = $empresa_data['razon_social'];
        }
    } else {
        // Generar nuevo ID
        $max_id = 0;
        foreach ($empresas as $emp) {
            if ($emp['id'] > $max_id) $max_id = $emp['id'];
        }
        $empresa_data['id'] = $max_id + 1;
        $empresas[] = $empresa_data;
    }
    
    save_data('empresas', $empresas);
}

header("Location: " . BASE_URL . "pages/empresas/index.php");
exit;
