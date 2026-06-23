<?php

declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => 'トップページ',
            'appName' => (string) config('app.name', '通信販売システム'),
            'appEnv' => (string) config('app.env', 'local'),
            'dbDriver' => (string) config('database.driver', 'sqlite'),
            'plannedFeatures' => config('app.planned_features', []),
        ]);
    }
}
