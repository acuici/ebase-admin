<?php

// +----------------------------------------------------------------------
// | EBASE API 路由定义
// +----------------------------------------------------------------------
// | 所有 API 前缀为 /api/v1，集合使用复数名词。
// +----------------------------------------------------------------------

use think\facade\Route;

Route::get('api/v1/health/db', 'HealthController/db');

Route::get('api/v1/health', function () {
    return json([
        'code'       => 'OK',
        'message'    => 'success',
        'data'       => ['status' => 'up', 'time' => date('c')],
        'request_id' => strtoupper(bin2hex(random_bytes(8))),
    ]);
})->pattern(['path' => 'api/v1/health']);

Route::get('api/v1/health/db', 'HealthController/db');
Route::get('api/v1/storefront-public/:siteCode/manifest', 'StorefrontPublicController/manifest');
Route::get('storefront/:siteCode/sitemap.xml', 'StorefrontPublicController/sitemap');
Route::get('storefront/:siteCode/robots.txt', 'StorefrontPublicController/robots');

Route::group('api/v1/channel-stores', function () {
    Route::get('/', 'ChannelStoreController/index')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.read');
    Route::post('/', 'ChannelStoreController/create')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.create');
    Route::get('/:id', 'ChannelStoreController/read')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.read');
    Route::patch('/:id', 'ChannelStoreController/update')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.update');
    Route::post('/:id/disable', 'ChannelStoreController/disable')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.disable');
    Route::post('/:id/sync', 'ChannelStoreController/sync')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.store.sync');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1/channel-products', function () {
    Route::get('/', 'ChannelProductController/index')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.read');
    Route::post('/', 'ChannelProductController/create')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.create');
    Route::get('/:id', 'ChannelProductController/read')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.read');
    Route::patch('/:id', 'ChannelProductController/update')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.update');
    Route::delete('/:id', 'ChannelProductController/archive')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.archive');
    Route::post('/:id/sync', 'ChannelProductController/sync')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.product.sync');
    Route::post('/:id/skus', 'ChannelProductController/createSku')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.mapping.manage');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1/channel-product-skus', function () {
    Route::patch('/:id', 'ChannelProductController/updateSku')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.mapping.manage');
    Route::delete('/:id', 'ChannelProductController/archiveSku')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.mapping.manage');
})->middleware(\app\common\middleware\AuthMiddleware::class);

Route::group('api/v1/channel-order-item-exceptions', function () {
    Route::get('/', 'ChannelOrderExceptionController/index')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order_exception.read');
    Route::get('/:id', 'ChannelOrderExceptionController/read')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order_exception.read');
    Route::post('/:id/resolve', 'ChannelOrderExceptionController/resolve')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order_exception.resolve');
    Route::post('/:id/ignore', 'ChannelOrderExceptionController/ignore')->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order_exception.resolve');
})->middleware(\app\common\middleware\AuthMiddleware::class);

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

Route::group('api/v1/secondary-operations', function () {
    Route::post('/:type', 'SecondaryOperationsController/create');
    Route::put('/:type/:id', 'SecondaryOperationsController/update');
    Route::delete('/:type/:id', 'SecondaryOperationsController/delete');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 运营模块聚合读取 ----
Route::get('api/v1/reports/operations', 'OperationsController/report')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/operations/dashboard', 'OperationsController/dashboard')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/operations/:module/stats', 'OperationsController/stats')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/operations/:module', 'OperationsController/module')->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 系统设置 ----
Route::group('api/v1/system-settings', function () {
    Route::get('/:group', 'SystemSettingsController/index');
    Route::put('/:group', 'SystemSettingsController/save');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/operation-logs', 'SystemSettingsController/operationLogs')->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 消费者与营销 ----
Route::group('api/v1/customers', function () {
    Route::get('/', 'CustomerController/index');
    Route::post('/', 'CustomerController/create');
    Route::get('/:id', 'CustomerController/read');
    Route::put('/:id', 'CustomerController/update');
    Route::get('/:id/addresses', 'CustomerController/addresses');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1/marketing', function () {
    Route::get('coupons', 'MarketingController/coupons');
    Route::post('coupons', 'MarketingController/createCoupon');
    Route::post('coupons/:couponId/claims', 'MarketingController/claim');
    Route::get('campaigns', 'MarketingController/campaigns');
    Route::get('approvals', 'MarketingController/approvals');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1/content', function () {
    Route::get('reviews', 'ContentReviewController/index');
    Route::post(':contentId/reviews', 'ContentReviewController/submit');
    Route::post('reviews/:id', 'ContentReviewController/review');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 智能补货、商品质量与物流异常 ----
Route::group('api/v1/restock', function () {
    Route::get('suggestions', 'RestockController/suggestions');
    Route::post('plans', 'RestockController/create');
})->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/products/:productId/quality-report', 'ProductQualityController/report')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::group('api/v1/logistics/exceptions', function () {
    Route::get('/', 'LogisticsExceptionController/index');
    Route::post('detect', 'LogisticsExceptionController/detect');
    Route::put('/:id', 'LogisticsExceptionController/update');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 通知中心 ----
Route::group('api/v1/notifications', function () {
    Route::get('/', 'NotificationController/index');
    Route::post('/:id/read', 'NotificationController/read');
    Route::post('/read-all', 'NotificationController/readAll');
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
Route::post('api/v1/channel-orders/import', 'ChannelOrderController/import')->middleware(\app\common\middleware\AuthMiddleware::class)->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order.import');
Route::post('api/v1/channel-orders/:id/confirm-inventory', 'ChannelOrderController/confirmInventory')->middleware(\app\common\middleware\AuthMiddleware::class)->middleware(\app\common\middleware\PermissionMiddleware::class, 'channel.order.inventory_confirm');
Route::group('api/v1', function () {
    Route::get('orders/:orderId/fulfillments', 'FulfillmentController/index');
    Route::post('orders/:orderId/fulfillments', 'FulfillmentController/create');
    Route::post('fulfillments/:id/ship', 'FulfillmentController/ship');
    Route::get('shipment-packages/:packageId/tracking-events', 'FulfillmentController/tracking');
    Route::post('shipment-packages/:packageId/tracking-events', 'FulfillmentController/addTracking');
    Route::post('shipment-packages/:packageId/subscriptions', 'LogisticsSubscriptionController/subscribe');
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
    Route::get('/:siteId/content/:id', 'StorefrontContentController/read');
    Route::post('/:siteId/content/:id/publish', 'StorefrontContentController/publish');
    Route::delete('/:siteId/content/:id', 'StorefrontContentController/delete');
})->middleware(\app\common\middleware\AuthMiddleware::class);

// ---- 支付 ----
Route::post('api/v1/payment-callbacks/:channel', 'PaymentController/callback');
Route::get('api/v1/payment-channels', 'PaymentController/channels')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::post('api/v1/orders/:orderId/payments', 'PaymentController/create')->middleware(\app\common\middleware\AuthMiddleware::class);
Route::get('api/v1/orders/:orderId/refunds', 'PaymentController/refunds')->middleware(\app\common\middleware\AuthMiddleware::class);

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
