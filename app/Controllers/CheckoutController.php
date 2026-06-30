<?php

declare(strict_types=1);

class CheckoutController extends Controller
{
    private CartService $cart;
    private CheckoutService $checkout;

    public function __construct()
    {
        $this->cart = new CartService();
        $this->checkout = new CheckoutService();
    }

    public function index(): void
    {
        if ($this->cart->isEmpty()) {
            flash('error', 'カートが空のため、注文情報の入力へ進めません。');
            $this->redirect('/cart');
        }

        $this->render('checkout.index', [
            'pageTitle' => 'ネット注文情報入力',
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$this->checkout->checkoutFormData(),
        ]);
    }

    public function confirm(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/checkout');
        }

        $result = $this->checkout->buildConfirmation($_POST);

        if (($result['ok'] ?? false) !== true) {
            $this->render('checkout.index', [
                'pageTitle' => 'ネット注文情報入力',
                'csrfToken' => csrf_token(),
                ...$this->checkout->checkoutFormData($result['form'] ?? $_POST, $result['errors'] ?? []),
            ]);
            return;
        }

        $this->render('checkout.confirm', [
            'pageTitle' => 'ネット注文確認',
            'csrfToken' => csrf_token(),
            ...$result,
        ]);
    }

    public function complete(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/checkout');
        }

        $result = $this->checkout->completeOrder();

        if (($result['ok'] ?? false) !== true) {
            flash('error', (string) (($result['errors'][0] ?? '注文の確定に失敗しました。')));
            $this->redirect('/checkout');
        }

        $this->redirect('/checkout/done?order_no=' . urlencode((string) $result['order_no']));
    }

    public function done(): void
    {
        $orderNo = trim((string) ($_GET['order_no'] ?? ''));

        if ($orderNo === '') {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $data = $this->checkout->findDoneViewData($orderNo);

        if ($data === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->render('checkout.done', [
            'pageTitle' => 'ネット注文完了',
            ...$data,
        ]);
    }
}
