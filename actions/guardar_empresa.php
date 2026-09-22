<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/companies.php';
action_response(function () { return save_company($_POST); });
