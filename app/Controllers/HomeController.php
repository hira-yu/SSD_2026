<?php

declare(strict_types=1);

class HomeController extends Controller
{
    private ProductService $products;

    public function __construct()
    {
        $this->products = new ProductService();
    }

    public function index(): void
    {
        $catalog = $this->products->homePageData();

        $this->render('home', [
            'pageTitle' => 'トップページ',
            'appName' => (string) config('app.customer_ui.service_name', 'IPUT EC'),
            'appEnv' => (string) config('app.env', 'local'),
            'dbDriver' => (string) config('database.driver', 'sqlite'),
            'plannedFeatures' => config('app.planned_features', []),
            ...$catalog,
        ]);
    }
}
