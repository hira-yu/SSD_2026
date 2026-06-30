<?php

declare(strict_types=1);

class ShippingController extends Controller
{
    private AuthService $auth;
    private ShippingService $shipping;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shipping = new ShippingService();
    }

    public function index(): void
    {
        $this->auth->authorizeRole('shipper');
        $orders = $this->shipping->listOrders();

        $this->render('staff/shipping_orders', [
            'pageTitle' => '商品発送係向け未発送注文一覧',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('shipper'),
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$orders,
        ]);
    }

    public function show(string $orderNo): void
    {
        $this->auth->authorizeRole('shipper');
        $detail = $this->shipping->findOrderDetail($orderNo);

        if ($detail === null) {
            flash('error', '対象の注文が見つかりませんでした。');
            $this->redirect('/staff/shipper/orders');
        }

        $this->render('staff/shipping_order_detail', [
            'pageTitle' => '商品発送係向け注文詳細',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('shipper'),
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$detail,
        ]);
    }

    public function ship(string $orderNo): void
    {
        $this->auth->authorizeRole('shipper');

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/staff/shipper/orders/' . urlencode($orderNo));
        }

        $result = $this->shipping->markOrderAsShipped($orderNo);

        if ($result['ok']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        $targetOrderNo = (string) ($result['order_no'] ?? $orderNo);
        $this->redirect('/staff/shipper/orders/' . urlencode($targetOrderNo));
    }
}
