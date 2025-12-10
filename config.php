<?php
// Configuration file for path management
// Works on: localhost, live server, subdirectory, root domain

// Get the directory where config.php is located (project root)
define('BASE_PATH', __DIR__);

// Auto-detect base URL
function getBaseUrl()
{
    // Get the current script's directory relative to DOCUMENT_ROOT
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $scriptPath = rtrim($scriptPath, '/');

    // If we're in a subdirectory of the current script, go up to find the real root
    // This handles cases where config is included from views/ folder
    $currentFile = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

    // Find where vragen-tool folder is in the path
    if (strpos($currentFile, '/vragen-tool/') !== false) {
        $parts = explode('/vragen-tool/', $currentFile);
        return $parts[0] . '/vragen-tool';
    }

    // If config.php is in root and we're accessing a file in root
    if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
        return $scriptPath;
    }

    // If we're accessing from views/ folder, go up one level
    if (strpos($currentFile, '/views/') !== false) {
        return dirname($scriptPath);
    }

    // Default: use current script directory
    return $scriptPath;
}

define('BASE_URL', getBaseUrl());

// Helper function to generate URLs for links, redirects, assets
function url($path = '')
{
    $path = ltrim($path, '/');
    $baseUrl = BASE_URL;

    // If BASE_URL is empty (root directory), just add leading slash
    if ($baseUrl === '' || $baseUrl === '/') {
        return '/' . $path;
    }

    return $baseUrl . '/' . $path;
}

// Helper function to generate absolute file system paths
function path($path = '')
{
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}