<?php
// Use with: php -S 127.0.0.1:8765 router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$resolved = realpath(__DIR__ . $path);
if (!$resolved || strpos($resolved, __DIR__ . DIRECTORY_SEPARATOR) !== 0 || preg_match('~^/(?:data|includes|tests|\.git)(?:/|$)~i', str_replace('\\', '/', $path))) {
    if ($path === '/') { require __DIR__ . '/index.php'; return; }
    http_response_code(404); exit('No disponible.');
}
// Check the resolved path too, so dot segments cannot bypass private directories.
$relative = str_replace('\\', '/', substr($resolved, strlen(__DIR__) + 1));
if (preg_match('~^(?:data|includes|tests|\.git)(?:/|$)~i', $relative)) { http_response_code(404); exit('No disponible.'); }
return false;
