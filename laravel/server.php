<?php

/**
 * Custom PHP built-in server router for AIStory.
 *
 * Fixes two PHP built-in server quirks:
 * 1. Directories with index.html (admin/, user-app/) would cause the server
 *    to set SCRIPT_NAME to the index.html path, breaking request()->path().
 * 2. file_exists() returns true for directories, accidentally serving them
 *    directly instead of routing through Laravel.
 *
 * Only actual files with recognized static extensions bypass Laravel.
 */

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Static asset extensions that should be served directly
$staticExtensions = ['.css', '.js', '.svg', '.png', '.jpg', '.jpeg',
    '.gif', '.ico', '.woff', '.woff2', '.ttf', '.eot', '.json', '.map'];

$isStaticFile = false;
if ($uri !== '/' && is_file($publicPath . $uri)) {
    $ext = strrchr($uri, '.');
    if ($ext && in_array(strtolower($ext), $staticExtensions, true)) {
        $isStaticFile = true;
    }
}

if ($isStaticFile) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once $publicPath . '/index.php';

