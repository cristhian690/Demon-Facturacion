<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/operations.php';
action_response(function () { return process_operation(false, $_POST); });
