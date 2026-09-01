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
    Route::post('password/reset', 'AuthController/resetPassword');
});

// ---- 认证（需登录） ----
Route::group('api/v1/auth', function () {
    Route::post('logout', 'AuthController/logout');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 成员（需登录） ----
Route::group('api/v1/member', function () {
    Route::get('profile', 'MemberController/profile');
    Route::patch('profile', 'MemberController/updateProfile');
    Route::get('sessions', 'MemberController/sessions');
    Route::delete('sessions/others', 'MemberController/revokeOtherSessions');
    Route::get('auth-logs', 'MemberController/authLogs');
    Route::delete('sessions/:id', 'MemberController/revokeSession');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 系统设置：成员与角色（需登录 + 显式权限） ----
Route::group('api/v1/admin', function () {
    Route::get('members', 'MemberAdminController/index')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.read');
    Route::get('members/:id', 'MemberAdminController/read')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.read');
    Route::post('members/invite', 'MemberAdminController/invite')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.invite');
    Route::put('members/:id', 'MemberAdminController/update')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.update');
    Route::post('members/:id/disable', 'MemberAdminController/disable')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.disable');
    Route::post('members/:id/reset-password', 'MemberAdminController/resetPassword')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.member.reset_password');
    Route::get('roles', 'RoleController/index')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.role.read');
    Route::post('roles', 'RoleController/create')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.role.create');
    Route::get('roles/:id', 'RoleController/read')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.role.read');
    Route::put('roles/:id', 'RoleController/update')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.role.update');
    Route::delete('roles/:id', 'RoleController/delete')->middleware(\app\common\middleware\PermissionMiddleware::class, 'admin.role.delete');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 素材库 ----
Route::group('api/v1/assets', function () {
    Route::get('/', 'AssetController/index');
    Route::post('/', 'AssetController/upload');
    Route::post('/:id/relations', 'AssetController/attach');
    Route::get('/:id/download', 'AssetController/download');
    Route::delete('/:id', 'AssetController/delete');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 渠道订单与履约 ----
Route::post('api/v1/channel-orders/import', 'ChannelOrderController/import')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1', function () {
    Route::get('orders/:orderId/fulfillments', 'FulfillmentController/index');
    Route::post('orders/:orderId/fulfillments', 'FulfillmentController/create');
    Route::post('fulfillments/:id/ship', 'FulfillmentController/ship');
    Route::get('shipment-packages/:packageId/tracking-events', 'FulfillmentController/tracking');
    Route::post('shipment-packages/:packageId/tracking-events', 'FulfillmentController/addTracking');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 独立站 ----
Route::group('api/v1/storefront/sites', function () {
    Route::get('/', 'StorefrontSiteController/index');
    Route::post('/', 'StorefrontSiteController/create');
    Route::get('/:id', 'StorefrontSiteController/read');
    Route::put('/:id', 'StorefrontSiteController/update');
    Route::get('/:siteId/products', 'StorefrontListingController/index');
    Route::put('/:siteId/products/:productId', 'StorefrontListingController/upsert');
    Route::get('/:siteId/content', 'StorefrontContentController/index');
    Route::put('/:siteId/content', 'StorefrontContentController/upsert');
})->middleware(\app\common\middleware\AuthMiddleware::class);

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
