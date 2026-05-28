<?php

/**
 * AnimusFlowStudio Development Server Router
 * Serves static files directly; all other requests go to index.php.
 * Usage: php -S 127.0.0.1:8001 -t public public/server.php
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'
);

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

require_once __DIR__ . '/index.php';
