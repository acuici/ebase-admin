<?php

// +----------------------------------------------------------------------
// | EBASE API 路由定义
// +----------------------------------------------------------------------
// | 所有 API 前缀为 /api/v1，集合使用复数名词。
// +----------------------------------------------------------------------

use think\facade\Route;

// ---- 健康检查 ----
Route::get('api/v1/health', function () {
    return json([
        'code'       => 'OK',
        'message'    => 'success',
        'data'       => ['status' => 'up', 'time' => date('c')],
        'request_id' => strtoupper(bin2hex(random_bytes(8))),
    ]);
});

// ---- 认证（公开） ----
Route::group('api/v1/auth', function () {
    Route::post('login', 'AuthController/login');
    Route::post('refresh', 'AuthController/refresh');
});

// ---- 认证（需登录） ----
Route::group('api/v1/auth', function () {
    Route::post('logout', 'AuthController/logout');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 成员（需登录） ----
Route::group('api/v1/member', function () {
    Route::get('profile', 'MemberController/profile');
    Route::get('sessions', 'MemberController/sessions');
    Route::delete('sessions/:id', 'MemberController/revokeSession');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 系统设置：成员与角色（需登录 + 权限） ----
Route::group('api/v1/admin', function () {
    // 成员目录
    Route::get('members', 'MemberAdminController/index');
    Route::get('members/:id', 'MemberAdminController/read');
    Route::post('members/invite', 'MemberAdminController/invite');
    Route::put('members/:id', 'MemberAdminController/update');
    Route::post('members/:id/disable', 'MemberAdminController/disable');
    Route::post('members/:id/reset-password', 'MemberAdminController/resetPassword');

    // 角色
    Route::get('roles', 'RoleController/index');
    Route::post('roles', 'RoleController/create');
    Route::get('roles/:id', 'RoleController/read');
    Route::put('roles/:id', 'RoleController/update');
    Route::delete('roles/:id', 'RoleController/delete');
})->middleware([\app\common\middleware\AuthMiddleware::class, \app\common\middleware\PermissionMiddleware::class]);

// ---- 支付 ----
Route::get('api/v1/payment-channels', 'PaymentController/channels')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::post('api/v1/orders/:orderId/payments', 'PaymentController/create')->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 订单 ----
Route::group('api/v1/orders', function () {
    Route::get('/', 'OrderController/index');
    Route::post('/', 'OrderController/create');
    Route::get('/:id', 'OrderController/read');
    Route::post('/:id/cancel', 'OrderController/cancel');
    Route::post('/:id/transition', 'OrderController/transition');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 商品与目录 ----
// SKU 嵌套路由必须在商品 /:id 路由前定义。
Route::group('api/v1/products/:productId/skus', function () {
    Route::get('/', 'ProductSkuController/index');
    Route::post('/', 'ProductSkuController/create');
    Route::put('/:id', 'ProductSkuController/update');
    Route::post('/:id/stock-adjustments', 'ProductSkuController/adjustStock');
})->middleware(\app\common\middleware\AuthMiddleware::class);

Route::group('api/v1/products', function () {
    Route::get('/', 'ProductController/index');
    Route::post('/', 'ProductController/create');
    Route::get('/:id', 'ProductController/read');
    Route::put('/:id', 'ProductController/update');
    Route::delete('/:id', 'ProductController/delete');
})->middleware(\app\common\middleware\AuthMiddleware::class);
