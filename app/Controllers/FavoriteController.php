<?php

declare(strict_types=1);

class FavoriteController extends Controller
{
    private FavoriteService $favorites;
    private ProductService $products;
    private CartService $cart;

    public function __construct()
    {
        $this->favorites = new FavoriteService();
        $this->products = new ProductService();
        $this->cart = new CartService();
    }

    public function index(): void
    {
        $catalog = $this->products->homePageData();

        $this->render('favorites/index', [
            'pageTitle' => 'お気に入り商品',
            'cartItemCount' => $this->cart->itemCount(),
            'csrfToken' => csrf_token(),
            'successMessage' => get_flash('success'),
            'errorMessage' => get_flash('error'),
            ...$catalog,
            ...$this->favorites->viewData(),
        ]);
    }

    public function add(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect($this->redirectTarget());
        }

        $productId = (string) ($_POST['product_id'] ?? '');

        if (!ctype_digit($productId) || (int) $productId < 1) {
            flash('error', '商品の指定が不正です。');
            $this->redirect($this->redirectTarget());
        }

        try {
            flash('success', $this->favorites->add((int) $productId));
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }

        $this->redirect($this->redirectTarget());
    }

    public function remove(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            flash('error', '不正なリクエストです。');
            $this->redirect($this->redirectTarget('/favorites'));
        }

        $productId = (string) ($_POST['product_id'] ?? '');

        if (!ctype_digit($productId) || (int) $productId < 1) {
            flash('error', '商品の指定が不正です。');
            $this->redirect($this->redirectTarget('/favorites'));
        }

        flash('success', $this->favorites->remove((int) $productId));
        $this->redirect($this->redirectTarget('/favorites'));
    }

    private function redirectTarget(string $default = '/products'): string
    {
        $path = trim((string) ($_POST['redirect_to'] ?? ''));

        if ($path === '' || !str_starts_with($path, '/')) {
            return $default;
        }

        return $path;
    }
}
