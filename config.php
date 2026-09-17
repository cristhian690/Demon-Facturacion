<?php
// config.php
session_start();

// Definir constante base
define('BASE_URL', '/');
define('DATA_PATH', __DIR__ . '/data/');

// Inicializar sesión demo
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = [
        'nombre' => 'Admin Demo',
        'rol' => 'Administrador'
    ];
}

// Si no hay empresa seleccionada, intentar cargar la primera
if (!isset($_SESSION['empresa_id'])) {
    $empresasJson = file_get_contents(DATA_PATH . 'empresas.json');
    if ($empresasJson) {
        $empresas = json_decode($empresasJson, true);
        if (!empty($empresas)) {
            $_SESSION['empresa_id'] = $empresas[0]['id'];
            $_SESSION['empresa_nombre'] = $empresas[0]['razon_social'];
        }
    }
}
