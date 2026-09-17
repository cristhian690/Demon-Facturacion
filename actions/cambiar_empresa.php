<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empresa_id'])) {
    $empresa_id = (int)$_POST['empresa_id'];
    
    // Buscar el nombre de la empresa
    $empresasJson = file_get_contents(DATA_PATH . 'empresas.json');
    if ($empresasJson) {
        $empresas = json_decode($empresasJson, true);
        foreach ($empresas as $emp) {
            if ($emp['id'] === $empresa_id) {
                $_SESSION['empresa_id'] = $empresa_id;
                $_SESSION['empresa_nombre'] = $emp['razon_social'];
                break;
            }
        }
    }
}

// Redirigir a la página anterior
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'pages/dashboard.php';
header("Location: $referer");
exit;
