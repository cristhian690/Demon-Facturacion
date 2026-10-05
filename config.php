<?php
// config.php
// XAMPP puede configurar C:\xampp\tmp como ruta de sesión aunque el usuario de
// Windows no tenga permisos allí. Usa la carpeta temporal escribible del usuario.
if (session_status() === PHP_SESSION_NONE) {
    $session_path = sys_get_temp_dir();
    if (is_dir($session_path) && is_writable($session_path)) session_save_path($session_path);
    session_start();
}

// Definir constante base
define('BASE_URL', '/');
define('DATA_PATH', __DIR__ . '/data/');
define('ENABLE_DEMO_TOOLS', false);

// Inicializar la sesión local mientras no exista un módulo de autenticación.
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = [
        'nombre' => 'Administrador',
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
