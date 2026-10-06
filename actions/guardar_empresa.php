<?php
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/companies.php';
require_once '../includes/company_logos.php';
action_response(function () { return save_company_with_logo($_POST, $_FILES['logo'] ?? null); });
