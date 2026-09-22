<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/dispatches.php';
action_response(function () { return process_dispatch($_POST); });
