<?php
require_once '../config.php'; require_once '../includes/helpers.php'; require_once '../includes/returns.php';
action_response(function(){ return process_return($_POST); });
