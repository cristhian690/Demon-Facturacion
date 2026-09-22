<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/catalog.php';
action_response(function () { return save_catalog('almacenes', $_POST); });
