<?php

declare(strict_types=1);

class CartController extends Controller
{
    private CartService $cart;

    public function __construct()
    {
        $this->cart = new CartService();
    }

    public function index(): void
    {
        $this->render('cart.index', [
            'pageTitle' => 'カート',
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$this->cart->cartViewData(),
        ]);
    }

    public function add(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/products');
        }

        if (!$this->isPositiveInteger($_POST['product_id'] ?? null) || !$this->isPositiveInteger($_POST['quantity'] ?? null)) {
            flash('error', '商品または数量の指定が不正です。');
            $this->redirect('/products');
        }

        $productId = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];

        try {
            flash('success', $this->cart->addItem($productId, $quantity));
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }

        $this->redirect('/cart');
    }

    public function update(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/cart');
        }

        if (!$this->isPositiveInteger($_POST['product_id'] ?? null) || !$this->isNonNegativeInteger($_POST['quantity'] ?? null)) {
            flash('error', '商品または数量の指定が不正です。');
            $this->redirect('/cart');
        }

        $productId = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];

        try {
            flash('success', $this->cart->updateItem($productId, $quantity));
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }

        $this->redirect('/cart');
    }

    public function remove(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect('/cart');
        }

        if (!$this->isPositiveInteger($_POST['product_id'] ?? null)) {
            flash('error', '商品の指定が不正です。');
            $this->redirect('/cart');
        }

        $productId = (int) $_POST['product_id'];
        flash('success', $this->cart->removeItem($productId));
        $this->redirect('/cart');
    }

    private function isPositiveInteger(mixed $value): bool
    {
        return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0;
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        return is_scalar($value) && ctype_digit((string) $value);
    }
}
