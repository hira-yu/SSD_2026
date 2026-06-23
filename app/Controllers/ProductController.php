<?php

declare(strict_types=1);

class ProductController extends Controller
{
    private ProductService $products;
    private AuthService $auth;

    public function __construct()
    {
        $this->products = new ProductService();
        $this->auth = new AuthService();
    }

    public function index(): void
    {
        $result = $this->products->searchPublicProducts($_GET['name'] ?? null);

        $this->render('products/index', [
            'pageTitle' => '商品一覧・商品検索',
            'filters' => $result['filters'],
            'products' => $result['products'],
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
