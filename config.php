<?php
/* Configuratie bestand voor automatische pad- en URL-detectie, werkt op localhost en live servers */

define('BASE_PATH', __DIR__);

function getBaseUrl()
{
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $scriptPath = rtrim($scriptPath, '/');

    $currentFile = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

    if (strpos($currentFile, '/vragen-tool/') !== false) {
        $parts = explode('/vragen-tool/', $currentFile);
        return $parts[0] . '/vragen-tool';
    }

    if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
        return $scriptPath;
    }

    if (strpos($currentFile, '/views/') !== false) {
        return dirname($scriptPath);
    }

    return $scriptPath;
}

define('BASE_URL', getBaseUrl());

function url($path = '')
{
    $path = ltrim($path, '/');
    $baseUrl = BASE_URL;

    if ($baseUrl === '' || $baseUrl === '/') {
        return '/' . $path;
    }

    return $baseUrl . '/' . $path;
}

function path($path = '')
{
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}