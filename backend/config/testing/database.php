<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type' => 'mysql', 'hostname' => '127.0.0.1', 'database' => 'ebase_test', 'username' => 'ebase_test', 'password' => 'ebase_test_local', 'hostport' => 3307, 'charset' => 'utf8mb4', 'prefix' => '', 'fields_strict' => true, 'builder' => '\\think\\db\\builder\\Mysql', 'params' => [Pdo\Mysql::ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'],
        ],
    ],
];
