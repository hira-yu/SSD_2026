<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

bootstrap_app(startSession: true);

set_exception_handler(static function (Throwable $exception): void {
    app_log('Unhandled exception', [
        'type' => $exception::class,
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
    ]);

    http_response_code(500);
    echo 'システムエラーが発生しました。時間をおいて再度お試しください。';
});

$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->get('/favorites', [FavoriteController::class, 'index']);
$router->post('/favorites/add', [FavoriteController::class, 'add']);
$router->post('/favorites/remove', [FavoriteController::class, 'remove']);
$router->get('/sitemap', [UtilityController::class, 'sitemap']);
$router->get('/stores', [UtilityController::class, 'stores']);
$router->get('/terms', [UtilityController::class, 'terms']);
$router->get('/privacy', [UtilityController::class, 'privacy']);
$router->get('/commercial-transactions', [UtilityController::class, 'commercialTransactions']);
$router->get('/returns', [UtilityController::class, 'returns']);
$router->get('/shipping-guide', [UtilityController::class, 'shippingGuide']);
$router->get('/after-service', [UtilityController::class, 'afterService']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{id}', [ProductController::class, 'show']);
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout/confirm', [CheckoutController::class, 'confirm']);
$router->post('/checkout/complete', [CheckoutController::class, 'complete']);
$router->get('/checkout/done', [CheckoutController::class, 'done']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/staff/receptionist', [StaffController::class, 'receptionist']);
$router->get('/staff/receptionist/products', [ProductController::class, 'receptionistIndex']);
$router->get('/staff/receptionist/orders', [ReceptionOrderController::class, 'index']);
$router->get('/staff/receptionist/orders/{order_no}', [ReceptionOrderController::class, 'show']);
$router->get('/staff/receptionist/orders/new', [ReceptionOrderController::class, 'create']);
$router->post('/staff/receptionist/orders/confirm', [ReceptionOrderController::class, 'confirm']);
$router->post('/staff/receptionist/orders', [ReceptionOrderController::class, 'store']);
$router->get('/staff/receptionist/orders/complete', [ReceptionOrderController::class, 'complete']);
$router->get('/staff/accountant', [StaffController::class, 'accountant']);
$router->get('/staff/accountant/orders', [AccountingController::class, 'index']);
$router->get('/staff/accountant/orders/{order_no}', [AccountingController::class, 'show']);
$router->post('/staff/accountant/orders/{order_no}/payment', [AccountingController::class, 'updatePayment']);
$router->get('/staff/shipper', [StaffController::class, 'shipper']);
$router->get('/staff/shipper/orders', [ShippingController::class, 'index']);
$router->get('/staff/shipper/orders/{order_no}/document.pdf', [ShippingController::class, 'document']);
$router->get('/staff/shipper/orders/{order_no}', [ShippingController::class, 'show']);
$router->post('/staff/shipper/orders/{order_no}/ship', [ShippingController::class, 'ship']);
$router->get('/staff/product-manager', [StaffController::class, 'productManager']);
$router->get('/staff/product-manager/products', [ProductManagementController::class, 'index']);
$router->get('/staff/product-manager/products/new', [ProductManagementController::class, 'create']);
$router->post('/staff/product-manager/products', [ProductManagementController::class, 'store']);
$router->get('/staff/product-manager/products/{id}/edit', [ProductManagementController::class, 'edit']);
$router->post('/staff/product-manager/products/{id}', [ProductManagementController::class, 'update']);
$router->post('/staff/product-manager/products/{id}/stock', [ProductManagementController::class, 'receiveStock']);
$router->get('/system/db-check', [SystemController::class, 'dbCheck']);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
