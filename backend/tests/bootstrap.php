<?php
// PHPUnit bootstrap: use the same public/index.php application bootstrap, without starting HTTP.
putenv('APP_ENV=testing');
putenv('DB_DRIVER=mysql');
putenv('DB_TYPE=mysql');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3307');
putenv('DB_NAME=ebase_test');
putenv('DB_USER=ebase_test');
putenv('DB_PASS=ebase_test_local');
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__));
$app->loadEnv('testing');
$app->setEnvName('testing');
$app->initialize();
$testingDatabase = require dirname(__DIR__) . '/config/testing/database.php';
\think\facade\Config::set(['database' => $testingDatabase]);
