<?php

declare(strict_types=1);

class ReceptionOrderController extends Controller
{
    private AuthService $auth;
    private ReceptionOrderService $orders;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->orders = new ReceptionOrderService();
    }

    public function create(): void
    {
        $this->auth->authorizeRole('receptionist');

        $this->render('staff/reception_order_new', [
            'pageTitle' => '電話/FAX注文登録',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$this->orders->newOrderFormData(),
        ]);
    }

    public function index(): void
    {
        $this->auth->authorizeRole('receptionist');
        $result = $this->orders->searchOrders($_GET);

        $this->render('staff/reception_orders', [
            'pageTitle' => '注文受付係向け注文一覧',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$result,
        ]);
    }

    public function show(string $orderNo): void
    {
        $this->auth->authorizeRole('receptionist');
        $detail = $this->orders->findOrderDetailViewData($orderNo);

        if ($detail === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->render('staff/reception_order_detail', [
            'pageTitle' => '注文受付係向け注文詳細',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$detail,
        ]);
    }

    public function confirm(): void
    {
        $this->auth->authorizeRole('receptionist');

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            $this->renderNewWithErrors($_POST, ['不正なリクエストです。再度入力してください。']);
            return;
        }

        $confirmation = $this->orders->buildConfirmation($_POST);

        if (($confirmation['ok'] ?? false) !== true) {
            $this->renderNewWithErrors($confirmation['form'] ?? $_POST, $confirmation['errors'] ?? []);
            return;
        }

        $this->render('staff/reception_order_confirm', [
            'pageTitle' => '電話/FAX注文確認',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$confirmation,
            'paymentGuide' => $this->orders->paymentGuide((string) $confirmation['form']['payment_method']),
        ]);
    }

    public function store(): void
    {
        $this->auth->authorizeRole('receptionist');

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            $this->renderNewWithErrors($_POST, ['不正なリクエストです。再度入力してください。']);
            return;
        }

        $result = $this->orders->createOrder($_POST);

        if (($result['ok'] ?? false) !== true) {
            $this->renderNewWithErrors($result['form'] ?? $_POST, $result['errors'] ?? []);
            return;
        }

        $this->redirect('/staff/receptionist/orders/complete?order_no=' . urlencode((string) $result['order_no']));
    }

    public function complete(): void
    {
        $this->auth->authorizeRole('receptionist');
        $orderNo = trim((string) ($_GET['order_no'] ?? ''));

        if ($orderNo === '') {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $completeViewData = $this->orders->findCompleteViewData($orderNo);

        if ($completeViewData === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->render('staff/reception_order_complete', [
            'pageTitle' => '電話/FAX注文登録完了',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$completeViewData,
        ]);
    }

    /**
     * @param array<string, mixed> $formInput
     * @param array<int, string> $errors
     */
    private function renderNewWithErrors(array $formInput, array $errors): void
    {
        $this->render('staff/reception_order_new', [
            'pageTitle' => '電話/FAX注文登録',
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
            ...$this->orders->newOrderFormData($formInput, $errors),
        ]);
    }
}
