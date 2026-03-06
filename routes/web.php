<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Events\BattleUpdated;
use Illuminate\Support\Facades\Broadcast;

Route::get('/event-test', function () {
    return Inertia::render('EventTest');
});

Route::get('/broadcast-test/{id}', function (Request $request, int $id) {
    $battleId = $id; 
    $data = [
        'message' => 'Hello from Laravel!',
        'timestamp' => now()->toIso8601String(),
    ];

    Broadcast::event(new BattleUpdated($data, $battleId));
    return response()->json(['status' => 'event sent']);
});

require __DIR__.'/auth.php';





