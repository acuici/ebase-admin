<?php
// [应用入口文件]
// 生产/开发环境保持原有 .env 与默认配置；只有 APP_ENV=testing 才切换到独立测试库。

use think\App;
use think\facade\Config;

$root = dirname(__DIR__);
$isTesting = getenv('APP_ENV') === 'testing';
if ($isTesting) {
    $testingEnv = parse_ini_file($root . '/.env.testing', false, INI_SCANNER_RAW) ?: [];
    foreach ($testingEnv as $key => $value) {
        putenv($key . '=' . $value);
    }
    putenv('APP_ENV=testing');
}

require $root . '/vendor/autoload.php';

$app = new App($root);
if ($isTesting) {
    $app->setEnvName('testing');
}
$app->initialize();

if ($isTesting) {
    $testingDatabase = require $root . '/config/testing/database.php';
    Config::set([
        'database' => $testingDatabase,
    ]);
}

$http = $app->http;
$response = $http->run();
$response->send();
$http->end($response);
