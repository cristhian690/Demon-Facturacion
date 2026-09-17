<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productos = get_data('productos');
    
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    
    $prod_data = [
        'sku' => $_POST['sku'] ?? '',
        'nombre' => $_POST['nombre'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'categoria' => $_POST['categoria'] ?? '',
        'marca' => $_POST['marca'] ?? '',
        'unidad_medida' => $_POST['unidad_medida'] ?? 'UN',
        'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
        'estado' => $_POST['estado'] ?? 'Activo'
    ];
    
    if ($is_edit) {
        $id = (int)$_POST['id'];
        $prod_data['id'] = $id;
        foreach ($productos as $key => $p) {
            if ($p['id'] == $id) {
                $productos[$key] = $prod_data;
                break;
            }
        }
    } else {
        $max_id = 0;
        foreach ($productos as $p) {
            if (isset($p['id']) && $p['id'] > $max_id) $max_id = $p['id'];
        }
        $prod_data['id'] = $max_id + 1;
        $productos[] = $prod_data;
    }
    
    save_data('productos', $productos);
}

header("Location: " . BASE_URL . "pages/productos/index.php");
exit;
