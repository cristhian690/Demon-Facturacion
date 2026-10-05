<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empresa_id'])) {
    if (!is_string($_POST['form_token'] ?? null) || !hash_equals($_SESSION['form_token'] ?? '', $_POST['form_token'])) {
        http_response_code(403); exit('El formulario venció. Recarga la página.');
    }
    $empresa_id = (int)$_POST['empresa_id'];
    
    // Buscar el nombre de la empresa
    $empresasJson = file_get_contents(DATA_PATH . 'empresas.json');
    if ($empresasJson) {
        $empresas = json_decode($empresasJson, true);
        foreach ($empresas as $emp) {
            if ((int)$emp['id'] === $empresa_id) {
                if (($_SESSION['empresa_id'] ?? null) != $empresa_id) $_SESSION['form_token'] = bin2hex(random_bytes(32));
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
