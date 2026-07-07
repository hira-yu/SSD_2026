<?php

declare(strict_types=1);

class HomeController extends Controller
{
    private ProductService $products;
    private FavoriteService $favorites;

    public function __construct()
    {
        $this->products = new ProductService();
        $this->favorites = new FavoriteService();
    }

    public function index(): void
    {
        $catalog = $this->products->homePageData();

        $this->render('home', [
            'pageTitle' => 'トップページ',
            'appName' => (string) config('app.customer_ui.service_name', 'IPUT EC'),
            'favoriteProductIds' => $this->favorites->favoriteProductIds(),
            'csrfToken' => csrf_token(),
            ...$catalog,
        ]);
    }
}
