<?php
require_once '../config.php'; require_once '../includes/helpers.php'; require_once '../includes/finance.php';
action_response(function(){ return process_refund($_POST); });
