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
            $this->finish(false, '不正なリクエストです。', $this->redirectTarget(), 422);
            return;
        }

        $productId = (string) ($_POST['product_id'] ?? '');

        if (!ctype_digit($productId) || (int) $productId < 1) {
            $this->finish(false, '商品の指定が不正です。', $this->redirectTarget(), 422);
            return;
        }

        try {
            $this->finish(true, $this->favorites->add((int) $productId), $this->redirectTarget(), 200, (int) $productId);
        } catch (RuntimeException $exception) {
            $this->finish(false, $exception->getMessage(), $this->redirectTarget(), 422, (int) $productId);
        }
    }

    public function remove(): void
    {
        if (!verify_csrf_token((string) ($_POST['_csrf'] ?? ''))) {
            $this->finish(false, '不正なリクエストです。', $this->redirectTarget('/favorites'), 422);
            return;
        }

        $productId = (string) ($_POST['product_id'] ?? '');

        if (!ctype_digit($productId) || (int) $productId < 1) {
            $this->finish(false, '商品の指定が不正です。', $this->redirectTarget('/favorites'), 422);
            return;
        }

        $this->finish(true, $this->favorites->remove((int) $productId), $this->redirectTarget('/favorites'), 200, (int) $productId);
    }

    private function redirectTarget(string $default = '/products'): string
    {
        $path = trim((string) ($_POST['redirect_to'] ?? ''));

        if ($path === '' || !str_starts_with($path, '/')) {
            return $default;
        }

        return $path;
    }

    private function finish(bool $ok, string $message, string $redirectTarget, int $statusCode = 200, ?int $productId = null): void
    {
        if ($this->expectsJson()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'ok' => $ok,
                'message' => $message,
                'favorite_count' => $this->favorites->itemCount(),
                'is_favorite' => $productId === null ? false : $this->favorites->has($productId),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        flash($ok ? 'success' : 'error', $message);
        $this->redirect($redirectTarget);
    }

    private function expectsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }
}
