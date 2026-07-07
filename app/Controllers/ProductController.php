<?php

declare(strict_types=1);

class ProductController extends Controller
{
    private ProductService $products;
    private AuthService $auth;
    private CartService $cart;
    private FavoriteService $favorites;

    public function __construct()
    {
        $this->products = new ProductService();
        $this->auth = new AuthService();
        $this->cart = new CartService();
        $this->favorites = new FavoriteService();
    }

    public function index(): void
    {
        $result = $this->products->searchPublicProducts(
            $_GET['name'] ?? null,
            $_GET['category'] ?? null,
            $_GET['maker'] ?? null
        );

        $this->render('products/index', [
            'pageTitle' => '商品一覧・商品検索',
            'filters' => $result['filters'],
            'products' => $result['products'],
            'categoryOptions' => $result['categoryOptions'],
            'makerOptions' => $result['makerOptions'],
            'cartItemCount' => $this->cart->itemCount(),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            'csrfToken' => csrf_token(),
        ]);
    }

    public function show(string $productId): void
    {
        if (!ctype_digit($productId)) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $detail = $this->products->productDetailData((int) $productId);

        if ($detail === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $this->render('products/show', [
            'pageTitle' => (string) (($detail['product']['name'] ?? '商品詳細')),
            'csrfToken' => csrf_token(),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            ...$detail,
        ]);
    }

    public function receptionistIndex(): void
    {
        $this->auth->authorizeRole('receptionist');
        $result = $this->products->searchReceptionistProducts($_GET['product_no'] ?? null, $_GET['name'] ?? null);

        $this->render('staff/receptionist_products', [
            'pageTitle' => '注文受付係向け商品検索',
            'filters' => $result['filters'],
            'products' => $result['products'],
            'user' => $this->auth->user(),
            'roleLabel' => $this->auth->roleLabel('receptionist'),
            'csrfToken' => csrf_token(),
        ]);
    }
}
