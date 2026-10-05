<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/demo.php';
if (!ENABLE_DEMO_TOOLS) {
    http_response_code(404);
    exit('La carga de datos de demostración está deshabilitada.');
}
action_response(function () { return load_demo_data(); });
