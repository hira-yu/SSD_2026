<?php

declare(strict_types=1);

class AccountingController extends Controller
{
    private AuthService $auth;
    private AccountingService $accounting;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->accounting = new AccountingService();
    }

    public function index(): void
    {
        $this->auth->authorizeRole('accountant');
        $result = $this->accounting->searchOrders($_GET);

        $this->render('staff/accounting_orders', [
            'pageTitle' => '会計係向け注文検索',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('accountant'),
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$result,
        ]);
    }

    public function show(string $orderNo): void
    {
        $this->auth->authorizeRole('accountant');
        $detail = $this->accounting->findOrderDetail($orderNo);

        if ($detail === null) {
            flash('error', '対象の注文が見つかりませんでした。');
            $this->redirect('/staff/accountant/orders');
        }

        $this->render('staff/accounting_order_detail', [
            'pageTitle' => '会計係向け注文詳細',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('accountant'),
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$detail,
        ]);
    }

    public function updatePayment(string $orderNo): void
    {
        $this->auth->authorizeRole('accountant');

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/staff/accountant/orders/' . urlencode($orderNo));
        }

        $result = $this->accounting->markPaymentAsPaid($orderNo);

        if ($result['ok']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        $targetOrderNo = (string) ($result['order_no'] ?? $orderNo);
        $this->redirect('/staff/accountant/orders/' . urlencode($targetOrderNo));
    }
}
