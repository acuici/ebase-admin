<?php

use app\common\middleware\CorsMiddleware;
use app\common\middleware\RequestIdMiddleware;

return [
    CorsMiddleware::class,
    RequestIdMiddleware::class,
];
