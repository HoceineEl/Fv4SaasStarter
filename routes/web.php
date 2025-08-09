<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('web')->group(function (Router $router) {
    $router->impersonate();
});
