<?php

use Illuminate\Support\Facades\Route;

// Rota catch-all para SPA - deve ser a última rota
// Todas as rotas serão gerenciadas pelo Vue Router
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*');

