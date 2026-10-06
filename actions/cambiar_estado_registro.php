<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/catalog.php';
require_once '../includes/companies.php';

action_response(function () {
    $table = input_text($_POST, 'tabla', true);
    $id = input_text($_POST, 'id', true);
    $status = input_text($_POST, 'estado', true);
    return $table === 'empresas'
        ? set_company_status($id, $status)
        : set_catalog_status($table, $id, $status);
});
