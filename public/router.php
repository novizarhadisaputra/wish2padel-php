<?php
/**
 * Router script for the PHP built-in web server.
 * This simulates Apache's mod_rewrite by directing all
 * non-file requests to index.php.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the requested resource exists as a file or directory in the public folder, serve it.
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything to index.php
require_once __DIR__ . '/index.php';
