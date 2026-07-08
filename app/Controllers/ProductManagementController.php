<?php

declare(strict_types=1);

class ProductManagementController extends Controller
{
    private AuthService $auth;
    private ProductManagementService $products;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->products = new ProductManagementService();
    }

    public function index(): void
    {
        $this->auth->authorizeRole('product_manager');
        $result = $this->products->indexData($_GET['product_no'] ?? null, $_GET['name'] ?? null);

        $this->render('staff/product_manager_products', [
            'pageTitle' => '商品管理',
            'filters' => $result['filters'],
            'products' => $result['products'],
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('product_manager'),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            'csrfToken' => csrf_token(),
        ]);
    }

    public function create(): void
    {
        $this->auth->authorizeRole('product_manager');
        $this->renderForm('new', $this->products->blankForm(), []);
    }

    public function store(): void
    {
        $this->auth->authorizeRole('product_manager');

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            $this->renderForm('new', $_POST, ['不正なリクエストです。']);
            return;
        }

        $result = $this->products->createProduct($_POST);

        if (($result['ok'] ?? false) !== true) {
            $this->renderForm('new', $_POST, $result['errors'] ?? []);
            return;
        }

        flash('success', '商品を登録しました。');
        $this->redirect('/staff/product-manager/products/' . urlencode((string) $result['product_id']) . '/edit');
    }

    public function edit(string $productId): void
    {
        $this->auth->authorizeRole('product_manager');
        $product = $this->findProductOr404($productId);

        if ($product === null) {
            return;
        }

        $this->renderForm('edit', $this->products->formFromProduct($product), [], $product);
    }

    public function update(string $productId): void
    {
        $this->auth->authorizeRole('product_manager');
        $product = $this->findProductOr404($productId);

        if ($product === null) {
            return;
        }

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            $this->renderForm('edit', $_POST, ['不正なリクエストです。'], $product);
            return;
        }

        $result = $this->products->updateProduct((int) $product['id'], $_POST);

        if (($result['ok'] ?? false) !== true) {
            $this->renderForm('edit', $_POST, $result['errors'] ?? [], $product);
            return;
        }

        flash('success', '商品情報を更新しました。');
        $this->redirect('/staff/product-manager/products/' . urlencode((string) $product['id']) . '/edit');
    }

    public function receiveStock(string $productId): void
    {
        $this->auth->authorizeRole('product_manager');
        $product = $this->findProductOr404($productId);

        if ($product === null) {
            return;
        }

        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/staff/product-manager/products/' . urlencode((string) $product['id']) . '/edit');
        }

        $result = $this->products->receiveStock((int) $product['id'], $_POST);

        if (($result['ok'] ?? false) !== true) {
            flash('error', implode(' ', $result['errors'] ?? ['在庫更新に失敗しました。']));
            $this->redirect('/staff/product-manager/products/' . urlencode((string) $product['id']) . '/edit');
        }

        flash('success', sprintf('%s を %d 点入庫しました。', (string) $product['name'], (int) $result['quantity']));
        $this->redirect('/staff/product-manager/products/' . urlencode((string) $product['id']) . '/edit');
    }

    /**
     * @param array<string, mixed> $form
     * @param array<int, string> $errors
     * @param array<string, mixed>|null $product
     */
    private function renderForm(string $mode, array $form, array $errors, ?array $product = null): void
    {
        $this->render('staff/product_manager_form', [
            'pageTitle' => $mode === 'new' ? '商品新規追加' : '商品編集',
            'mode' => $mode,
            'form' => $form,
            'errors' => $errors,
            'product' => $product,
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('product_manager'),
            'csrfToken' => csrf_token(),
        ]);
    }

    private function findProductOr404(string $productId): ?array
    {
        if (!ctype_digit($productId)) {
            http_response_code(404);
            echo '404 Not Found';
            return null;
        }

        $product = $this->products->findProduct((int) $productId);

        if ($product === null) {
            http_response_code(404);
            echo '404 Not Found';
            return null;
        }

        return $product;
    }
}
