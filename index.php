<?php
// index.php
require_once 'config.php';
// Redirigir al dashboard por defecto
header("Location: " . BASE_URL . "pages/dashboard.php");
exit;
