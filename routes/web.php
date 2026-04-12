<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
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

Route::get('/api-docs', function () {
    return Inertia::render('ApiDocs');
})->name('api-docs');

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->name('dashboard');

Route::get('/battlearena', function () {
    return inertia('BattleArena');
})->name('battlearena');

Route::get('/event-test', function () {
    return Inertia::render('EventTest');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/anonymize', [AdminController::class, 'anonymize'])->name('users.anonymize');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
});

Route::get('/admin/login', function () {
    return Inertia::render('Auth/Login', ['isAdmin' => true]);
})->name('admin.login');

require __DIR__.'/auth.php';

Route::get('/{any}', function () {
    return Inertia::render('Welcome');
})->where('any', '^(?!api\/).*');

