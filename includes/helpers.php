<?php
// includes/helpers.php

function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function get_data($jsonFile) {
    $path = DATA_PATH . $jsonFile . '.json';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function format_money($amount) {
    return 'S/ ' . number_format($amount, 2, '.', ',');
}

function get_empresa_activa() {
    return [
        'id' => $_SESSION['empresa_id'] ?? null,
        'nombre' => $_SESSION['empresa_nombre'] ?? 'Sin empresa'
    ];
}

function save_data($jsonFile, $data) {
    $path = DATA_PATH . $jsonFile . '.json';
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

