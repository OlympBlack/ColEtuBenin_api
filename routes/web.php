<?php

use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

// ColCampus API - Web & Verification Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/validate-account/{id}/{hash}', [RegisterController::class, 'validateAccountFromEmail'])
    ->name('auth.validate-account');

Route::get('/auth/validation/{id}/{hash}', [RegisterController::class, 'validateAccountFromEmail'])
    ->name('auth.validation');

Route::get('/login', function () {
    return redirect(config('app.frontend_url', 'https://coletubenin.lavoixdabouloussi.org') . '/#/connexion');
})->name('login');

Route::get('/register', function () {
    return redirect(config('app.frontend_url', 'https://coletubenin.lavoixdabouloussi.org') . '/#/inscription');
})->name('register');
