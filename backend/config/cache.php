<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => 'redis',

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            'type'       => 'File',
            'path'       => '',
            'prefix'     => '',
            'expire'     => 0,
            'tag_prefix' => 'tag:',
            'serialize'  => [],
        ],
        // Redis 缓存：验证码、Token/会话、热点数据、幂等键
        'redis' => [
            'type'       => '\think\cache\driver\Redis',
            'host'       => env('REDIS_HOST', '127.0.0.1'),
            'port'       => (int) env('REDIS_PORT', 6379),
            'password'   => env('REDIS_PASSWORD', ''),
            'select'     => (int) env('REDIS_SELECT', 0),
            'timeout'    => 0,
            'expire'     => 0,
            'persistent' => false,
            'prefix'     => env('REDIS_PREFIX', 'ebase:dev:'),
        ],
    ],
];
