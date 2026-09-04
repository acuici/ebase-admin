<?php
// PHP built-in server router. All dynamic requests use the canonical application entry.
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if ($scriptName !== '' && is_file($documentRoot . $scriptName)) {
    return false;
}
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
require __DIR__ . '/index.php';
