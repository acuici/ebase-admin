<?php
// 全局中间件定义文件

use app\common\middleware\RequestIdMiddleware;

return [
    // 全局请求 ID
    RequestIdMiddleware::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
];
