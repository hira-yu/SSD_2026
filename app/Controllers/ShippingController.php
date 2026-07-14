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

    public function document(string $orderNo): void
    {
        $this->auth->authorizeRole('shipper');
        $detail = $this->shipping->findOrderDetail($orderNo);

        if ($detail === null) {
            http_response_code(404);
            echo '対象の注文が見つかりませんでした。';
            return;
        }

        try {
            $pdf = (new DeliveryDocumentPdfService())->generate(
                (array) $detail['order'],
                (array) $detail['items']
            );
        } catch (Throwable $exception) {
            app_log('Delivery document PDF generation failed', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'order_no' => $orderNo,
            ]);
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: inline');
            header('Cache-Control: no-store, max-age=0');
            echo 'PDFの生成に失敗しました。時間をおいて再度お試しください。';
            return;
        }

        $safeOrderNo = preg_replace('/[^A-Za-z0-9_-]/', '', $orderNo) ?: 'order';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="delivery-document-' . $safeOrderNo . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, no-store, max-age=0');
        echo $pdf;
    }
}
