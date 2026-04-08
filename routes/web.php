<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Events\BattleUpdated;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/event-test', function () {
    return Inertia::render('EventTest');
});

require __DIR__.'/auth.php';

Route::get('/{any}', function () {
    return Inertia::render('Welcome');
})->where('any', '^(?!api\/).*');

