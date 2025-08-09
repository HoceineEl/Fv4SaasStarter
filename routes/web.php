<?php

use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\ImpersonateServiceProvider;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('web')->group(function () {
    Route::impersonate();
});
