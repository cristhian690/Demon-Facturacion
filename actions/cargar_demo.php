<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/demo.php';
action_response(function () { return load_demo_data(); });
